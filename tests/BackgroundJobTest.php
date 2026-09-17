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
