<?php

declare(strict_types=1);

namespace MiGears\Jobs;

/**
 * Minimalist background job runner.
 *
 * Launches shell commands as asynchronous background processes.
 * No queues, no workers, no state management — just fire and forget.
 *
 * Usage:
 *   // Run a command in the background
 *   $pid = BackgroundJob::exec('php /path/to/worker.php');
 *
 *   // With log output
 *   $pid = BackgroundJob::exec('php worker.php', logFile: '/tmp/job.log');
 *
 *   // Check if a process is still running
 *   if (BackgroundJob::isRunning($pid)) { ... }
 */
class BackgroundJob
{
    public const VERSION = '2.0.0';

    /**
     * Execute a command in the background.
     *
     * Returns the PID of the background process.
     *
     * @param string $command Shell command to execute
     * @param string|null $logFile Path to log file (stdout + stderr). If null, output is discarded.
     * @param string|null $workingDir Working directory for the command. If null, uses current.
     *
     * @return int Process ID (PID)
     */
    public static function exec(
        string $command,
        ?string $logFile = null,
        ?string $workingDir = null,
    ): int {
        $windows = self::isWindows();
        $redirect = self::buildRedirect($logFile, $windows);

        if ($windows) {
            $fullCommand = self::windowsCommand($command, $redirect);
            $pid = self::launchWindows($fullCommand, $workingDir);
        } else {
            $pidFile = self::createPidFile();
            // The command is handed to `sh -c` as a single argument, so nohup,
            // the redirect and `$!` all apply to it as a whole: a command ending
            // in (or containing) `&` would otherwise split the line and leave
            // `$!` pointing at an empty, instantly-dead subshell.
            // The PID comes from a temp file, never a pipe, so the detached job
            // cannot hold our descriptors open — no read-blocking, no SIGPIPE.
            $fullCommand = 'nohup sh -c ' . escapeshellarg($command) . $redirect
                . ' & echo $! > ' . escapeshellarg($pidFile);
            $pid = self::launchUnix($fullCommand, $pidFile, $workingDir);
        }

        return $pid;
    }

    /**
     * Execute a PHP script in the background.
     *
     * Convenience wrapper around exec() for PHP scripts.
     *
     * @param string $scriptPath Path to the PHP script
     * @param array<int, string> $args Command line arguments
     * @param string|null $logFile Path to log file
     * @param string|null $phpBinary Path to PHP binary (default: PHP_BINARY)
     * @param string|null $workingDir Working directory
     */
    public static function script(
        string $scriptPath,
        array $args = [],
        ?string $logFile = null,
        ?string $phpBinary = null,
        ?string $workingDir = null,
    ): int {
        $php = escapeshellarg($phpBinary ?? PHP_BINARY);
        $script = escapeshellarg($scriptPath);
        $argString = $args !== [] ? ' ' . implode(' ', array_map('escapeshellarg', $args)) : '';

        return self::exec($php . ' ' . $script . $argString, $logFile, $workingDir);
    }

    /**
     * Check if a process with the given PID is still running.
     */
    public static function isRunning(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        // A host may disable exec(); it is then removed from the function table,
        // so function_exists() reports false. Report "not running" rather than
        // letting a fatal Error escape.
        if (! function_exists('exec')) {
            return false;
        }

        if (self::isWindows()) {
            exec('tasklist /FI "PID eq ' . $pid . '" /FO CSV /NH 2>NUL', $output, $returnVar);
            if ($returnVar !== 0) {
                return false;
            }
            return self::tasklistHasPid($output, $pid);
        }

        // Unix: the ps state tells us whether the process is gone.
        exec('ps -p ' . $pid . ' -o stat= 2>/dev/null', $statOut, $statRc);
        if ($statRc !== 0 || $statOut === []) {
            return false;
        }
        $stat = trim($statOut[0]);

        return $stat !== '' && $stat !== '?' && ! self::isZombieState($stat);
    }

    /**
     * Whether a ps state string denotes a finished (zombie / defunct) process.
     *
     * The state letter comes first, so ZN, Zs and Z+ are zombies too — match
     * the prefix, not the whole string.
     */
    private static function isZombieState(string $stat): bool
    {
        return str_starts_with($stat, 'Z');
    }

    /**
     * Whether a tasklist CSV dump contains the given PID.
     *
     * tasklist columns are "Image Name","PID","Session Name",... — the PID is
     * the second field, so parse the CSV instead of guessing a line offset.
     *
     * @param list<string> $lines
     */
    private static function tasklistHasPid(array $lines, int $pid): bool
    {
        foreach ($lines as $line) {
            // $escape is passed explicitly: omitting it is deprecated as of
            // PHP 8.4 and its default value is set to change.
            $fields = str_getcsv($line, ',', '"', '');
            if (isset($fields[1]) && (int) $fields[1] === $pid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current platform is Windows.
     */
    public static function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    /**
     * Create a temp file the child shell writes the PID into.
     */
    private static function createPidFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bg');
        if ($path === false) {
            throw new \RuntimeException('Failed to create temporary PID file');
        }
        return $path;
    }

    /**
     * Build the stdout/stderr redirect for the platform.
     *
     * The null device is not portable: NUL on Windows, /dev/null elsewhere.
     */
    private static function buildRedirect(?string $logFile, bool $windows): string
    {
        if ($logFile !== null) {
            return ' >> ' . escapeshellarg($logFile) . ' 2>&1';
        }

        return $windows ? ' > NUL 2>&1' : ' > /dev/null 2>&1';
    }

    /**
     * Build a Windows launch command.
     *
     * `start` reads the first quoted token as a window title, so an explicit
     * empty title is passed — otherwise a quoted command (as produced by
     * script()) would be swallowed as the title and never run.
     */
    private static function windowsCommand(string $command, string $redirect): string
    {
        return 'start "" /B ' . $command . $redirect;
    }

    /**
     * Resolve the child's working directory.
     *
     * getcwd() returns false once the current directory has been removed, and
     * that false must not reach proc_open() or escapeshellarg(); null means
     * "inherit", which is what omitting $workingDir gives anyway.
     */
    private static function workingDirectory(?string $workingDir): ?string
    {
        if ($workingDir !== null) {
            return $workingDir;
        }

        return getcwd() ?: null;
    }

    /**
     * Launch a background process on Unix-like systems.
     *
     * Uses sh -c so shell features (&, $!) work correctly.
     */
    private static function launchUnix(string $command, string $pidFile, ?string $workingDir): int
    {
        $cwd = self::workingDirectory($workingDir);

        // Every fd points at /dev/null: the detached job (and anything it
        // spawns) can never hold our descriptors open, so there is no read-
        // blocking and no risk of SIGPIPE killing the job. The PID arrives
        // via the temp file instead of a pipe.
        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ];

        // Wrap in sh -c for proper shell handling of & and $!
        $process = proc_open(['sh', '-c', $command], $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            @unlink($pidFile);
            throw new \RuntimeException('Failed to launch background process');
        }

        // proc_close waits for the parent shell only, which exits right after
        // writing the PID file — long before the detached job finishes.
        $exitCode = proc_close($process);

        $pidText = file_exists($pidFile) ? (string) file_get_contents($pidFile) : '';
        @unlink($pidFile);

        $pid = (int) trim($pidText);

        if ($pid <= 0) {
            throw new \RuntimeException(
                'Failed to get background process PID'
                . ($exitCode === 0 ? '' : " (launch shell exited with status {$exitCode})")
            );
        }

        return $pid;
    }

    /**
     * Launch a background process on Windows.
     *
     * Note: Windows does not easily return a PID for background processes
     * launched via start /B. We return 0 on Windows.
     */
    private static function launchWindows(string $command, ?string $workingDir): int
    {
        $cwd = self::workingDirectory($workingDir);

        $launch = $cwd === null
            ? $command
            : 'cd /D ' . escapeshellarg($cwd) . ' && ' . $command;

        $handle = popen($launch, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Failed to launch background process');
        }

        pclose($handle);

        return 0; // PID not easily available on Windows with this method
    }
}
