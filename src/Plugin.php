<?php

namespace Detain\MyAdminQuickservers;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'QuickServers Module';
	public static $description = 'Allows selling of QuickServers Module';
	public static $help = '';
	public static $module = 'quickservers';
	public static $type = 'module';
	public static $settings = [
		'SERVICE_ID_OFFSET' => 0,
		'USE_REPEAT_INVOICE' => TRUE,
		'USE_PACKAGES' => FALSE,
		'BILLING_DAYS_OFFSET' => 0,
		'IMGNAME' => 'server_add_48.png',
		'REPEAT_BILLING_METHOD' => PRORATE_BILLING,
		'DELETE_PENDING_DAYS' => 30,
		'SUSPEND_DAYS' => 14,
		'SUSPEND_WARNING_DAYS' => 7,
		'TITLE' => 'QuickServers',
		'MENUNAME' => 'QuickServers',
		'EMAIL_FROM' => 'support@interserver.net',
		'TBLNAME' => 'QuickServers',
		'TABLE' => 'quickservers',
		'TITLE_FIELD' => 'qs_hostname',
		'TITLE_FIELD2' => 'qs_ip',
		'PREFIX' => 'qs'];


	public function __construct() {
	}

	public static function getHooks() {
		return [
			self::$module.'.load_processing' => [__CLASS__, 'loadProcessing'],
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function loadProcessing(GenericEvent $event) {

	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_dropdown_setting(self::$module, 'General', 'outofstock_quickservers', 'Out Of Stock Quickservers', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_QUICKSERVERS'), array('0', '1'), array('No', 'Yes',));
	}
}
