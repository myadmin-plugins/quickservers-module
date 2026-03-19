<?php
/**
 * Unit tests for \Detain\MyAdminQuickservers\Plugin
 *
 * Tests class structure, static properties, hook registration,
 * event handler signatures, and settings configuration without
 * requiring a database or full application bootstrap.
 *
 * @package Tests
 */

namespace Detain\MyAdminQuickservers\Tests;

use Detain\MyAdminQuickservers\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @coversDefaultClass \Detain\MyAdminQuickservers\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    /**
     * Set up the reflection instance for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    // ========================================================================
    // Class structure tests
    // ========================================================================

    /**
     * Verify the Plugin class exists and can be loaded.
     *
     * @return void
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Verify the Plugin class is in the correct namespace.
     *
     * @return void
     */
    public function testClassNamespace(): void
    {
        $this->assertSame(
            'Detain\\MyAdminQuickservers',
            $this->reflection->getNamespaceName()
        );
    }

    /**
     * Verify the Plugin class can be instantiated.
     *
     * @return void
     */
    public function testClassIsInstantiable(): void
    {
        $this->assertTrue($this->reflection->isInstantiable());
    }

    /**
     * Verify the constructor is public and takes no required parameters.
     *
     * @return void
     */
    public function testConstructorIsPublicAndParameterless(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());
        $this->assertSame(0, $constructor->getNumberOfRequiredParameters());
    }

    /**
     * Verify the Plugin can be instantiated without errors.
     *
     * @return void
     */
    public function testConstructorDoesNotThrow(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    // ========================================================================
    // Static property tests
    // ========================================================================

    /**
     * Verify the $name static property holds the expected value.
     *
     * @return void
     */
    public function testNameProperty(): void
    {
        $this->assertSame('Rapid Deploy Servers', Plugin::$name);
    }

    /**
     * Verify the $description static property is a non-empty string.
     *
     * @return void
     */
    public function testDescriptionPropertyIsNonEmptyString(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
    }

    /**
     * Verify the $help static property is a string.
     *
     * @return void
     */
    public function testHelpPropertyIsString(): void
    {
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Verify the $module static property holds the expected value.
     *
     * @return void
     */
    public function testModuleProperty(): void
    {
        $this->assertSame('quickservers', Plugin::$module);
    }

    /**
     * Verify the $type static property holds the expected value.
     *
     * @return void
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('module', Plugin::$type);
    }

    /**
     * Verify that all expected static properties exist on the class.
     *
     * @return void
     */
    public function testAllStaticPropertiesExist(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type', 'settings'];
        foreach ($expected as $property) {
            $this->assertTrue(
                $this->reflection->hasProperty($property),
                "Missing static property: \${$property}"
            );
            $this->assertTrue(
                $this->reflection->getProperty($property)->isStatic(),
                "\${$property} should be static"
            );
            $this->assertTrue(
                $this->reflection->getProperty($property)->isPublic(),
                "\${$property} should be public"
            );
        }
    }

    // ========================================================================
    // Settings array tests
    // ========================================================================

    /**
     * Verify the $settings property is an array.
     *
     * @return void
     */
    public function testSettingsIsArray(): void
    {
        $this->assertIsArray(Plugin::$settings);
    }

    /**
     * Verify all required keys are present in $settings.
     *
     * @return void
     */
    public function testSettingsHasAllRequiredKeys(): void
    {
        $requiredKeys = [
            'SERVICE_ID_OFFSET',
            'USE_REPEAT_INVOICE',
            'USE_PACKAGES',
            'BILLING_DAYS_OFFSET',
            'IMGNAME',
            'REPEAT_BILLING_METHOD',
            'DELETE_PENDING_DAYS',
            'SUSPEND_DAYS',
            'SUSPEND_WARNING_DAYS',
            'TITLE',
            'MENUNAME',
            'EMAIL_FROM',
            'TBLNAME',
            'TABLE',
            'TITLE_FIELD',
            'TITLE_FIELD2',
            'PREFIX',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                Plugin::$settings,
                "Settings missing required key: {$key}"
            );
        }
    }

    /**
     * Verify SERVICE_ID_OFFSET is an integer.
     *
     * @return void
     */
    public function testSettingsServiceIdOffsetIsInt(): void
    {
        $this->assertIsInt(Plugin::$settings['SERVICE_ID_OFFSET']);
    }

    /**
     * Verify USE_REPEAT_INVOICE is a boolean.
     *
     * @return void
     */
    public function testSettingsUseRepeatInvoiceIsBool(): void
    {
        $this->assertIsBool(Plugin::$settings['USE_REPEAT_INVOICE']);
        $this->assertTrue(Plugin::$settings['USE_REPEAT_INVOICE']);
    }

    /**
     * Verify USE_PACKAGES is a boolean.
     *
     * @return void
     */
    public function testSettingsUsePackagesIsBool(): void
    {
        $this->assertIsBool(Plugin::$settings['USE_PACKAGES']);
        $this->assertFalse(Plugin::$settings['USE_PACKAGES']);
    }

    /**
     * Verify BILLING_DAYS_OFFSET is an integer.
     *
     * @return void
     */
    public function testSettingsBillingDaysOffsetIsInt(): void
    {
        $this->assertIsInt(Plugin::$settings['BILLING_DAYS_OFFSET']);
    }

    /**
     * Verify IMGNAME is a non-empty string ending in a known extension.
     *
     * @return void
     */
    public function testSettingsImgNameIsValidFilename(): void
    {
        $this->assertIsString(Plugin::$settings['IMGNAME']);
        $this->assertMatchesRegularExpression(
            '/\.(png|jpg|gif|svg|webp)$/i',
            Plugin::$settings['IMGNAME']
        );
    }

    /**
     * Verify REPEAT_BILLING_METHOD uses the PRORATE_BILLING constant.
     *
     * @return void
     */
    public function testSettingsRepeatBillingMethodIsProrate(): void
    {
        $this->assertSame(PRORATE_BILLING, Plugin::$settings['REPEAT_BILLING_METHOD']);
    }

    /**
     * Verify DELETE_PENDING_DAYS is a positive integer.
     *
     * @return void
     */
    public function testSettingsDeletePendingDaysIsPositive(): void
    {
        $this->assertIsInt(Plugin::$settings['DELETE_PENDING_DAYS']);
        $this->assertGreaterThan(0, Plugin::$settings['DELETE_PENDING_DAYS']);
    }

    /**
     * Verify SUSPEND_DAYS is a positive integer.
     *
     * @return void
     */
    public function testSettingsSuspendDaysIsPositive(): void
    {
        $this->assertIsInt(Plugin::$settings['SUSPEND_DAYS']);
        $this->assertGreaterThan(0, Plugin::$settings['SUSPEND_DAYS']);
    }

    /**
     * Verify SUSPEND_WARNING_DAYS is less than SUSPEND_DAYS.
     *
     * @return void
     */
    public function testSettingsSuspendWarningDaysLessThanSuspendDays(): void
    {
        $this->assertLessThan(
            Plugin::$settings['SUSPEND_DAYS'],
            Plugin::$settings['SUSPEND_WARNING_DAYS']
        );
    }

    /**
     * Verify TITLE matches the expected value.
     *
     * @return void
     */
    public function testSettingsTitleMatchesName(): void
    {
        $this->assertSame('Rapid Deploy Servers', Plugin::$settings['TITLE']);
    }

    /**
     * Verify MENUNAME matches the expected value.
     *
     * @return void
     */
    public function testSettingsMenuNameMatchesTitle(): void
    {
        $this->assertSame(Plugin::$settings['TITLE'], Plugin::$settings['MENUNAME']);
    }

    /**
     * Verify EMAIL_FROM is a valid email address.
     *
     * @return void
     */
    public function testSettingsEmailFromIsValidEmail(): void
    {
        $this->assertNotFalse(
            filter_var(Plugin::$settings['EMAIL_FROM'], FILTER_VALIDATE_EMAIL),
            'EMAIL_FROM should be a valid email address'
        );
    }

    /**
     * Verify TABLE is the expected table name.
     *
     * @return void
     */
    public function testSettingsTableValue(): void
    {
        $this->assertSame('quickservers', Plugin::$settings['TABLE']);
    }

    /**
     * Verify PREFIX is the expected prefix.
     *
     * @return void
     */
    public function testSettingsPrefixValue(): void
    {
        $this->assertSame('qs', Plugin::$settings['PREFIX']);
    }

    /**
     * Verify TITLE_FIELD starts with the prefix.
     *
     * @return void
     */
    public function testSettingsTitleFieldStartsWithPrefix(): void
    {
        $this->assertStringStartsWith(
            Plugin::$settings['PREFIX'] . '_',
            Plugin::$settings['TITLE_FIELD']
        );
    }

    /**
     * Verify TITLE_FIELD2 starts with the prefix.
     *
     * @return void
     */
    public function testSettingsTitleField2StartsWithPrefix(): void
    {
        $this->assertStringStartsWith(
            Plugin::$settings['PREFIX'] . '_',
            Plugin::$settings['TITLE_FIELD2']
        );
    }

    /**
     * Verify TITLE_FIELD is 'qs_hostname'.
     *
     * @return void
     */
    public function testSettingsTitleFieldValue(): void
    {
        $this->assertSame('qs_hostname', Plugin::$settings['TITLE_FIELD']);
    }

    /**
     * Verify TITLE_FIELD2 is 'qs_ip'.
     *
     * @return void
     */
    public function testSettingsTitleField2Value(): void
    {
        $this->assertSame('qs_ip', Plugin::$settings['TITLE_FIELD2']);
    }

    // ========================================================================
    // getHooks() tests
    // ========================================================================

    /**
     * Verify getHooks() returns an array.
     *
     * @return void
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Verify getHooks() returns exactly four hooks.
     *
     * @return void
     */
    public function testGetHooksReturnsFourEntries(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(4, $hooks);
    }

    /**
     * Verify all hook keys are prefixed with the module name.
     *
     * @return void
     */
    public function testGetHooksKeysArePrefixedWithModule(): void
    {
        $hooks = Plugin::getHooks();
        foreach (array_keys($hooks) as $key) {
            $this->assertStringStartsWith(
                Plugin::$module . '.',
                $key,
                "Hook key '{$key}' should start with module prefix"
            );
        }
    }

    /**
     * Verify the load_processing hook is registered.
     *
     * @return void
     */
    public function testGetHooksContainsLoadProcessing(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('quickservers.load_processing', $hooks);
    }

    /**
     * Verify the settings hook is registered.
     *
     * @return void
     */
    public function testGetHooksContainsSettings(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('quickservers.settings', $hooks);
    }

    /**
     * Verify the deactivate hook is registered.
     *
     * @return void
     */
    public function testGetHooksContainsDeactivate(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('quickservers.deactivate', $hooks);
    }

    /**
     * Verify the queue hook is registered.
     *
     * @return void
     */
    public function testGetHooksContainsQueue(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('quickservers.queue', $hooks);
    }

    /**
     * Verify all hook values are valid callable arrays with [class, method].
     *
     * @return void
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $handler) {
            $this->assertIsArray($handler, "Handler for '{$key}' should be an array");
            $this->assertCount(2, $handler, "Handler for '{$key}' should have exactly 2 elements");
            $this->assertSame(
                Plugin::class,
                $handler[0],
                "Handler class for '{$key}' should be Plugin"
            );
            $this->assertIsString($handler[1], "Handler method for '{$key}' should be a string");
        }
    }

    /**
     * Verify all hook handler methods exist on the Plugin class.
     *
     * @return void
     */
    public function testGetHooksMethodsExistOnClass(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $handler) {
            $this->assertTrue(
                method_exists(Plugin::class, $handler[1]),
                "Method {$handler[1]} referenced by hook '{$key}' does not exist"
            );
        }
    }

    /**
     * Verify all hook handler methods are public static.
     *
     * @return void
     */
    public function testGetHooksMethodsArePublicStatic(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $handler) {
            $method = $this->reflection->getMethod($handler[1]);
            $this->assertTrue(
                $method->isPublic(),
                "Method {$handler[1]} should be public"
            );
            $this->assertTrue(
                $method->isStatic(),
                "Method {$handler[1]} should be static"
            );
        }
    }

    // ========================================================================
    // Event handler signature tests
    // ========================================================================

    /**
     * Verify getDeactivate accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetDeactivateAcceptsGenericEvent(): void
    {
        $method = $this->reflection->getMethod('getDeactivate');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify loadProcessing accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testLoadProcessingAcceptsGenericEvent(): void
    {
        $method = $this->reflection->getMethod('loadProcessing');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify getSettings accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetSettingsAcceptsGenericEvent(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify getQueue accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetQueueAcceptsGenericEvent(): void
    {
        $method = $this->reflection->getMethod('getQueue');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify all event handler methods have exactly one required parameter.
     *
     * @return void
     */
    public function testAllEventHandlersHaveOneRequiredParameter(): void
    {
        $handlers = ['getDeactivate', 'loadProcessing', 'getSettings', 'getQueue'];
        foreach ($handlers as $handlerName) {
            $method = $this->reflection->getMethod($handlerName);
            $this->assertSame(
                1,
                $method->getNumberOfRequiredParameters(),
                "{$handlerName} should require exactly 1 parameter"
            );
        }
    }

    /**
     * Verify all event handler methods return void (no explicit return type
     * in the source, but they do not return values).
     *
     * @return void
     */
    public function testEventHandlerReturnTypes(): void
    {
        $handlers = ['getDeactivate', 'loadProcessing', 'getSettings', 'getQueue'];
        foreach ($handlers as $handlerName) {
            $method = $this->reflection->getMethod($handlerName);
            // These methods have no declared return type in the source
            $returnType = $method->getReturnType();
            // Accept either null (no return type) or void
            if ($returnType !== null) {
                $this->assertSame('void', $returnType->getName());
            } else {
                $this->assertNull($returnType);
            }
        }
    }

    // ========================================================================
    // getHooks() return type test
    // ========================================================================

    /**
     * Verify getHooks has a documented @return array annotation.
     *
     * @return void
     */
    public function testGetHooksHasReturnDocblock(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $docComment = $method->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@return', $docComment);
        $this->assertStringContainsString('array', $docComment);
    }

    /**
     * Verify getHooks is a public static method.
     *
     * @return void
     */
    public function testGetHooksIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Verify getHooks takes no parameters.
     *
     * @return void
     */
    public function testGetHooksHasNoParameters(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertSame(0, $method->getNumberOfParameters());
    }

    // ========================================================================
    // Static analysis: DB-touching methods reference expected patterns
    // ========================================================================

    /**
     * Verify loadProcessing method body references the settings PREFIX and TABLE.
     *
     * @return void
     */
    public function testLoadProcessingReferencesSettingsKeys(): void
    {
        $method = $this->reflection->getMethod('loadProcessing');
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $filename = $method->getFileName();

        $lines = file($filename);
        $body = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('PREFIX', $body);
        $this->assertStringContainsString('TABLE', $body);
        $this->assertStringContainsString('get_module_settings', $body);
        $this->assertStringContainsString('get_module_db', $body);
    }

    /**
     * Verify getDeactivate references myadmin_log and history->add.
     *
     * @return void
     */
    public function testGetDeactivateReferencesLoggingAndHistory(): void
    {
        $method = $this->reflection->getMethod('getDeactivate');
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $filename = $method->getFileName();

        $lines = file($filename);
        $body = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('myadmin_log', $body);
        $this->assertStringContainsString('history->add', $body);
    }

    /**
     * Verify getQueue references file_exists for template checking.
     *
     * @return void
     */
    public function testGetQueueChecksTemplateFileExists(): void
    {
        $method = $this->reflection->getMethod('getQueue');
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $filename = $method->getFileName();

        $lines = file($filename);
        $body = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('file_exists', $body);
        $this->assertStringContainsString('.sh.tpl', $body);
    }

    /**
     * Verify getQueue calls stopPropagation on the event.
     *
     * @return void
     */
    public function testGetQueueStopsPropagation(): void
    {
        $method = $this->reflection->getMethod('getQueue');
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $filename = $method->getFileName();

        $lines = file($filename);
        $body = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('stopPropagation', $body);
    }

    /**
     * Verify the terminate closure in loadProcessing references reverse_dns.
     *
     * @return void
     */
    public function testLoadProcessingTerminateReferencesReverseDns(): void
    {
        $method = $this->reflection->getMethod('loadProcessing');
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $filename = $method->getFileName();

        $lines = file($filename);
        $body = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('reverse_dns', $body);
        $this->assertStringContainsString('validIp', $body);
    }

    /**
     * Verify loadProcessing registers enable, reactivate, disable, and terminate handlers.
     *
     * @return void
     */
    public function testLoadProcessingRegistersAllServiceHandlers(): void
    {
        $method = $this->reflection->getMethod('loadProcessing');
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $filename = $method->getFileName();

        $lines = file($filename);
        $body = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('setModule', $body);
        $this->assertStringContainsString('setEnable', $body);
        $this->assertStringContainsString('setReactivate', $body);
        $this->assertStringContainsString('setDisable', $body);
        $this->assertStringContainsString('setTerminate', $body);
        $this->assertStringContainsString('register()', $body);
    }

    /**
     * Verify getSettings references setTarget and add_dropdown_setting.
     *
     * @return void
     */
    public function testGetSettingsReferencesSettingsMethods(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $filename = $method->getFileName();

        $lines = file($filename);
        $body = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('setTarget', $body);
        $this->assertStringContainsString('add_dropdown_setting', $body);
        $this->assertStringContainsString('add_master_text_setting', $body);
    }

    // ========================================================================
    // Hook-to-method mapping consistency
    // ========================================================================

    /**
     * Verify the load_processing hook maps to loadProcessing method.
     *
     * @return void
     */
    public function testLoadProcessingHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('loadProcessing', $hooks['quickservers.load_processing'][1]);
    }

    /**
     * Verify the settings hook maps to getSettings method.
     *
     * @return void
     */
    public function testSettingsHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getSettings', $hooks['quickservers.settings'][1]);
    }

    /**
     * Verify the deactivate hook maps to getDeactivate method.
     *
     * @return void
     */
    public function testDeactivateHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getDeactivate', $hooks['quickservers.deactivate'][1]);
    }

    /**
     * Verify the queue hook maps to getQueue method.
     *
     * @return void
     */
    public function testQueueHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getQueue', $hooks['quickservers.queue'][1]);
    }

    // ========================================================================
    // File existence tests
    // ========================================================================

    /**
     * Verify the Plugin.php source file exists.
     *
     * @return void
     */
    public function testPluginSourceFileExists(): void
    {
        $this->assertFileExists(
            dirname(__DIR__) . '/src/Plugin.php'
        );
    }

    /**
     * Verify composer.json exists at package root.
     *
     * @return void
     */
    public function testComposerJsonExists(): void
    {
        $this->assertFileExists(
            dirname(__DIR__) . '/composer.json'
        );
    }

    // ========================================================================
    // Settings consistency and edge-case tests
    // ========================================================================

    /**
     * Verify that the module name used in settings TABLE matches $module.
     *
     * @return void
     */
    public function testSettingsTableMatchesModule(): void
    {
        $this->assertSame(Plugin::$module, Plugin::$settings['TABLE']);
    }

    /**
     * Verify no unexpected settings keys are present.
     *
     * @return void
     */
    public function testSettingsHasNoUnexpectedKeys(): void
    {
        $expectedKeys = [
            'SERVICE_ID_OFFSET',
            'USE_REPEAT_INVOICE',
            'USE_PACKAGES',
            'BILLING_DAYS_OFFSET',
            'IMGNAME',
            'REPEAT_BILLING_METHOD',
            'DELETE_PENDING_DAYS',
            'SUSPEND_DAYS',
            'SUSPEND_WARNING_DAYS',
            'TITLE',
            'MENUNAME',
            'EMAIL_FROM',
            'TBLNAME',
            'TABLE',
            'TITLE_FIELD',
            'TITLE_FIELD2',
            'PREFIX',
        ];

        $actualKeys = array_keys(Plugin::$settings);
        sort($expectedKeys);
        sort($actualKeys);
        $this->assertSame($expectedKeys, $actualKeys);
    }

    /**
     * Verify TBLNAME is a non-empty string.
     *
     * @return void
     */
    public function testSettingsTblNameIsNonEmpty(): void
    {
        $this->assertIsString(Plugin::$settings['TBLNAME']);
        $this->assertNotEmpty(Plugin::$settings['TBLNAME']);
    }

    /**
     * Verify all string settings values are non-null.
     *
     * @return void
     */
    public function testSettingsStringValuesAreNonNull(): void
    {
        $stringKeys = [
            'IMGNAME', 'TITLE', 'MENUNAME', 'EMAIL_FROM',
            'TBLNAME', 'TABLE', 'TITLE_FIELD', 'TITLE_FIELD2', 'PREFIX',
        ];

        foreach ($stringKeys as $key) {
            $this->assertNotNull(
                Plugin::$settings[$key],
                "Settings key '{$key}' should not be null"
            );
            $this->assertIsString(
                Plugin::$settings[$key],
                "Settings key '{$key}' should be a string"
            );
        }
    }

    // ========================================================================
    // Module consistency tests
    // ========================================================================

    /**
     * Verify that getHooks uses the $module property for key prefixes.
     *
     * @return void
     */
    public function testHookKeysUseModuleProperty(): void
    {
        $hooks = Plugin::getHooks();
        $module = Plugin::$module;

        foreach (array_keys($hooks) as $hookName) {
            $parts = explode('.', $hookName, 2);
            $this->assertSame(
                $module,
                $parts[0],
                "Hook '{$hookName}' prefix should match \$module"
            );
        }
    }

    /**
     * Verify all hook handler arrays reference the Plugin class via __CLASS__.
     *
     * @return void
     */
    public function testAllHookHandlersReferencePluginClass(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $handler) {
            $this->assertSame(
                Plugin::class,
                $handler[0],
                "Hook '{$key}' should reference Plugin class"
            );
        }
    }

    /**
     * Verify the class has exactly the expected public static methods
     * (excluding the constructor which is public but not static).
     *
     * @return void
     */
    public function testClassPublicStaticMethods(): void
    {
        $expected = ['getHooks', 'getDeactivate', 'loadProcessing', 'getSettings', 'getQueue'];
        $methods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);

        $methodNames = array_filter(
            array_map(
                fn(ReflectionMethod $m) => $m->getName(),
                $methods
            ),
            fn(string $name) => $name !== '__construct'
        );
        $methodNames = array_values($methodNames);

        sort($expected);
        sort($methodNames);
        $this->assertSame($expected, $methodNames);
    }

    /**
     * Verify getHooks is idempotent (returns same result on multiple calls).
     *
     * @return void
     */
    public function testGetHooksIsIdempotent(): void
    {
        $first = Plugin::getHooks();
        $second = Plugin::getHooks();
        $this->assertSame($first, $second);
    }
}
