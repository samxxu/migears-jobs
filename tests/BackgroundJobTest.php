<?php

declare(strict_types=1);

namespace MiGears\Jobs\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Jobs\BackgroundJob;

class BackgroundJobTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/migears_jobs_test_' . uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        // Kill background PHP jobs this test started (they are detached), so a
        // long-running one can't keep the process tree alive and stall the suite.
        @exec('pkill -f ' . escapeshellarg($this->tmpDir) . ' 2>/dev/null');

        // Clean up temp directory
        $files = glob($this->tmpDir . '/*');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        @rmdir($this->tmpDir);
    }

    // --- exec ---

    public function testExecRunsCommandInBackground(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Background PID not available on Windows');
        }

        $logFile = $this->tmpDir . '/output.log';
        $pid = BackgroundJob::exec('sleep 0.1 && echo "hello from bg"', logFile: $logFile);

        $this->assertIsInt($pid);
        $this->assertGreaterThan(0, $pid);

        // Give the background process time to complete
        usleep(300000); // 0.3 seconds

        $this->assertFileExists($logFile);
        $this->assertStringContainsString('hello from bg', file_get_contents($logFile));
    }

    public function testExecWithLogFile(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        $logFile = $this->tmpDir . '/job.log';
        $pid = BackgroundJob::exec('echo "log output"', logFile: $logFile);

        $this->assertGreaterThan(0, $pid);

        usleep(200000);

        $this->assertFileExists($logFile);
        $this->assertStringContainsString('log output', file_get_contents($logFile));
    }

    public function testExecWithWorkingDir(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        $logFile = $this->tmpDir . '/cwd_test.log';
        BackgroundJob::exec(
            'pwd',
            logFile: $logFile,
            workingDir: $this->tmpDir,
        );

        usleep(300000);

        $this->assertFileExists($logFile);
        $this->assertStringContainsString(
            rtrim($this->tmpDir, '/'),
            trim(file_get_contents($logFile))
        );
    }

    public function testExecReturnsPositivePid(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('PID not available on Windows');
        }

        $pid = BackgroundJob::exec('sleep 0.1');
        $this->assertGreaterThan(0, $pid);
    }

    public function testExecDoesNotBlockUntilCommandFinishes(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        $logFile = $this->tmpDir . '/async.log';

        $start = microtime(true);
        $pid = BackgroundJob::exec('sleep 2 && echo "done"', logFile: $logFile);
        $elapsed = microtime(true) - $start;

        // Fire-and-forget: exec() must return long before the 2s command completes
        $this->assertGreaterThan(0, $pid);
        $this->assertLessThan(
            1.0,
            $elapsed,
            'exec() should return immediately, not wait for the background command'
        );

        // Command still running right after launch
        $this->assertTrue(BackgroundJob::isRunning($pid));

        // The redirect covers the whole command now, so the log file appears as
        // soon as the job starts; wait for the content, not merely the file.
        $this->waitForFileContent($logFile, 'done', 4.0);
        usleep(300000); // let the wrapper shell exit
        $this->assertFalse(BackgroundJob::isRunning($pid));
    }

    public function testDetachedJobWritingStdoutIsNotKilled(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        $script = $this->tmpDir . '/spammer.php';
        $marker = $this->tmpDir . '/alive.txt';

        // Keeps writing to stdout; must survive detachment without SIGPIPE.
        $counter = var_export($marker, true);
        file_put_contents(
            $script,
            "<?php \$c = 0; for (;;) { file_put_contents({$counter}, ++\$c); usleep(50000); }"
        );

        // Multi-& command: the first job's stdout is not covered by the
        // trailing redirect, so it used to hold the pipe and die on SIGPIPE.
        BackgroundJob::exec('php ' . escapeshellarg($script) . ' & sleep 2');

        usleep(500000);
        $first = (int) (file_exists($marker) ? file_get_contents($marker) : 0);
        $this->assertGreaterThan(0, $first, 'spammer never wrote');

        usleep(400000);
        $second = (int) file_get_contents($marker);

        // Counter keeps increasing => the process is alive and writing.
        $this->assertGreaterThan(
            $first + 2,
            $second,
            'spammer died (likely SIGPIPE) and stopped writing'
        );
    }

    public function testCommandEndingWithAmpersandStillLogsToLogFile(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        // A trailing `&` used to split the launch line, so the redirect never
        // reached the real command and its output bypassed logFile entirely.
        $logFile = $this->tmpDir . '/trailing_amp.log';

        BackgroundJob::exec('echo "logged after amp" &', logFile: $logFile);

        $this->waitForFileContent($logFile, 'logged after amp');
        $this->assertStringContainsString('logged after amp', (string) file_get_contents($logFile));
    }

    public function testExecWorksWhenCurrentDirectoryIsGone(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        // getcwd() returns false once the directory is removed; that false used
        // to be handed straight to proc_open() and raised a TypeError.
        $gone = $this->tmpDir . '/gone';
        mkdir($gone);

        $previous = getcwd();
        chdir($gone);
        rmdir($gone);

        try {
            $this->assertFalse(getcwd(), 'precondition: the working directory should be gone');

            $pid = BackgroundJob::exec('true');
            $this->assertGreaterThan(0, $pid);
        } finally {
            chdir($previous);
        }
    }

    // --- helpers ---

    /**
     * Invoke a private static method on BackgroundJob, so the platform-specific
     * parsing can be exercised without running it on this OS.
     *
     * @param list<mixed> $args
     */
    private function invokePrivate(string $method, array $args): mixed
    {
        // Since PHP 8.1 reflection reaches private members directly, so there is
        // no setAccessible() call (that method is deprecated as of PHP 8.5).
        $ref = new \ReflectionMethod(BackgroundJob::class, $method);

        return $ref->invoke(null, ...$args);
    }

    private function waitForFile(string $path, float $timeout = 2.0): void
    {
        $deadline = microtime(true) + $timeout;
        while (!file_exists($path)) {
            if (microtime(true) >= $deadline) {
                $this->fail("Expected file '{$path}' was not created within {$timeout}s");
            }
            usleep(50000); // 50ms
        }
    }

    private function waitForFileContent(string $path, string $needle, float $timeout = 3.0): void
    {
        $deadline = microtime(true) + $timeout;

        while (true) {
            if (file_exists($path) && str_contains((string) file_get_contents($path), $needle)) {
                return;
            }

            if (microtime(true) >= $deadline) {
                $this->fail("Expected '{$needle}' in '{$path}' within {$timeout}s");
            }

            usleep(50000); // 50ms
        }
    }

    // --- script ---

    public function testScriptRunsPhpFile(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        $script = $this->tmpDir . '/worker.php';
        $outputFile = $this->tmpDir . '/worker_output.txt';

        file_put_contents($script, '<?php file_put_contents(' . var_export($outputFile, true) . ', "php job done\n");');

        $pid = BackgroundJob::script($script);
        $this->assertGreaterThan(0, $pid);

        usleep(300000);

        $this->assertFileExists($outputFile);
        $this->assertStringContainsString('php job done', file_get_contents($outputFile));
    }

    public function testScriptWithArgs(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        $script = $this->tmpDir . '/args_worker.php';
        $outputFile = $this->tmpDir . '/args_output.txt';

        file_put_contents($script, '<?php file_put_contents(' . var_export($outputFile, true) . ', implode(",", $argv));');

        $pid = BackgroundJob::script($script, ['apple', 'banana', 'cherry']);
        $this->assertGreaterThan(0, $pid);

        usleep(300000);

        $this->assertFileExists($outputFile);
        $content = file_get_contents($outputFile);
        $this->assertStringContainsString('apple', $content);
        $this->assertStringContainsString('banana', $content);
        $this->assertStringContainsString('cherry', $content);
    }

    public function testScriptEscapesPhpBinaryPathWithSpaces(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        // A "php binary" whose path contains a space. A one-line shebang
        // wrapper keeps the test fast while still exercising the shell
        // escaping of the binary path itself.
        $fakePhp = $this->tmpDir . '/fake php';
        file_put_contents($fakePhp, "#!/bin/sh\nexec " . escapeshellarg(PHP_BINARY) . " \"\$@\"\n");
        chmod($fakePhp, 0755);

        $script = $this->tmpDir . '/space_worker.php';
        $outputFile = $this->tmpDir . '/space_output.txt';
        file_put_contents($script, '<?php file_put_contents(' . var_export($outputFile, true) . ', "ok");');

        $pid = BackgroundJob::script($script, phpBinary: $fakePhp);
        $this->assertGreaterThan(0, $pid);

        $this->waitForFile($outputFile, 3.0);
        $this->assertSame('ok', file_get_contents($outputFile));
    }

    public function testScriptWithLogFile(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        $script = $this->tmpDir . '/log_worker.php';
        $logFile = $this->tmpDir . '/script.log';

        file_put_contents($script, '<?php echo "from stdout\n"; fprintf(STDERR, "from stderr\n");');

        $pid = BackgroundJob::script($script, logFile: $logFile);
        $this->assertGreaterThan(0, $pid);

        usleep(300000);

        $this->assertFileExists($logFile);
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('from stdout', $logContent);
        $this->assertStringContainsString('from stderr', $logContent);
    }

    // --- isRunning ---

    public function testIsRunningReturnsTrueForRunningProcess(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        $pid = BackgroundJob::exec('sleep 1');
        $this->assertGreaterThan(0, $pid);

        // Process should still be running immediately after launch
        $this->assertTrue(BackgroundJob::isRunning($pid));

        // Wait for it to finish
        sleep(2);
        $this->assertFalse(BackgroundJob::isRunning($pid));
    }

    public function testIsRunningReturnsFalseForInvalidPid(): void
    {
        $this->assertFalse(BackgroundJob::isRunning(0));
        $this->assertFalse(BackgroundJob::isRunning(-1));
        // A very high PID is very unlikely to exist
        $this->assertFalse(BackgroundJob::isRunning(99999999));
    }

    public function testIsRunningReturnsFalseForZombieProcess(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Zombie state is a Unix concept');
        }

        // A parent that forks a quick child and never waits leaves the child
        // as a zombie — its PID is still in the table, so naive existence
        // checks (kill -0) would report true.
        $logFile = $this->tmpDir . '/zombie.log';
        @unlink($logFile);
        $script = $this->tmpDir . '/zombie_parent.php';
        $marker = $this->tmpDir . '/child_pid.txt';

        file_put_contents(
            $script,
            '<?php '
            . '$pid = pcntl_fork(); '
            . 'if ($pid === 0) { usleep(150000); exit(0); } '
            . 'file_put_contents(' . var_export($marker, true) . ', (string) $pid); '
            . 'sleep(8);'
        );

        BackgroundJob::exec('php ' . escapeshellarg($script), logFile: $logFile);

        // Wait for the child to die and become a zombie (parent never waits).
        $this->waitForFile($marker, 3.0);
        usleep(400000);
        $childPid = (int) trim((string) @file_get_contents($marker));
        $this->assertGreaterThan(0, $childPid, 'child PID not captured');

        // Sanity: confirm the kernel really reports Z for that PID.
        exec('ps -p ' . $childPid . ' -o stat= 2>/dev/null', $statOut);
        $this->assertSame('Z', trim($statOut[0] ?? ''), 'child is not actually a zombie');

        // Now the regression: isRunning must report false for the zombie.
        $this->assertFalse(
            BackgroundJob::isRunning($childPid),
            'isRunning() should treat zombie processes as not running'
        );
    }

    public function testIsRunningReturnsFalseWhenExecIsDisabled(): void
    {
        if (BackgroundJob::isWindows()) {
            $this->markTestSkipped('Skipped on Windows');
        }

        // isRunning() probes with exec(); where a host disables it the call
        // would be a fatal Error rather than a clean false. Run the probe in a
        // child process so exec can be disabled for it alone.
        $probe = $this->tmpDir . '/exec_disabled.php';
        $class = dirname(__DIR__) . '/src/BackgroundJob.php';

        file_put_contents(
            $probe,
            '<?php require ' . var_export($class, true) . '; '
            . 'echo (MiGears\\Jobs\\BackgroundJob::isRunning(1) ? "true" : "false");'
        );

        exec(
            escapeshellarg(PHP_BINARY)
            . ' -d disable_functions=exec '
            . escapeshellarg($probe) . ' 2>&1',
            $output,
            $returnVar
        );

        $this->assertSame(0, $returnVar, 'probe failed: ' . implode("\n", $output));
        $this->assertSame('false', trim(implode('', $output)));
    }

    // --- pure helpers (state classification, command construction) ---

    public function testZombieStateDetectionCoversFlagBearingStates(): void
    {
        // A zombie's state letter may carry modifiers (ZN, Zs, Z+).
        foreach (['Z', 'ZN', 'Zs', 'Z+'] as $stat) {
            $this->assertTrue(
                $this->invokePrivate('isZombieState', [$stat]),
                "'{$stat}' should be treated as a zombie"
            );
        }

        // Live processes must not be mistaken for zombies.
        foreach (['S', 'Ss', 'S+', 'R', 'R+', 'SN', ''] as $stat) {
            $this->assertFalse(
                $this->invokePrivate('isZombieState', [$stat]),
                "'{$stat}' should not be treated as a zombie"
            );
        }
    }

    public function testTasklistPidParsing(): void
    {
        // tasklist /FO CSV /NH rows: "Image Name","PID","Session Name",...
        $this->assertTrue(
            $this->invokePrivate('tasklistHasPid', [['"chrome.exe","1234","Console","1","123,456 K"'], 1234])
        );

        // Header row (when /NH is absent) is not a match.
        $this->assertFalse(
            $this->invokePrivate('tasklistHasPid', [['"Image Name","PID","Session Name","Session#","Mem Usage"'], 1234])
        );

        // tasklist's no-match notice.
        $this->assertFalse(
            $this->invokePrivate('tasklistHasPid', [['INFO: No tasks are running which match the specified criteria.'], 1234])
        );

        // A longer PID must not match by substring.
        $this->assertFalse(
            $this->invokePrivate('tasklistHasPid', [['"chrome.exe","12345","Console","1","1 K"'], 1234])
        );

        // Digits in the image name are not the PID.
        $this->assertFalse(
            $this->invokePrivate('tasklistHasPid', [['"1234.exe","9999","Console","1","1 K"'], 1234])
        );

        // Digits inside Mem Usage are not the PID.
        $this->assertFalse(
            $this->invokePrivate('tasklistHasPid', [['"chrome.exe","9999","Console","1","1,234 K"'], 1234])
        );

        // Empty output.
        $this->assertFalse($this->invokePrivate('tasklistHasPid', [[], 1234]));
    }

    public function testRedirectUsesPlatformNullDevice(): void
    {
        // No log file: output is discarded via the platform's null device.
        $this->assertSame(' > /dev/null 2>&1', $this->invokePrivate('buildRedirect', [null, false]));
        $this->assertSame(' > NUL 2>&1', $this->invokePrivate('buildRedirect', [null, true]));

        // With a log file, both platforms append stdout and stderr to it.
        $expected = ' >> ' . escapeshellarg('job.log') . ' 2>&1';
        $this->assertSame($expected, $this->invokePrivate('buildRedirect', ['job.log', false]));
        $this->assertSame($expected, $this->invokePrivate('buildRedirect', ['job.log', true]));
    }

    public function testWindowsCommandPassesAnEmptyTitle(): void
    {
        // Without the empty title, `start` reads the first quoted token as the
        // window title, so a quoted command (as script() builds) never runs.
        $this->assertSame(
            'start "" /B "C:\php\php.exe" "job.php" > NUL 2>&1',
            $this->invokePrivate('windowsCommand', ['"C:\php\php.exe" "job.php"', ' > NUL 2>&1'])
        );
    }

    // TODO(windows): everything Windows-side is covered only by the pure-helper
    // tests above; none of it has run on a real Windows host. Verify end to end
    // there:
    //   - `tasklist /FI "PID eq N" /FO CSV /NH 2>NUL` runs, and exits 0 both
    //     when the PID exists and when no task matches;
    //   - isRunning() is true for a live PID and false once it has exited;
    //   - exec() with logFile === null discards output via NUL, not /dev/null;
    //   - exec()'s `start "" /B` path launches a quoted command (the script()
    //     form); it returns 0 as the PID, so isRunning() cannot confirm it.

    // --- isWindows ---

    public function testIsWindowsReturnsBoolean(): void
    {
        $this->assertIsBool(BackgroundJob::isWindows());
    }

    public function testIsWindowsConsistentWithPhpOsFamily(): void
    {
        $this->assertSame(
            PHP_OS_FAMILY === 'Windows',
            BackgroundJob::isWindows()
        );
    }

    // --- VERSION ---

    public function testVersionConstant(): void
    {
        $this->assertSame('2.0.0', BackgroundJob::VERSION);
    }
}
