# migears-jobs — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (5th round, 2026-09-28).

| | |
|---|---|
| Status | **P2 open** |
| Size | src 142 lines (net) · 25 tests · 1 src file |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 0 · P2 1 · P3 1 · other 0 |
| Settled | 4 of 6 |
| Waiting on the owner | `P3-3` |
| Waiting on the coordinator | _nothing_ |
| Waiting on the reviewer | `P2-1` |
| Deferred, owing nobody | _nothing_ |

| id | level | status | title |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **rejected** | On Windows the redirect is appended *after* `start '' /B <cmd>`, so cmd … |
| [`P2-2`](issues/P2-2.md) | P2 | **verified** | The launch paths called `proc_open`/`popen` with no availability check, … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | `launchWindows()` still calls `pclose($handle)` without checking the … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | The zombie-process test relies on `pcntl_fork` with no availability … |
| [`P3-3`](issues/P3-3.md) | P3 | **open** | isRunning() on Windows interpolates $pid directly into the exec() … |
| [`G2`](issues/G2.md) | - | **verified** | Strict flags: `phpunit.xml.dist` currently sets none of the five. The … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **2** of 6 |
| By status | `open` 1 · `rejected` 1 |
| Waiting on | owner 1 · reviewer 1 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P2** | [`P2-1`](issues/P2-1.md) | `rejected` | reviewer | On Windows the redirect is appended *after* `start '' /B <cmd>`, so cmd … |
| **P3** | [`P3-3`](issues/P3-3.md) | `open` | owner | isRunning() on Windows interpolates $pid directly into the exec() … |

## Verdict

A compact background job launcher with careful shell escaping on both Unix and Windows; the one open P2 is a Windows-only redirect-placement claim that the owner rejected with reference to Microsoft documentation.

## Fixed since the last round

G2 strict flags confirmed complete; P3-1 pclose() return value now checked and throws on non-zero; P3-2 zombie test guarded with function_exists(pcntl_fork).

## Test gaps

No end-to-end Windows verification (noted as TODO in tests); no test for wait() timeout behavior; no test for isRunning() with invalid PID values.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-jobs — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（5th round，2026-09-28）。

| | |
|---|---|
| 状态 | **P2 待修** |
| 体量 | src 142 行（净）· 25 个用例 · 1 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 0 · P2 1 · P3 1 · 其他 0 |
| 已了结 | 4 / 6 |
| 等模块主 | `P3-3` |
| 等协调人 | _无_ |
| 等评审方 | `P2-1` |
| 已暂缓，不欠谁 | _无_ |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P2-1`](issues/P2-1.md) | P2 | **rejected** | Windows 上重定向被追加在 start "" /B <cmd> **之后**，cmd 会把它绑定到 start 本身而非子进程；配了 … |
| [`P2-2`](issues/P2-2.md) | P2 | **verified** | 启动路径调用 `proc_open`/`popen` 时不做可用性检查，因此在任一被禁用的主机上抛出的会是未捕获的 … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | launchWindows() 仍不检查 pclose($handle) 的返回值——本模块最后一处未检查的资源调用，危害低。 |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | 僵尸进程用例依赖 pcntl_fork 且无可用性守卫，pcntl 缺失时会失败而非跳过（项目约定是 markTestSkipped）。 |
| [`P3-3`](issues/P3-3.md) | P3 | **open** | Windows 下 isRunning() 将 $pid 直接拼入 exec() 命令字符串；虽然 $pid 是 int … |
| [`G2`](issues/G2.md) | - | **verified** | 严格开关：`phpunit.xml.dist` … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **2** / 6 |
| 按状态 | `open` 1 · `rejected` 1 |
| 等在谁 | 模块主 1 · 评审方 1 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P2** | [`P2-1`](issues/P2-1.md) | `rejected` | 评审方 | Windows 上重定向被追加在 start "" /B <cmd> **之后**，cmd 会把它绑定到 start 本身而非子进程；配了 … |
| **P3** | [`P3-3`](issues/P3-3.md) | `open` | 模块主 | Windows 下 isRunning() 将 $pid 直接拼入 exec() 命令字符串；虽然 $pid 是 int … |

## 结论

一个紧凑的后台作业启动器，Unix 与 Windows 两端均谨慎做 shell 转义；唯一开放的 P2 是 Windows 专属的重定向位置问题，负责人已引用微软文档驳回。

## 本轮已修复确认

G2 strict flags confirmed complete; P3-1 pclose() return value now checked and throws on non-zero; P3-2 zombie test guarded with function_exists(pcntl_fork).

## 测试盲区

无 Windows 端到端验证（测试中标注为 TODO）；无 wait() 超时行为测试；无无效 PID 值的 isRunning() 测试。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
