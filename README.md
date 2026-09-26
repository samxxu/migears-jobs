# migears/jobs

![Version](https://img.shields.io/badge/version-2.0.0-blue)

Minimalist background job runner for PHP. Fire and forget — no queues, no workers, no database, no state management.

Launch shell commands or PHP scripts as asynchronous background processes with a single method call.

> **Background**: miGears is the open-source successor of **TinyGears**, a
> self-developed PHP framework. It was renamed and open-sourced recently because
> the name *TinyGears* is already taken in the open-source community.

## Features

- **Fire and forget** — launch background processes in one line
- **Cross-platform** — Unix (nohup) and Windows (start /B) support
- **PHP script helper** — convenient `script()` method for PHP files
- **Process status check** — `isRunning($pid)` to check if a job is still alive
- **Log redirection** — capture stdout + stderr to a log file
- **Working directory** — run jobs from any directory
- **Zero dependencies** — just PHP 8.1+
- **Single class, ~290 lines**

> Unix is the tested path. The Windows branch (`start "" /B` with `NUL`) is implemented but has not been verified end to end, and `exec()` cannot return a PID there — see the API notes.

## Installation

```bash
composer require migears/jobs
```

Requires: PHP 8.1+.

## Quick Start

### Run a command in the background

```php
use MiGears\Jobs\BackgroundJob;

// Launch and get PID
$pid = BackgroundJob::exec('php /path/to/long_task.php');

echo "Job started with PID: $pid";
```

### With log output

```php
$pid = BackgroundJob::exec(
    command: 'php /path/to/worker.php',
    logFile: '/var/log/jobs/worker.log',
);
```

### Run a PHP script

```php
$pid = BackgroundJob::script(
    scriptPath: __DIR__ . '/workers/send_emails.php',
    args: ['--batch=100', '--template=welcome'],
    logFile: '/var/log/jobs/email.log',
);
```

> **Web SAPI note** — `script()` runs the script with `PHP_BINARY` by default. Under php-fpm or an Apache PHP module that points at the web process, not the CLI binary, so pass an explicit CLI `$phpBinary` when launching jobs from a web request.

### Check if a job is still running

```php
$pid = BackgroundJob::exec('sleep 60');

if (BackgroundJob::isRunning($pid)) {
    echo "Job is still running...";
} else {
    echo "Job has finished.";
}
```

### Specify working directory

```php
BackgroundJob::exec(
    command: './process_files.sh',
    workingDir: '/var/data/uploads',
    logFile: '/var/log/process.log',
);
```

## API Reference

| Method | Returns | Description |
|--------|---------|-------------|
| `BackgroundJob::exec($command, $logFile, $workingDir)` | `int` (PID) | Execute a shell command in the background |
| `BackgroundJob::script($scriptPath, $args, $logFile, $phpBinary, $workingDir)` | `int` (PID) | Execute a PHP script in the background |
| `BackgroundJob::isRunning(int $pid)` | `bool` | Check if a process is still running |
| `BackgroundJob::isWindows()` | `bool` | Check if running on Windows |

> On Windows `exec()` cannot report a PID, so it returns `0` — there is then no status to poll with `isRunning()`.

## Use Cases

- Sending bulk emails asynchronously
- Processing uploaded files in the background
- Generating reports without blocking the request
- Triggering maintenance tasks
- Any slow operation that shouldn't block the user

## What It Doesn't Do

- **No queue system** — jobs are fire-and-forget, no ordering guarantee
- **No retry logic** — failed jobs stay failed
- **No status tracking** — only checks if process is alive
- **No scheduling** — use cron for scheduled jobs
- **No worker pool** — each job is a separate process
- **No launch feedback** — `exec()` returns a PID as soon as the shell forks, even if the command then fails to start (a missing binary, say). Pass `logFile` to capture such errors.

If you need any of the above, use a proper queue system (Redis Queue, RabbitMQ, etc.).

## Security Notes

`exec()` hands `$command` straight to a shell (`sh -c`) **without escaping or validation** — this is deliberate, so you can pass a full shell command. For the same reason, **never interpolate untrusted input** (user input, request params) into `$command`; escape it yourself or prefer `script()`, which shell-escapes the PHP path and every argument. `$logFile` and `$workingDir` are escaped for you.

## Design Philosophy

miGears Jobs follows the Unix philosophy: do one thing and do it well. It launches background processes. That's it. No layers of abstraction, no dependencies, no surprises.

For many PHP applications, a full queue system is overkill. Sometimes you just need to kick off a slow task and move on.

## License

MIT

---

# migears/jobs

![Version](https://img.shields.io/badge/version-2.0.0-blue)

极简 PHP 后台任务执行器。发了就忘（fire and forget）— 没有队列，没有 worker，没有数据库，没有状态管理。

一个方法调用，即可将 shell 命令或 PHP 脚本作为异步后台进程启动。

## 特性

- **发了就忘** — 一行代码启动后台进程
- **跨平台** — 支持 Unix（nohup）和 Windows（start /B）
- **PHP 脚本助手** — `script()` 方法便捷执行 PHP 文件
- **进程状态检查** — `isRunning($pid)` 检查任务是否仍在运行
- **日志重定向** — 将 stdout + stderr 捕获到日志文件
- **工作目录** — 可从任意目录运行任务
- **零依赖** — 只需要 PHP 8.1+
- **单文件，~290 行**

> Unix 为经验证的路径。Windows 分支（`start "" /B` 配 `NUL`）已实现但尚未端到端验证，且该平台下 `exec()` 无法返回 PID——详见 API 说明。

## 安装

```bash
composer require migears/jobs
```

要求：PHP 8.1+。

## 快速开始

### 后台执行命令

```php
use MiGears\Jobs\BackgroundJob;

// 启动并获取 PID
$pid = BackgroundJob::exec('php /path/to/long_task.php');

echo "任务已启动，PID：$pid";
```

### 带日志输出

```php
$pid = BackgroundJob::exec(
    command: 'php /path/to/worker.php',
    logFile: '/var/log/jobs/worker.log',
);
```

### 执行 PHP 脚本

```php
$pid = BackgroundJob::script(
    scriptPath: __DIR__ . '/workers/send_emails.php',
    args: ['--batch=100', '--template=welcome'],
    logFile: '/var/log/jobs/email.log',
);
```

> **Web SAPI 提示** —— `script()` 默认用 `PHP_BINARY` 运行脚本。在 php-fpm 或 Apache PHP 模块下，该值指向 Web 进程而非 CLI 二进制，因此从 Web 请求中触发任务时，请显式传入 CLI 的 `$phpBinary`。

### 检查任务是否仍在运行

```php
$pid = BackgroundJob::exec('sleep 60');

if (BackgroundJob::isRunning($pid)) {
    echo "任务仍在运行...";
} else {
    echo "任务已完成。";
}
```

### 指定工作目录

```php
BackgroundJob::exec(
    command: './process_files.sh',
    workingDir: '/var/data/uploads',
    logFile: '/var/log/process.log',
);
```

## API 参考

| 方法 | 返回值 | 说明 |
|------|--------|------|
| `BackgroundJob::exec($command, $logFile, $workingDir)` | `int` (PID) | 后台执行 shell 命令 |
| `BackgroundJob::script($scriptPath, $args, $logFile, $phpBinary, $workingDir)` | `int` (PID) | 后台执行 PHP 脚本 |
| `BackgroundJob::isRunning(int $pid)` | `bool` | 检查进程是否仍在运行 |
| `BackgroundJob::isWindows()` | `bool` | 检查是否在 Windows 上运行 |

> 在 Windows 上 `exec()` 无法取得 PID，会返回 `0`——此时没有可供 `isRunning()` 检查的状态。

## 适用场景

- 异步发送批量邮件
- 后台处理上传文件
- 不阻塞请求地生成报表
- 触发维护任务
- 任何不应该阻塞用户的慢操作

## 不做的事

- **没有队列系统** — 任务是发了就忘，不保证执行顺序
- **没有重试逻辑** — 失败的任务就是失败了
- **没有状态跟踪** — 只能检查进程是否存活
- **没有调度功能** — 定时任务请用 cron
- **没有 worker 池** — 每个任务都是独立进程
- **没有启动反馈** — shell fork 之后 `exec()` 就返回 PID，即使命令随后启动失败（例如二进制文件不存在）也一样返回；请传入 `logFile` 以捕获这类错误。

如果你需要以上任何功能，请使用专业的队列系统（Redis Queue、RabbitMQ 等）。

## 安全注意

`exec()` 会把 `$command` 原样交给 shell（`sh -c`）执行，**不做任何转义或校验**——这是有意的，因此你可以传入完整的 shell 命令。同理，**切勿把不受信任的输入**（用户输入、请求参数）拼进 `$command`；请自行转义，或优先使用 `script()`——它会对 PHP 路径和每个参数做 shell 转义。`$logFile` 与 `$workingDir` 会由本库负责转义。

## 设计哲学

miGears Jobs 遵循 Unix 哲学：只做一件事，做好它。它就是启动后台进程。仅此而已。没有层层抽象，没有依赖，没有意外。

对于许多 PHP 应用来说，完整的队列系统是过度设计。有时候你只是需要把一个慢任务踢到后台，然后继续。

## 许可证

MIT
