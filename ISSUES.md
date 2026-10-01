# migears-jobs — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (6th round, 2026-10-01).

| | |
|---|---|
| Status | **Best state** |
| Size | src 150 lines (net) · 27 tests · 1 src file |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 0 · P2 1 · P3 1 · other 0 |
| Settled | 4 of 6 |
| Waiting on the owner | _nothing_ |
| Waiting on the coordinator | _nothing_ |
| Waiting on the reviewer | `P2-1`, `P3-3` |
| Deferred, owing nobody | _nothing_ |

| id | level | status | title |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **rejected** | On Windows the redirect is appended *after* `start '' /B <cmd>`, so cmd … |
| [`P2-2`](issues/P2-2.md) | P2 | **verified** | The launch paths called `proc_open`/`popen` with no availability check, … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | `launchWindows()` still calls `pclose($handle)` without checking the … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | The zombie-process test relies on `pcntl_fork` with no availability … |
| [`P3-3`](issues/P3-3.md) | P3 | **rejected** | isRunning() on Windows interpolates $pid directly into the exec() … |
| [`G2`](issues/G2.md) | - | **verified** | Strict flags: `phpunit.xml.dist` currently sets none of the five. The … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **2** of 6 |
| By status | `rejected` 2 |
| Waiting on | reviewer 2 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P2** | [`P2-1`](issues/P2-1.md) | `rejected` | reviewer | On Windows the redirect is appended *after* `start '' /B <cmd>`, so cmd … |
| **P3** | [`P3-3`](issues/P3-3.md) | `rejected` | reviewer | isRunning() on Windows interpolates $pid directly into the exec() … |

## Verdict

The availability guards and the launcher-status check are in place, and the two rejected items stand on the code rather than on opinion.

## Fixed since the last round

No item was awaiting a verdict. Both rejections are upheld on the code: windowsCommand() always emits /B so the failure the finding needs cannot occur, and $pid is int-typed so the interpolation carries no metacharacters.

## Test gaps

The Windows half (launchWindows/windowsCommand/tasklistHasPid) still has no on-platform end-to-end test — the module says so itself; the NUL>:/dev/null redirect combination and the getcwd()-returns-false restore path are untested.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-jobs — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（6th round，2026-10-01）。

| | |
|---|---|
| 状态 | **状态最好** |
| 体量 | src 150 行（净）· 27 个用例 · 1 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 0 · P2 1 · P3 1 · 其他 0 |
| 已了结 | 4 / 6 |
| 等模块主 | _无_ |
| 等协调人 | _无_ |
| 等评审方 | `P2-1`, `P3-3` |
| 已暂缓，不欠谁 | _无_ |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **rejected** | Windows 上重定向被追加在 start "" /B <cmd> **之后**，cmd 会把它绑定到 start 本身而非子进程；配了 … |
| [`P2-2`](issues/P2-2.md) | P2 | **verified** | 启动路径调用 `proc_open`/`popen` 时不做可用性检查，因此在任一被禁用的主机上抛出的会是未捕获的 … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | launchWindows() 仍不检查 pclose($handle) 的返回值——本模块最后一处未检查的资源调用，危害低。 |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | 僵尸进程用例依赖 pcntl_fork 且无可用性守卫，pcntl 缺失时会失败而非跳过（项目约定是 markTestSkipped）。 |
| [`P3-3`](issues/P3-3.md) | P3 | **rejected** | Windows 下 isRunning() 将 $pid 直接拼入 exec() 命令字符串；虽然 $pid 是 int … |
| [`G2`](issues/G2.md) | - | **verified** | 严格开关：`phpunit.xml.dist` … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **2** / 6 |
| 按状态 | `rejected` 2 |
| 等在谁 | 评审方 2 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P2** | [`P2-1`](issues/P2-1.md) | `rejected` | 评审方 | Windows 上重定向被追加在 start "" /B <cmd> **之后**，cmd 会把它绑定到 start 本身而非子进程；配了 … |
| **P3** | [`P3-3`](issues/P3-3.md) | `rejected` | 评审方 | Windows 下 isRunning() 将 $pid 直接拼入 exec() 命令字符串；虽然 $pid 是 int … |

## 结论

可用性守卫与启动器状态检查都在位，两条被驳回的条目也立于代码之上而非意见之上。

## 本轮已修复确认

No item was awaiting a verdict. Both rejections are upheld on the code: windowsCommand() always emits /B so the failure the finding needs cannot occur, and $pid is int-typed so the interpolation carries no metacharacters.

## 测试盲区

Windows 半边（launchWindows/windowsCommand/tasklistHasPid）仍无实机端到端用例——模块自己写明了；NUL>:/dev/null 重定向组合与 getcwd() 返回 false 的恢复路径无用例。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
