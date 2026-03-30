---
name: phpunit-stub
description: Writes PHPUnit test methods under `tests/PluginTest.php` using stubs already defined in `tests/bootstrap.php`. Use when user says 'write test', 'add test', 'test this method', or adds a new method to `src/Plugin.php`. Covers static-property assertions, hook-mapping checks, and reflection-based source analysis for DB-touching methods. Do NOT use for integration tests, non-Plugin classes, or tests requiring a live database.
---
# PHPUnit Stub (QuickServers Module)

## Critical

- **Never redefine** stubs already in `tests/bootstrap.php`: `myadmin_log`, `get_module_settings`, `get_module_db`, `run_event`, `validIp`, `reverse_dns`, `function_requirements`, `admin_email_qs_pending_setup`, `get_orm_class_from_table`, constants `PRORATE_BILLING`/`ANNIVERSARY_BILLING`.
- All test methods must be in namespace `Detain\MyAdminQuickservers\Tests`, class `PluginTest extends TestCase`.
- All test methods are `public function testXxx(): void` — never omit the `void` return type.
- Tests are append-only to `tests/PluginTest.php` unless you are replacing a broken test.

## Instructions

1. **Identify the method under test** in `src/Plugin.php`. Note whether it: (a) returns a value, (b) reads DB via `get_module_db`, or (c) mutates state via `myadmin_log`/`history->add`.
   - Verify the method exists with `grep -n 'function methodName' src/Plugin.php` before writing the test.

2. **Choose the test category** based on what the method does:
   - Static property → assert value directly: `$this->assertSame('expected', Plugin::$prop);`
   - Return value → call the static method and assert the result.
   - Reflection/source analysis (for DB-touching methods) → extract method body from file and use `assertStringContainsString`.
   - Signature check → use `ReflectionMethod` to assert parameter count, type, and `isPublic()`/`isStatic()`.

3. **Write reflection-based source analysis tests** for methods that call `get_module_db` (the stub returns a fresh anonymous instance per call, so behavior cannot be tested end-to-end):
   ```php
   public function testMyMethodReferencesExpectedPatterns(): void
   {
       $method = $this->reflection->getMethod('myMethod');
       $lines  = file($method->getFileName());
       $body   = implode('', array_slice(
           $lines,
           $method->getStartLine() - 1,
           $method->getEndLine() - $method->getStartLine() + 1
       ));
       $this->assertStringContainsString('get_module_db', $body);
       $this->assertStringContainsString('PREFIX', $body);
       $this->assertStringContainsString('TABLE', $body);
   }
   ```
   Verify the method name exists on `$this->reflection` before asserting body contents.

4. **Inject mock DB rows** only when testing a method whose logic branches on `num_rows()` > 0 and the method is callable in isolation. Capture the stub object via a wrapper in test setup (not by redefining `get_module_db`):
   ```php
   // In test: call the method, then assert logged side-effects or return value.
   // The stub's $rows defaults to [] so num_rows() returns 0 — test the zero-row branch.
   Plugin::myMethod($event); // exercises the empty-result path
   $this->assertTrue(true);  // or assert event state
   ```

5. **Add the test method** inside the appropriate `// === section ===` block in `tests/PluginTest.php`. Match the existing section grouping: `Class structure`, `Static property`, `Settings array`, `getHooks()`, `Event handler signature`, `Static analysis: DB-touching methods`, `Hook-to-method mapping consistency`.

6. **Run the tests** to verify no regressions:
   ```bash
   vendor/bin/phpunit
   ```
   All prior tests must still pass.

## Examples

**User says:** "Add a test that `getDeactivate` calls `myadmin_log`"

**Actions taken:**
1. Confirmed `getDeactivate` exists via `grep -n 'function getDeactivate' src/Plugin.php`.
2. Checked existing `testGetDeactivateReferencesLoggingAndHistory` — already covers `myadmin_log`. Noted this and extended coverage instead.
3. Added source-analysis test inside the `// Static analysis: DB-touching methods` section:

```php
/**
 * Verify getDeactivate references the service PREFIX for status update.
 *
 * @return void
 */
public function testGetDeactivateReferencesServicePrefix(): void
{
    $method = $this->reflection->getMethod('getDeactivate');
    $lines  = file($method->getFileName());
    $body   = implode('', array_slice(
        $lines,
        $method->getStartLine() - 1,
        $method->getEndLine() - $method->getStartLine() + 1
    ));
    $this->assertStringContainsString("PREFIX.'_status'", $body);
}
```

4. Ran `vendor/bin/phpunit` — all tests green.

## Common Issues

- **"Cannot redeclare function get_module_db"**: You added a stub in the test file. Remove it — it is already in `tests/bootstrap.php`.
- **"Call to undefined method ... getType()"** on a `ReflectionParameter`: PHP < 7.4 — check `$param->getType() !== null` before calling `getName()` on the type.
- **"No tests executed"**: Method name does not start with `test`. Rename `checkFoo` → `testCheckFoo`.
- **Reflection body test fails unexpectedly**: `getStartLine()`/`getEndLine()` include the function signature line. If the assertion targets a string only in the body, it is still found — but verify line math with `var_dump(array_slice(...))` in a debug run.
- **`assertMatchesRegularExpression` not found**: Requires PHPUnit ≥ 9. For older versions use `assertRegExp` (deprecated). Check `vendor/bin/phpunit --version`.