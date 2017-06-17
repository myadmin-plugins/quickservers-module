<?php

namespace Detain\MyAdminQuickservers;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'QuickServers Module';
	public static $description = 'Allows selling of QuickServers Module';
	public static $help = '';
	public static $module = 'quickservers';
	public static $type = 'module';


	public function __construct() {
	}

	public static function Hooks() {
		return [
			'quickservers.load_processing' => [__CLASS__, 'Load'],
			'quickservers.settings' => [__CLASS__, 'Settings'],
		];
	}

	public static function Load(GenericEvent $event) {

	}

	public static function Settings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_dropdown_setting('quickservers', 'General', 'outofstock_quickservers', 'Out Of Stock Quickservers', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_QUICKSERVERS'), array('0', '1'), array('No', 'Yes',));
	}
}
