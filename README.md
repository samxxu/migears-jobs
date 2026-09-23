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
- **Single class, ~150 lines**

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

## Use Cases

- Sending bulk emails asynchronously
- Processing uploaded files in the background
- Generating reports without blocking the request
- Triggering maintenance tasks
- Any slow operation that shouldn't block the user

## What It Doesn't Do

- **No queue system** — jobs are fire-and-forget, no ordering guarantee
- **No retry logic** — failed jobs stay failed
- **No status tracking** — only checks if process is alive (Unix only)
- **No scheduling** — use cron for scheduled jobs
- **No worker pool** — each job is a separate process

If you need any of the above, use a proper queue system (Redis Queue, RabbitMQ, etc.).

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
- **单文件，~150 行**

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

## 适用场景

- 异步发送批量邮件
- 后台处理上传文件
- 不阻塞请求地生成报表
- 触发维护任务
- 任何不应该阻塞用户的慢操作

## 不做的事

- **没有队列系统** — 任务是发了就忘，不保证执行顺序
- **没有重试逻辑** — 失败的任务就是失败了
- **没有状态跟踪** — 只能检查进程是否存活（仅限 Unix）
- **没有调度功能** — 定时任务请用 cron
- **没有 worker 池** — 每个任务都是独立进程

如果你需要以上任何功能，请使用专业的队列系统（Redis Queue、RabbitMQ 等）。

## 设计哲学

miGears Jobs 遵循 Unix 哲学：只做一件事，做好它。它就是启动后台进程。仅此而已。没有层层抽象，没有依赖，没有意外。

对于许多 PHP 应用来说，完整的队列系统是过度设计。有时候你只是需要把一个慢任务踢到后台，然后继续。

## 许可证

MIT
