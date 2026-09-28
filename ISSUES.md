# migears-jobs — Known Issues / 已知问题

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> From the miGears Full-Module Code Review Report (4th round, 2026-09-27).

| | |
|---|---|
| Status / 状态 | **P0 cleared / P0 已清零** |
| Size / 体量 | src 288 lines (141 net) · 24 tests · 1 src file |

Legend / 图例 — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs
级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## At a glance / 状态一览

| | |
|---|---|
| Items / 条目 | P0 0 · P1 0 · P2 1 · P3 2 · other 1 |
| Answered / 已回复 | 2 of 4 |
| Waiting / 等待回复 | `P3-1`, `P3-2` |

| id | level | status | title |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **rejected** | On Windows the redirect is appended *after* `start '' /B <cmd>`, so cmd … |
| [`P3-1`](issues/P3-1.md) | P3 | **open** | `launchWindows()` still calls `pclose($handle)` without checking the … |
| [`P3-2`](issues/P3-2.md) | P3 | **open** | The zombie-process test relies on `pcntl_fork` with no availability … |
| [`G2`](issues/G2.md) | - | **fixed** | Strict flags: `phpunit.xml.dist` currently sets none of the five. The … |

## Verdict / 结论

The three Windows defects and the command-wrapping issues are all fixed, and the README now states the platform limitation instead of over-claiming. What is left is a Windows-only redirect placement and two test-hygiene items.

三处 Windows 缺陷与命令包裹问题全部修复，README 也不再过度承诺、而是写明平台限制。剩下一条 Windows 专属的重定向位置问题与两条测试卫生项。

## Fixed since the last round / 本轮已修复确认

上一轮全部 6 项修复：重定向按平台返回 NUL//dev/null、start 命令补空标题、命令整体交给 sh -c 且重定向置于外层、popen 返回值守卫、getcwd() ?: null、isRunning 检查 function_exists('exec')、README 行数改为 ~290 并加「Windows 分支未端到端验证」限定。 

## Test gaps / 测试盲区

The Windows path is still only covered by pure-function tests (a TODO remains); no test for `pclose` return values; `testIsRunningReturnsFalseForZombieProcess` depends on `pcntl_fork` without a `function_exists` skip guard, so it fails outright on PHP builds without pcntl.

Windows 路径仍只有纯函数单测（TODO 仍在）；pclose 返回值无用例；testIsRunningReturnsFalseForZombieProcess 依赖 pcntl_fork 却没有 function_exists 跳过守卫，未启用 pcntl 的 PHP 上会直接失败。

## Verification protocol / 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
