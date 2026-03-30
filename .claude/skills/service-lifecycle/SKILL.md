---
name: service-lifecycle
description: Implements enable, reactivate, disable, or terminate closures inside Plugin::loadProcessing() using the get_module_settings/get_module_db/history->add pattern. Use when user says 'add lifecycle', 'handle enable', 'implement terminate', 'add reactivate', or needs to change what happens during service state transitions. Key: always UPDATE {PREFIX}_status via $db->query() and write to history. Do NOT use for getDeactivate (that is a separate GenericEvent handler), queue processing, or settings registration.
---
# service-lifecycle

## Critical

- **Never use PDO.** Always use `$db = get_module_db(self::$module)`.
- **Every lifecycle closure must call `$GLOBALS['tf']->history->add()`** on the table and on `self::$module.'queue'` — omitting either breaks the audit trail.
- All four closures (`setEnable`, `setReactivate`, `setDisable`, `setTerminate`) **must be present** and the chain must end with `->register()`. Missing any breaks service handler registration.
- Access service fields only via `$settings['PREFIX']` — never hardcode `qs_` column names.

## Instructions

1. **Open `src/Plugin.php`** and locate `loadProcessing(GenericEvent $event)`. The method body starts with `$service = $event->getSubject()` and the full chain is `$service->setModule()->setEnable()->setReactivate()->setDisable()->setTerminate()->register()`.
   - Verify the method exists and ends with `->register()` before editing.

2. **Get settings and DB inside each closure** — both must be fetched inside the closure itself, not outside:
   ```php
   ->setEnable(function ($service) {
       $serviceInfo = $service->getServiceInfo();
       $settings = get_module_settings(self::$module);
       $db = get_module_db(self::$module);
   ```

3. **Update `{PREFIX}_status`** with a raw query — no PDO, no ORM for status changes:
   ```php
   $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='pending-setup' "
       . "where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'",
       __LINE__, __FILE__);
   ```
   Valid status values in this module: `'pending-setup'`, `'active'`, `'disabled'`.

4. **Write history in this order** — table-level change first, then queue action:
   ```php
   $GLOBALS['tf']->history->add(
       $settings['TABLE'],        // 'quickservers'
       'change_status',
       'pending-setup',           // new status string
       $serviceInfo[$settings['PREFIX'].'_id'],
       $serviceInfo[$settings['PREFIX'].'_custid']
   );
   $GLOBALS['tf']->history->add(
       self::$module.'queue',     // 'quickserversqueue'
       $serviceInfo[$settings['PREFIX'].'_id'],
       'initial_install',         // queue action verb
       '',
       $serviceInfo[$settings['PREFIX'].'_custid']
   );
   ```
   - Step uses output from Step 2 (`$settings`, `$serviceInfo`).

5. **For reactivate**: check `_server_status === 'deleted'` or `_ip == ''` to decide between `pending-setup` (re-provision) vs `active` (resume). Both branches need their own DB update + two history entries. After both branches, send the admin email:
   ```php
   $smarty = new \TFSmarty();
   $smarty->assign('qs_name', $serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name']);
   $email = $smarty->fetch('email/admin/qs_reactivated.tpl');
   $subject = $serviceInfo[$settings['TITLE_FIELD']] . ' ' . ... . ' ' . $settings['TBLNAME'] . ' Reactivated';
   (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/qs_reactivated.tpl');
   ```

6. **For terminate**: release master server, release IPs, remove reverse DNS:
   ```php
   $db->query("update {$settings['PREFIX']}_masters set {$settings['PREFIX']}_available=1 where {$settings['PREFIX']}_id={$serviceInfo[$settings['PREFIX'].'_server']}", __LINE__, __FILE__);
   // select qs_ips, then:
   $db->query("update {$settings['PREFIX']}_ips set ips_main=0,ips_usable=1,ips_used=0,ips_{$settings['PREFIX']}=0 where ips_{$settings['PREFIX']}='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
   function_requirements('reverse_dns');
   foreach ($ips as $ip) {
       if (validIp($ip)) { reverse_dns($ip, '', 'remove_reverse'); }
   }
   $GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'destroy', '', $serviceInfo[$settings['PREFIX'].'_custid']);
   ```

7. **Run tests** to verify: `vendor/bin/phpunit`
   - Confirm `testLoadProcessingRegistersAllServiceHandlers` passes.
   - Confirm `testLoadProcessingReferencesSettingsKeys` passes.

## Examples

**User says:** "add a disable handler that sets status to disabled and logs to history"

**Actions taken:**
1. Open `src/Plugin.php`, find the empty `->setDisable(function ($service) { })` closure.
2. Replace with:
   ```php
   ->setDisable(function ($service) {
       $serviceInfo = $service->getServiceInfo();
       $settings = get_module_settings(self::$module);
       $db = get_module_db(self::$module);
       $GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'disabled', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
       $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='disabled' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
       $GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'disable', '', $serviceInfo[$settings['PREFIX'].'_custid']);
   })
   ```
3. Run `vendor/bin/phpunit` — all tests pass.

**Result:** `qs_status` column set to `'disabled'`, two history rows written (table + queue).

## Common Issues

- **`Call to undefined function get_module_settings()`** in tests: the bootstrap stub at `tests/bootstrap.php` must define it. Check that your test file does not re-define stubs already in bootstrap — PHPUnit will fatal on duplicate function declarations.
- **History not recorded / silent failure**: `$GLOBALS['tf']` is not set. In production this is always present; in tests the bootstrap does not stub it. Add `$GLOBALS['tf'] = new stdClass(); $GLOBALS['tf']->history = new class { public function add(...$a){} };` in your test setUp if needed.
- **`->register()` missing**: if you add a closure but forget to keep `->register()` at the end of the chain, no lifecycle handlers fire. The chain must be one fluent expression ending with `->register()`.
- **Column not found (`qs_whatever`)**: you hardcoded the prefix. Replace with `$settings['PREFIX'].'_whatever'` so the pattern works if PREFIX ever changes.
- **`validIp()` or `reverse_dns()` undefined**: call `function_requirements('reverse_dns')` before using either (as in the terminate closure). This lazy-loads the function file.