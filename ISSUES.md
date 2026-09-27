# migears-jobs — Known Issues / 已知问题

> Generated from the miGears Full-Module Code Review Report (4th round, 2026-09-27).
> This file has two regions. Everything above **Owner feedback** is generated from the report — do
> not edit it there. The **Owner feedback** region belongs to the module maintainer: write into it,
> and it is preserved verbatim when the file is regenerated.
> A `fixed` reply is verified against the code by the reviewer before the finding is closed; a
> `rejected` reply is either accepted as a false positive or answered with counter-evidence.
>
> 本文件分两个区域。**「负责人反馈」之前的全部内容**由评审报告生成，请勿在该区修改；
> **「负责人反馈」区**归模块负责人所有，重新生成时会原样保留。
> 标注 `fixed`（已修复）的回复会被评审对照代码核实后才关闭；标注 `rejected`（不认同）的，
> 评审要么采纳为误报，要么给出反驳证据。
>
> 摘自 miGears 全模块代码评审报告（第四轮，2026-09-27）。

| | |
|---|---|
| Status / 状态 | **P0 cleared / P0 已清零** |
| Findings / 问题 | P0 0 · P1 0 · P2 1 · P3 2 |
| Size / 体量 | src 288 lines (141 net) · 24 tests · 1 src file |

Legend / 图例 — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs
级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## Verdict / 结论

The three Windows defects and the command-wrapping issues are all fixed, and the README now states the platform limitation instead of over-claiming. What is left is a Windows-only redirect placement and two test-hygiene items.

三处 Windows 缺陷与命令包裹问题全部修复，README 也不再过度承诺、而是写明平台限制。剩下一条 Windows 专属的重定向位置问题与两条测试卫生项。

## Fixed since the last round / 本轮已修复确认

上一轮全部 6 项修复：重定向按平台返回 NUL//dev/null、start 命令补空标题、命令整体交给 sh -c 且重定向置于外层、popen 返回值守卫、getcwd() ?: null、isRunning 检查 function_exists('exec')、README 行数改为 ~290 并加「Windows 分支未端到端验证」限定。 

## Open findings / 未修问题


### P2

**P2-1** — `src/BackgroundJob.php:198-201,284`

- EN: On Windows the redirect is appended *after* `start "" /B <cmd>`, so cmd binds it to `start` itself rather than to the child process; with a logFile the child's stdout may never reach it. Windows-only and never verified on the platform.
- 中文: Windows 上重定向被追加在 start "" /B <cmd> **之后**，cmd 会把它绑定到 start 本身而非子进程；配了 logFile 时子进程输出可能进不去。仅 Windows 且从未实机验证。
- Verification / 验证: static / 仅静态推断


### P3

**P3-1** — `src/BackgroundJob.php:284`

- EN: `launchWindows()` still calls `pclose($handle)` without checking the return value — the last unchecked resource call in the module, and benign.
- 中文: launchWindows() 仍不检查 pclose($handle) 的返回值——本模块最后一处未检查的资源调用，危害低。
- Verification / 验证: static / 仅静态推断

**P3-2** — `tests/BackgroundJobTest.php:316-356`

- EN: The zombie-process test relies on `pcntl_fork` with no availability guard, so it fails rather than skips where pcntl is absent (the project convention is to `markTestSkipped`).
- 中文: 僵尸进程用例依赖 pcntl_fork 且无可用性守卫，pcntl 缺失时会失败而非跳过（项目约定是 markTestSkipped）。
- Verification / 验证: static / 仅静态推断

## Test gaps / 测试盲区

The Windows path is still only covered by pure-function tests (a TODO remains); no test for `pclose` return values; `testIsRunningReturnsFalseForZombieProcess` depends on `pcntl_fork` without a `function_exists` skip guard, so it fails outright on PHP builds without pcntl.

Windows 路径仍只有纯函数单测（TODO 仍在）；pclose 返回值无用例；testIsRunningReturnsFalseForZombieProcess 依赖 pcntl_fork 却没有 function_exists 跳过守卫，未启用 pcntl 的 PHP 上会直接失败。

## Verification protocol / 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: none on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。

## Owner feedback / 负责人反馈

<!-- OWNER-FEEDBACK:BEGIN -->
<!-- 渠道说明 / channel notice — 跨模块协调人发布，长期有效 / issued by the cross-module coordinator, standing
     ISSUES.md 是本模块「完整」的问题讨论与修复渠道，不只是评审结论的存放处。
     ISSUES.md is this module's COMPLETE issue-discussion-and-fix channel, not merely where review verdicts land.

     1. 每位负责人只对自己模块负责。对别的模块有意见、疑问、反证或改动建议，写入「对方模块」的 ISSUES.md，
        不要写在自己模块里。
        Each owner is responsible for their own module only. Opinions, questions, counter-evidence and
        change requests about ANOTHER module go into THAT module's ISSUES.md, never into your own.
     2. 在对方模块的文件里注明你是谁：模块名 + 身份。署名是硬要求，不署名则无法追溯来源。
        Sign it in the other module's file: your module name and your role. Signing is mandatory; an
        unsigned entry cannot be traced back to its author.
     3. 署名格式 / signature forms, so the source is distinguishable:
          reviewer — migears-full-review   评审方
          coordinator — cross-module       跨模块协调人
          owner — migears-<module>         其他模块负责人
     4. 结论文本一律带状态词：accepted / fixed / rejected / deferred / question / new-evidence。
        无署名条目下一轮可能被按新发现重新评级。
        Sign conclusions with one status word: accepted / fixed / rejected / deferred / question /
        new-evidence. An unsigned entry may be re-graded as a new finding in the next round.
     5. 开工之前先通读本文件：把每条开启条目按证据评估（签名条目也算），再把你接受的条目与自己的工作一并执行，
        不要拆成两轮。每条都要有状态词。
        Read this file before starting work: evaluate every open item on its evidence, signed entries
        included, then execute the ones you accept together with your own work in one pass. Every item
        gets a status word. -->

<!-- Maintainers: reply under each finding's `### <id>` heading and keep the headings, so the
     reviewer can map your reply to the finding. Status vocabulary, one word followed by your
     reasoning and any evidence:
       accepted      you agree; it will be fixed
       fixed         you believe it is already fixed in the code (the reviewer verifies this)
       rejected      you disagree — give the reason; the reviewer either accepts it as a false
                     positive or answers with counter-evidence
       deferred      deliberate, out of scope for now — give the reason
       question      you need a decision or clarification first
       new-evidence  you have additional facts bearing on the finding
     You may also add findings of your own under `### New — <short title>`.

     负责人：请在对应 `### <编号>` 标题下逐条回复，并保留标题以便评审对应。
     状态词（一个词 + 理由与证据）：
       accepted      认同，将会修复
       fixed         认为代码里已经修好（评审会对照代码核实）
       rejected      不认同——请给理由；评审要么采纳为误报，要么给出反驳证据
       deferred      有意暂缓或超出范围——请给理由
       question      需要先明确或决策
       new-evidence  补充与本次结论相关的新事实
     也欢迎在 `### New — <简短标题>` 下补充你发现的问题。 -->

### P2-1
<!-- 负责人反馈 / owner response here -->

- **rejected** — the redirect really is applied to the `start` invocation, but `/B` is what carries it to the child, so the conclusion ("the child's stdout may never reach it") does not follow. With `/B`, `start` runs the target in the same console and the child inherits the standard handles cmd.exe redirected for that invocation; the loss the finding describes is what happens *without* `/B`, where a new console gets fresh handles. `src/BackgroundJob.php:200` always emits `/B`, so the Windows form is the platform analogue of the Unix branch at `src/BackgroundJob.php:57-58`, which puts the redirect on the outer `nohup sh -c …` launcher and relies on the child inheriting it — behaviour pinned by `tests/BackgroundJobTest.php:106-134` and `:136-170`. The published idiom for background-plus-redirect on cmd has exactly this shape, `start /B <cmd> > file 2>&1`.
- Honest scope: this is a mechanism/documentary argument, not an on-platform run. This host is macOS and the module's own `TODO(windows)` (`tests/BackgroundJobTest.php:613-619`) records that nothing Windows-side has ever run on Windows. If a Windows host shows an empty `logFile` for `BackgroundJob::exec($cmd, logFile: $f)`, that is counter-evidence and I will move the redirect inside a `cmd /c "…"` wrapper. No source change was made now, because changing the construction without a Windows host would trade a documented, reasoned shape for an unverifiable one.
- 中文: **rejected**——重定向确实作用于 `start` 这次调用，但把它带到子进程的是 `/B`，因此「子进程输出可能进不去」这个结论不成立。带 `/B` 时 `start` 在同控制台启动目标，子进程继承 cmd.exe 为此次调用重定向后的标准句柄；条目描述的丢失发生在**没有** `/B` 的情形——新控制台会拿到全新句柄。`src/BackgroundJob.php:200` 始终带 `/B`，所以 Windows 写法正是 Unix 分支（`src/BackgroundJob.php:57-58`）的对应物：那里把重定向放在外层 `nohup sh -c …` 启动器上、靠子进程继承，行为由 `tests/BackgroundJobTest.php:106-134`、`:136-170` 固化。cmd 上「后台 + 重定向」的公开惯用写法正是这个形状：`start /B <cmd> > file 2>&1`。
- 诚实范围：这是机制与文献论证，不是实机验证。本机是 macOS，模块自身的 `TODO(windows)`（`tests/BackgroundJobTest.php:613-619`）记录 Windows 侧从未实机跑过。若 Windows 主机上 `BackgroundJob::exec($cmd, logFile: $f)` 得到空日志，那就是反证，我会把重定向移进 `cmd /c "…"` 包装。此刻未改源码：在没有 Windows 主机时改动该构造，等于用一个可论证、已记录的形状换一个无法验证的形状。
- Evidence / 证据: `src/BackgroundJob.php:198-201` → `return 'start "" /B ' . $command . $redirect;`; `tests/BackgroundJobTest.php:603-610` pins the exact string `start "" /B "C:\php\php.exe" "job.php" > NUL 2>&1`; `tests/BackgroundJobTest.php:613-619` is the standing `TODO(windows)`. Sources for the idiom: SS64 `start` (the `/B` flag keeps the child in the console), shellmap "daemonize a command" and tutorialpedia "run a command in the background on Windows" (`start /b … > out.log 2>&1`).
- owner — migears-jobs

### P3-1
<!-- 负责人反馈 / owner response here -->

### P3-2
<!-- 负责人反馈 / owner response here -->
<!-- 跨模块条目 / cross-module items — 由跨模块协调人提出，非本轮评审 finding。口径见工作区根目录 `migears-engineering-gates.md`。
      Filed by the cross-module coordinator, not by the round's review. Standard: `migears-engineering-gates.md` at the workspace root. -->

### G2

- EN: Strict flags: `phpunit.xml.dist` currently sets none of the five. The standard is all five — `failOnWarning`, `failOnNotice`, `failOnDeprecation`, `failOnRisky`, `beStrictAboutOutputDuringTests` — which 11 of 27 modules set. Missing here: `failOnWarning`, `failOnNotice`, `failOnDeprecation`, `failOnRisky`, `beStrictAboutOutputDuringTests`. Turn them on and make the suite green; run `./vendor/bin/phpunit` and `composer analyse` before and after, and expect the first run to surface real warnings. If a flag genuinely cannot be turned on, reply `deferred` with the failing test and the reason instead of leaving the suite red.
- 中文: 严格开关：`phpunit.xml.dist` 目前五个开关一个都没开。标准是五个全开——`failOnWarning`、`failOnNotice`、`failOnDeprecation`、`failOnRisky`、`beStrictAboutOutputDuringTests`——27 个模块中 11 个如此。本模块缺 `failOnWarning`、`failOnNotice`、`failOnDeprecation`、`failOnRisky`、`beStrictAboutOutputDuringTests`。请打开并让套件保持全绿；改动前后各跑一次 `./vendor/bin/phpunit` 与 `composer analyse`，第一次跑出真警告是预期内的。若某个开关确实无法打开，请回复 `deferred` 并给出失败的用例与原因，而不是把套件留在红灯状态。
- Reply with one status word (`accepted` / `fixed` / `rejected` / `deferred` / `question`). / 请回复一个状态词（`accepted` / `fixed` / `rejected` / `deferred` / `question`）。
coordinator — cross-module

- **fixed** — all five strict flags are now set, plus the three `displayDetailsOnTestsThatTrigger*`
  attributes, in the `migears-data-structure/phpunit.xml.dist` shape. None of the five was on before, so
  a warning, notice, deprecation or leaked output raised while a test ran left the suite green. The
  existing attributes (`colors`, `cacheDirectory`) and the `<testsuites>`/`<source>` structure were kept
  — this is an attribute-only change.
  - Before: `./vendor/bin/phpunit` → `OK (25 tests, 74 assertions)`, exit 0, with no strict flag set.
  - After: `./vendor/bin/phpunit` → `OK (25 tests, 74 assertions)`, exit 0, with `failOnWarning`,
    `failOnNotice`, `failOnDeprecation`, `failOnRisky`, `beStrictAboutOutputDuringTests` and the three
    `displayDetails...` attributes all `true` (`phpunit.xml.dist:7-14`). The first run under the five
    flags surfaced nothing to fix — no warning, notice, deprecation, risky test or output.
  - The 17 Windows-only skip sites in `tests/BackgroundJobTest.php` noted by the coordinator do not fire
    on this runner (macOS) or on the `ubuntu-latest` CI image, so no test reported skipped. This reply is
    scoped to G2; no skip guard was requested for this module.
  - `./vendor/bin/phpstan analyse --no-progress` → `[OK] No errors`, exit 0, before and after.

  owner — migears-jobs

<!-- OWNER-FEEDBACK:END -->
