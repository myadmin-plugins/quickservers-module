---
name: plugin-hook
description: Adds a new event hook to Plugin::getHooks() and implements the corresponding static handler method with proper GenericEvent signature. Use when user says 'add hook', 'new event', 'handle event', or needs to respond to a new quickservers.* event. Do NOT use for modifying existing hook logic — only for registering new hooks.
---
# plugin-hook

## Critical

- All handler methods MUST be `public static` — never instance methods.
- The single parameter MUST be type-hinted as `\Symfony\Component\EventDispatcher\GenericEvent $event`.
- Hook keys MUST use `self::$module.'.<event_name>'` — never a hardcoded string like `'quickservers.foo'`.
- Handler value MUST be `[__CLASS__, 'methodName']` — never a closure or string.
- Do NOT declare a return type on handler methods; existing handlers have no return type declaration.
- Logging calls MUST use `myadmin_log(self::$module, ...)` — never `error_log` or `var_dump`.

## Instructions

1. **Choose a hook name and method name.**
   - Hook key pattern: `quickservers.<snake_case_event>` (e.g. `quickservers.suspend`).
   - Method name: camelCase verb matching the event (e.g. `getSuspend`, `loadSuspend`).
   - Verify no existing key in `getHooks()` in `src/Plugin.php` conflicts.

2. **Register the hook in `getHooks()` (`src/Plugin.php`).**
   Add one entry to the returned array:
   ```php
   self::$module.'.suspend' => [__CLASS__, 'getSuspend'],
   ```
   Full `getHooks()` after adding:
   ```php
   public static function getHooks()
   {
       return [
           self::$module.'.load_processing' => [__CLASS__, 'loadProcessing'],
           self::$module.'.settings'        => [__CLASS__, 'getSettings'],
           self::$module.'.deactivate'      => [__CLASS__, 'getDeactivate'],
           self::$module.'.queue'           => [__CLASS__, 'getQueue'],
           self::$module.'.suspend'         => [__CLASS__, 'getSuspend'],
       ];
   }
   ```
   Verify the array key uses `self::$module` before proceeding.

3. **Implement the handler method in `src/Plugin.php`.**
   Add after the last existing handler. Required structure:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function getSuspend(GenericEvent $event)
   {
       $settings = get_module_settings(self::$module);
       $serviceInfo = $event->getSubject();
       myadmin_log(self::$module, 'info', self::$name.' Suspend', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
       $db = get_module_db(self::$module);
       $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='suspended' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
       $GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'suspended', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
   }
   ```
   - Use `$event->getSubject()` when the subject is a service info array or service object.
   - Always pass `__LINE__, __FILE__` as the last two args to `$db->query()` and `myadmin_log()`.
   - Access service fields as `$serviceInfo[$settings['PREFIX'].'_fieldname']` — never `$serviceInfo['qs_fieldname']` directly.

4. **Add a test in `tests/PluginTest.php`.**
   Follow the pattern for existing hook tests:
   ```php
   public function testGetHooksContainsSuspend(): void
   {
       $hooks = Plugin::getHooks();
       $this->assertArrayHasKey('quickservers.suspend', $hooks);
       $this->assertSame('getSuspend', $hooks['quickservers.suspend'][1]);
   }

   public function testGetSuspendAcceptsGenericEvent(): void
   {
       $method = $this->reflection->getMethod('getSuspend');
       $params = $method->getParameters();
       $this->assertCount(1, $params);
       $type = $params[0]->getType();
       $this->assertSame(GenericEvent::class, $type->getName());
   }
   ```
   Update `testGetHooksReturnsFourEntries` count assertion to match the new total.
   Update `testClassPublicStaticMethods` expected array to include `'getSuspend'`.

5. **Run tests to verify.**
   ```bash
   vendor/bin/phpunit
   ```
   All existing tests must still pass. The two new tests must pass.

## Examples

**User says:** "Add a hook for quickservers.suspend that marks the service suspended"

**Actions taken:**
1. Add `self::$module.'.suspend' => [__CLASS__, 'getSuspend']` to `getHooks()` array in `src/Plugin.php`.
2. Implement `public static function getSuspend(GenericEvent $event)` after `getQueue()`, calling `get_module_settings`, `myadmin_log`, `get_module_db`, `$db->query(...)`, and `history->add`.
3. Add `testGetHooksContainsSuspend` and `testGetSuspendAcceptsGenericEvent` to `tests/PluginTest.php`; update count assertion from 4 to 5.
4. Run `vendor/bin/phpunit` — all tests green.

**Result:** `Plugin::getHooks()` returns 5 entries; `getSuspend` is a public static method typed `GenericEvent $event`; tests pass.

## Common Issues

- **`testGetHooksReturnsFourEntries` fails with "5 elements"**: You added the hook but forgot to update the count assertion in `PluginTest.php`. Change `assertCount(4, $hooks)` to `assertCount(5, $hooks)`.

- **`testClassPublicStaticMethods` fails with unexpected method name**: Add the new method name (e.g. `'getSuspend'`) to the `$expected` array in that test.

- **`Argument 1 passed to getSuspend() must be an instance of GenericEvent`**: The `use Symfony\Component\EventDispatcher\GenericEvent;` import is already at line 5 of `src/Plugin.php` — do not add a second import. Verify the type hint is `GenericEvent`, not `\GenericEvent`.

- **`Call to undefined function get_module_settings()`** during tests: This is stubbed in `tests/bootstrap.php`. Do not redefine it in test files; the stub returns a value satisfying array access.
