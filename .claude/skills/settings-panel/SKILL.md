---
name: settings-panel
description: Adds admin settings fields inside Plugin::getSettings() using add_dropdown_setting or add_master_text_setting with correct setTarget('module')/setTarget('global') framing. Use when user says 'add setting', 'new admin option', 'settings field', 'out of stock toggle', or 'cost field'. Do NOT use for non-settings plugin work like hooks, queue actions, or DB migrations.
---
# settings-panel

Adds admin settings fields to `Plugin::getSettings()` in `src/Plugin.php`.

## Critical

- Always open with `$settings->setTarget('module')` and close with `$settings->setTarget('global')` — omitting either corrupts settings scope for other modules.
- Never use PDO. Never touch `$_GET`/`$_POST` directly inside `getSettings()`.
- `$settings` here is the `\MyAdmin\Settings` object from `$event->getSubject()` — not `get_module_settings()`.
- `get_setting()` key must be UPPERCASE (e.g. `OUTOFSTOCK_QUICKSERVERS`), matching the stored setting name.

## Instructions

1. **Open `src/Plugin.php`** and locate `getSettings(GenericEvent $event)`. Verify the method exists and currently ends with `$settings->setTarget('global')`.

2. **Add a dropdown setting** (on/off toggles, enum choices) before `setTarget('global')`:
   ```php
   $settings->add_dropdown_setting(
       self::$module,                          // module: 'quickservers'
       _('General'),                           // section label (gettext-wrapped)
       'outofstock_quickservers',              // setting key (lowercase_snake)
       _('Out Of Stock Quickservers'),         // field label
       _('Enable/Disable Sales Of This Type'), // description
       $settings->get_setting('OUTOFSTOCK_QUICKSERVERS'), // current value — UPPERCASE key
       ['0', '1'],                             // option values
       ['No', 'Yes']                           // option labels
   );
   ```
   Verify: option values array and labels array have equal length.

3. **Add a master text setting** (free-text fields like cost, hostname) before `setTarget('global')`:
   ```php
   $settings->add_master_text_setting(
       self::$module,      // module
       'Server Settings',  // section label (NOT gettext-wrapped in existing code)
       self::$module,      // group (typically same as module)
       'cost',             // field suffix
       'qs_cost',          // DB column / setting key
       'Server Cost',      // label
       '<p>The price to list the server at.</p>' // HTML help text
   );
   ```
   Verify: the `qs_` prefix matches `$settings['PREFIX']` (`'qs'`).

4. **Final structure must look like:**
   ```php
   public static function getSettings(GenericEvent $event)
   {
       /** @var \MyAdmin\Settings $settings **/
       $settings = $event->getSubject();
       $settings->setTarget('module');
       // ... add_dropdown_setting / add_master_text_setting calls ...
       $settings->setTarget('global');
   }
   ```

5. **Run tests** to confirm nothing is broken:
   ```bash
   vendor/bin/phpunit
   ```

## Examples

**User says:** "Add a maintenance mode toggle and a max-servers limit field to quickservers settings."

**Actions taken:**
```php
$settings->setTarget('module');
$settings->add_dropdown_setting(
    self::$module,
    _('General'),
    'maintenance_quickservers',
    _('Maintenance Mode'),
    _('Disable all new quickserver provisioning'),
    $settings->get_setting('MAINTENANCE_QUICKSERVERS'),
    ['0', '1'],
    ['Off', 'On']
);
$settings->add_master_text_setting(
    self::$module,
    'Server Settings',
    self::$module,
    'max_servers',
    'qs_max_servers',
    'Max Servers',
    '<p>Maximum number of quickservers allowed.</p>'
);
$settings->setTarget('global');
```

**Result:** Two new fields appear in the admin settings panel under the quickservers module — a Yes/No dropdown and a text input.

## Common Issues

- **Settings from other modules appear broken after your change:** You forgot `$settings->setTarget('global')` at the end. Add it as the last line before the closing brace.
- **`get_setting()` returns null/empty:** The key passed to `get_setting()` must be UPPERCASE (e.g. `'OUTOFSTOCK_QUICKSERVERS'`), not the lowercase snake-case key used in `add_dropdown_setting()`.
- **PHPUnit fails with "Call to undefined method":** The `\MyAdmin\Settings` stub in `tests/bootstrap.php` may not include the new method. Add a stub entry if testing settings-specific behavior, or keep the test focused on hook registration only.
- **Section label shows raw string instead of translated text:** Wrap labels in `_('...')` for gettext. The second argument to `add_master_text_setting` is intentionally NOT wrapped in existing code — match the existing style for that argument.