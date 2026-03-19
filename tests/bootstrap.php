<?php
/**
 * PHPUnit Bootstrap for myadmin-quickservers-module
 *
 * Provides the minimal environment needed to test the Plugin class
 * without requiring the full MyAdmin application stack.
 */

// Define billing constants used in Plugin::$settings
if (!defined('PRORATE_BILLING')) {
    define('PRORATE_BILLING', 1);
}
if (!defined('ANNIVERSARY_BILLING')) {
    define('ANNIVERSARY_BILLING', 2);
}

// Stub global functions referenced by Plugin methods
if (!function_exists('myadmin_log')) {
    function myadmin_log(string $module, string $level, string $message, $line = '', $file = '', string $section = '', $id = ''): void
    {
    }
}

if (!function_exists('get_module_settings')) {
    function get_module_settings(string $module): array
    {
        return [
            'SERVICE_ID_OFFSET' => 0,
            'USE_REPEAT_INVOICE' => true,
            'USE_PACKAGES' => false,
            'BILLING_DAYS_OFFSET' => 0,
            'IMGNAME' => 'server.png',
            'REPEAT_BILLING_METHOD' => PRORATE_BILLING,
            'DELETE_PENDING_DAYS' => 30,
            'SUSPEND_DAYS' => 14,
            'SUSPEND_WARNING_DAYS' => 7,
            'TITLE' => 'Rapid Deploy Servers',
            'MENUNAME' => 'Rapid Deploy Servers',
            'EMAIL_FROM' => 'support@interserver.net',
            'TBLNAME' => 'Rapid Deploy Servers',
            'TABLE' => 'quickservers',
            'TITLE_FIELD' => 'qs_hostname',
            'TITLE_FIELD2' => 'qs_ip',
            'PREFIX' => 'qs',
        ];
    }
}

if (!function_exists('get_module_db')) {
    function get_module_db(string $module): object
    {
        return new class {
            /** @var array<int,array<string,mixed>> */
            public array $rows = [];
            private int $cursor = -1;
            /** @var array<string,mixed> */
            public array $Record = [];

            public function query(string $sql = '', $line = '', $file = ''): void
            {
            }

            public function next_record($type = null): bool
            {
                $this->cursor++;
                if (isset($this->rows[$this->cursor])) {
                    $this->Record = $this->rows[$this->cursor];
                    return true;
                }
                return false;
            }

            public function num_rows(): int
            {
                return count($this->rows);
            }

            /** @param string $n */
            public function f($n)
            {
                return $this->Record[$n] ?? 0;
            }
        };
    }
}

if (!function_exists('run_event')) {
    function run_event(string $event, $default = false, string $module = '')
    {
        return $default;
    }
}

if (!function_exists('get_service_define')) {
    function get_service_define(string $name): int
    {
        $defines = [
            'KVM_LINUX' => 2,
            'KVM_WINDOWS' => 3,
            'CLOUD_KVM_LINUX' => 10,
            'CLOUD_KVM_WINDOWS' => 12,
        ];
        return $defines[$name] ?? 0;
    }
}

if (!function_exists('get_orm_class_from_table')) {
    function get_orm_class_from_table(string $table): string
    {
        return ucfirst($table);
    }
}

if (!function_exists('function_requirements')) {
    function function_requirements(string $func): void
    {
    }
}

if (!function_exists('validIp')) {
    function validIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}

if (!function_exists('reverse_dns')) {
    function reverse_dns(string $ip, string $hostname = '', string $action = ''): void
    {
    }
}

if (!function_exists('admin_email_qs_pending_setup')) {
    function admin_email_qs_pending_setup($id): void
    {
    }
}

// Autoload the module source
$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadFile)) {
    require_once $autoloadFile;
} else {
    // Fallback: register a simple PSR-4 autoloader for the module namespace
    spl_autoload_register(function (string $class): void {
        $prefix = 'Detain\\MyAdminQuickservers\\';
        if (strpos($class, $prefix) === 0) {
            $relativeClass = substr($class, strlen($prefix));
            $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}
