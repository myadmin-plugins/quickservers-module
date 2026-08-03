<?php

/**
 * Test doubles for the MyAdmin framework services Plugin calls into.
 *
 * The plugin's handlers are static and reach straight out to global
 * framework services (\MyAdmin\App::history(), myadmin_log(), ...).
 * These doubles record what they were asked to do, so a test can *invoke*
 * a handler and assert its observable effects instead of grepping
 * src/Plugin.php for the spelling of a call.
 */

namespace Detain\MyAdminQuickservers\Tests\Support {
    /**
     * Records \MyAdmin\History::add() calls made through \MyAdmin\App::history().
     */
    class HistorySpy
    {
        /**
         * Every recorded call, in order.
         *
         * @var array<int,array<string,mixed>>
         */
        public $entries = [];

        /**
         * Mirrors \MyAdmin\History::add()'s signature.
         *
         * @param string $section history section
         * @param string $type history type
         * @param string $new new value
         * @param string $old old value
         * @param bool|int $custid customer id
         * @param bool|string $extra optional extra info
         * @return int the fake history id
         */
        public function add($section, $type, $new, $old = '', $custid = false, $extra = false)
        {
            $this->entries[] = [
                'section' => $section,
                'type' => $type,
                'new' => $new,
                'old' => $old,
                'custid' => $custid,
                'extra' => $extra,
            ];
            return count($this->entries);
        }

        /**
         * Recorded entries for a single history section.
         *
         * @param string $section
         * @return array<int,array<string,mixed>>
         */
        public function entriesForSection($section)
        {
            $matches = array_filter($this->entries, static function (array $entry) use ($section) {
                return $entry['section'] === $section;
            });
            return array_values($matches);
        }
    }

    /**
     * Records myadmin_log() calls.
     */
    class LogSpy
    {
        /**
         * @var array<int,array<string,mixed>>
         */
        private static $calls = [];

        /**
         * @param string $module
         * @param string $level
         * @param string $message
         * @param string $section
         * @param int|string $id
         * @return void
         */
        public static function record($module, $level, $message, $section = '', $id = '')
        {
            self::$calls[] = [
                'module' => $module,
                'level' => $level,
                'message' => $message,
                'section' => $section,
                'id' => $id,
            ];
        }

        /**
         * @return array<int,array<string,mixed>>
         */
        public static function calls()
        {
            return self::$calls;
        }

        /**
         * @return void
         */
        public static function reset()
        {
            self::$calls = [];
        }
    }
}

namespace MyAdmin {
    if (!class_exists(App::class, false)) {
        /**
         * Stand-in for the real \MyAdmin\App facade. Only history() is
         * reached by this plugin, and it hands back a spy the tests own.
         */
        class App
        {
            /**
             * @var \Detain\MyAdminQuickservers\Tests\Support\HistorySpy|null
             */
            private static $history;

            /**
             * @return \Detain\MyAdminQuickservers\Tests\Support\HistorySpy
             */
            public static function history()
            {
                if (self::$history === null) {
                    self::$history = new \Detain\MyAdminQuickservers\Tests\Support\HistorySpy();
                }
                return self::$history;
            }

            /**
             * Installs a fresh spy and returns it. Call from setUp().
             *
             * @return \Detain\MyAdminQuickservers\Tests\Support\HistorySpy
             */
            public static function resetHistory()
            {
                self::$history = new \Detain\MyAdminQuickservers\Tests\Support\HistorySpy();
                return self::$history;
            }
        }
    }
}
