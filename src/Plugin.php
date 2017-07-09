<?php

namespace Detain\MyAdminQuickservers;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'QuickServers';
	public static $description = 'Allows selling of Servers that create a VPS using 100% of the resources to allow to manage the server in ways you can only with a VPS but while having a full servers worth of hardware at your disposal.';
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
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
		];
	}

	public static function getDeactivate(GenericEvent $event) {
		myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__);
		$serviceClass = $event->getSubject();
		$GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
	}

	public static function loadProcessing(GenericEvent $event) {
		$service = $event->getSubject();
		$service->setModule(self::$module)
			->set_enable(function($service) {
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$db = get_module_db(self::$module);
				$db->query("update ".$settings['TABLE']." set ".$settings['PREFIX']."_status='pending-setup' where ".$settings['PREFIX']."_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($settings['PREFIX'], 'change_status', 'pending-setup', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
				$GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'initial_install', '', $serviceInfo[$settings['PREFIX'].'_custid']);
				admin_email_qs_pending_setup($serviceInfo[$settings['PREFIX'].'_id']);
			})->set_reactivate(function($service) {
				$serviceTypes = run_event('get_service_types', FALSE, self::$module);
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$db = get_module_db(self::$module);
				if ($serviceInfo[$settings['PREFIX'].'_server_status'] === 'deleted' || $serviceInfo[$settings['PREFIX'].'_ip'] == '') {
					$GLOBALS['tf']->history->add($settings['PREFIX'], 'change_status', 'pending-setup', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
					$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='pending-setup' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
					$GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'initial_install', '', $serviceInfo[$settings['PREFIX'].'_custid']);
				} else {
					$GLOBALS['tf']->history->add($settings['PREFIX'], 'change_status', 'active', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
					$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
					$GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'enable', '', $serviceInfo[$settings['PREFIX'].'_custid']);
					$GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'start', '', $serviceInfo[$settings['PREFIX'].'_custid']);
				}
				$smarty = new \TFSmarty;
				$smarty->assign('qs_name', $serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name']);
				$email = $smarty->fetch('email/admin_email_qs_reactivated.tpl');
				$subject = $db->Record[$settings['TITLE_FIELD']].' '.$serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name'].' '.$settings['TBLNAME'].' Re-Activated';
				$headers = '';
				$headers .= 'MIME-Version: 1.0'.EMAIL_NEWLINE;
				$headers .= 'Content-type: text/html; charset=UTF-8'.EMAIL_NEWLINE;
				$headers .= 'From: '.TITLE.' <'.EMAIL_FROM.'>'.EMAIL_NEWLINE;
				admin_mail($subject, $email, $headers, FALSE, 'admin_email_qs_reactivated.tpl');
			})->set_disable(function($service) {
			})->register();
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_dropdown_setting(self::$module, 'General', 'outofstock_quickservers', 'Out Of Stock Quickservers', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_QUICKSERVERS'), ['0', '1'], ['No', 'Yes']);
	}
}
