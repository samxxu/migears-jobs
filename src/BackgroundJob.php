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
        $redirect = $logFile !== null
            ? ' >> ' . escapeshellarg($logFile) . ' 2>&1'
            : ' > /dev/null 2>&1';

        if (self::isWindows()) {
            // Windows: start /B runs without a window
            $fullCommand = 'start /B ' . $command . $redirect;
            $pid = self::launchWindows($fullCommand, $workingDir);
        } else {
            // Unix-like: nohup + & for true background
            $fullCommand = 'nohup ' . $command . $redirect . ' & echo $!';
            $pid = self::launchUnix($fullCommand, $workingDir);
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
        $php = $phpBinary ?? PHP_BINARY;
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

        if (self::isWindows()) {
            exec('tasklist /FI "PID eq ' . $pid . '" 2>NUL', $output, $returnVar);
            return $returnVar === 0 && isset($output[3]) && str_contains($output[3], (string) $pid);
        }

        // Unix: kill -0 checks if process exists without sending a signal
        exec('kill -0 ' . $pid . ' 2>/dev/null', $_, $returnVar);
        return $returnVar === 0;
    }

    /**
     * Check if the current platform is Windows.
     */
    public static function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    /**
     * Launch a background process on Unix-like systems.
     *
     * Uses sh -c so shell features (&, $!) work correctly.
     */
    private static function launchUnix(string $command, ?string $workingDir): int
    {
        $cwd = $workingDir ?? getcwd();

        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout (we read PID from here)
            2 => ['pipe', 'w'],  // stderr
        ];

        // Wrap in sh -c for proper shell handling of & and $!
        $process = proc_open(['sh', '-c', $command], $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to launch background process');
        }

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $pid = (int) trim($output);

        if ($pid <= 0) {
            throw new \RuntimeException('Failed to get background process PID');
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
        $cwd = $workingDir ?? getcwd();
        pclose(popen('cd /D ' . escapeshellarg($cwd) . ' && ' . $command, 'r'));
        return 0; // PID not easily available on Windows with this method
    }
}
