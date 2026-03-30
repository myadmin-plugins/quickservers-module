# MyAdmin QuickServers Module

Composer plugin (type `myadmin-plugin`) — QuickServers (Rapid Deploy Servers) lifecycle management for MyAdmin.

## Commands

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpunit tests/ -v --coverage-clover coverage.xml --whitelist src/
```

## Architecture

**Entry**: `src/Plugin.php` — `Detain\MyAdminQuickservers\Plugin` (static methods only) 
**Tests**: `tests/PluginTest.php` · bootstrap stubs in `tests/bootstrap.php` 
**Autoload**: `Detain\MyAdminQuickservers\` → `src/` · `Detain\MyAdminQuickservers\Tests\` → `tests/`

**Plugin static fields** (`src/Plugin.php`):
- `$module = 'quickservers'` · `$type = 'module'` · `$name = 'Rapid Deploy Servers'`
- `$settings`: `PREFIX='qs'`, `TABLE='quickservers'`, `TITLE_FIELD='qs_hostname'`, `TITLE_FIELD2='qs_ip'`

**Hooks** (`Plugin::getHooks()` → Symfony `GenericEvent`):
- `quickservers.load_processing` → `loadProcessing` (registers enable/reactivate/disable/terminate)
- `quickservers.settings` → `getSettings`
- `quickservers.deactivate` → `getDeactivate`
- `quickservers.queue` → `getQueue` (renders `../../myadmin-kvm-vps/templates/{action}.sh.tpl`)

**IDE**: `.idea/` holds PhpStorm project config — `inspectionProfiles/` (static analysis rules), `composerJson.xml` (Composer integration), `code-comments.xml` (inline annotation settings), plus deployment and encoding configs.

## Conventions

**DB** — never use PDO:
```php
$db = get_module_db(self::$module);
$db->query("UPDATE {$settings['TABLE']} SET {$settings['PREFIX']}_status='active' WHERE {$settings['PREFIX']}_id='{$id}'", __LINE__, __FILE__);
while ($db->next_record(MYSQL_ASSOC)) { $row = $db->Record; }
```

**Logging**:
```php
myadmin_log(self::$module, 'info', 'message', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
```

**History**:
```php
$GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'pending-setup', $id, $custid);
$GLOBALS['tf']->history->add(self::$module.'queue', $id, 'initial_install', '', $custid);
```

**Settings access**:
```php
$settings = get_module_settings(self::$module);
$serviceInfo[$settings['PREFIX'].'_id']     // qs_id
$serviceInfo[$settings['PREFIX'].'_custid']  // qs_custid
```

**Admin email**:
```php
$smarty = new \TFSmarty();
$smarty->assign('qs_name', $value);
$email = $smarty->fetch('email/admin/qs_reactivated.tpl');
(new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/qs_reactivated.tpl');
```

## Testing

`tests/bootstrap.php` stubs these globals (do not re-define in test files):
- Functions: `myadmin_log`, `get_module_settings`, `get_module_db`, `run_event`, `validIp`, `reverse_dns`, `function_requirements`, `admin_email_qs_pending_setup`, `get_orm_class_from_table`
- `get_module_db` returns anonymous class with `query()`, `next_record(MYSQL_ASSOC)`, `$Record[]`, `num_rows()`
- Constants: `PRORATE_BILLING=1`, `ANNIVERSARY_BILLING=2`

## CI

- `.github/` — GitHub Actions workflows (`workflows/tests.yml`) for automated PHPUnit runs and coverage reporting
- `.scrutinizer.yml`: PHP 7.0, clover coverage, phpunit with `--whitelist src/`
- `.codeclimate.yml`: phpmd + duplication, excludes `tests/`
- `.bettercodehub.yml`: php language config
- `.travis.yml`: legacy (PHP 5.4–7.1), superseded by GitHub Actions

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
