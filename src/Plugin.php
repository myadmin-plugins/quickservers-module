<?php

namespace Detain\MyAdminQuickservers;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminQuickservers
 */
class Plugin
{
	public static $name = 'Rapid Deploy Servers';
	public static $description = 'Allows selling of Servers that create a VPS using 100% of the resources to allow to manage the server in ways you can only with a VPS but while having a full servers worth of hardware at your disposal.';
	public static $help = '';
	public static $module = 'quickservers';
	public static $type = 'module';
	public static $settings = [
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
		'PREFIX' => 'qs'];

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			self::$module.'.load_processing' => [__CLASS__, 'loadProcessing'],
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.queue' => [__CLASS__, 'getQueue'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event)
	{
		$serviceClass = $event->getSubject();
        myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
		$GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function loadProcessing(GenericEvent $event)
	{
		/**
		 * @var \ServiceHandler $service
		 */
		$service = $event->getSubject();
		$service->setModule(self::$module)
			->setEnable(function ($service) {
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$db = get_module_db(self::$module);
				$db->query('update ' . $settings['TABLE']. ' set ' . $settings['PREFIX']."_status='pending-setup' where ". $settings['PREFIX']."_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'pending-setup', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
				$GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'initial_install', '', $serviceInfo[$settings['PREFIX'].'_custid']);
				admin_email_qs_pending_setup($serviceInfo[$settings['PREFIX'].'_id']);
                $db->query("select * from queue_log where history_section='".self::$module."order' and history_type='{$serviceInfo[$settings['PREFIX'].'_id']}' and history_new_value=0");
                if ($db->num_rows() > 0) {
                    $db->next_record(MYSQL_ASSOC);
                    $db->query("update queue_log set history_new_value=1 where history_id='{$db->Record['history_id']}'");
                }
			})->setReactivate(function ($service) {
				$serviceTypes = run_event('get_service_types', false, self::$module);
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$db = get_module_db(self::$module);
				if ($serviceInfo[$settings['PREFIX'].'_server_status'] === 'deleted' || $serviceInfo[$settings['PREFIX'].'_ip'] == '') {
					$GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'pending-setup', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
					$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='pending-setup' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
					$GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'initial_install', '', $serviceInfo[$settings['PREFIX'].'_custid']);
				} else {
					$GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'active', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
					$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
					$GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'enable', '', $serviceInfo[$settings['PREFIX'].'_custid']);
					$GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'start', '', $serviceInfo[$settings['PREFIX'].'_custid']);
				}
				$smarty = new \TFSmarty;
				$smarty->assign('qs_name', $serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name']);
				$email = $smarty->fetch('email/admin/qs_reactivated.tpl');
				$subject = $serviceInfo[$settings['TITLE_FIELD']].' '.$serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name'].' '.$settings['TBLNAME'].' Reactivated';
				$headers = '';
				$headers .= 'MIME-Version: 1.0'.PHP_EOL;
				$headers .= 'Content-type: text/html; charset=UTF-8'.PHP_EOL;
				$headers .= 'From: '.TITLE.' <'.EMAIL_FROM.'>'.PHP_EOL;
				admin_mail($subject, $email, $headers, false, 'admin/qs_reactivated.tpl');
			})->setDisable(function ($service) {
			})->setTerminate(function ($service) {
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$serviceTypes = run_event('get_service_types', false, self::$module);
				$class = '\\MyAdmin\\Orm\\'.get_orm_class_from_table($settings['TABLE']);
				$ips = [];
				$db = get_module_db(self::$module);
				$db->query("update {$settings['PREFIX']}_masters set {$settings['PREFIX']}_available=1 where {$settings['PREFIX']}_id={$serviceInfo[$settings['PREFIX'].'_server']}", __LINE__, __FILE__);
				$db->query("select * from {$settings['PREFIX']}_ips where ips_{$settings['PREFIX']}='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
				while ($db->next_record(MYSQL_ASSOC)) {
					if (!in_array($db->Record['ips_ip'], $ips)) {
						$ips[] = $db->Record['ips_ip'];
					}
				}
				$db->query("update {$settings['PREFIX']}_ips set ips_main=0,ips_usable=1,ips_used=0,ips_{$settings['PREFIX']}=0 where ips_{$settings['PREFIX']}='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
				function_requirements('reverse_dns');
				foreach ($ips as $ip) {
					if (validIp($ip)) {
						reverse_dns($ip, '', 'remove_reverse');
					}
				}
				$GLOBALS['tf']->history->add(self::$module . 'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'destroy', '', $serviceInfo[$settings['PREFIX'].'_custid']);
			})->register();
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
        $settings->setTarget('module');
		$settings->add_dropdown_setting(self::$module, _('General'), 'outofstock_quickservers', _('Out Of Stock Quickservers'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_QUICKSERVERS'), ['0', '1'], ['No', 'Yes']);
		$settings->add_master_text_setting(self::$module, 'Server Settings', self::$module, 'cost', 'qs_cost', 'Server Cost', '<p>The price to list the server at.</p>');
        $settings->setTarget('global');
	}


	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueue(GenericEvent $event)
	{
		//if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
		$settings = get_module_settings(self::$module);
		$serviceInfo = $event->getSubject();
		myadmin_log(self::$module, 'info', self::$name.' Queue '.ucwords(str_replace('_', ' ', $serviceInfo['action'])).' for '.$settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_hostname'].'(#'.$serviceInfo[$settings['PREFIX'].'_id'].'/'.$serviceInfo[$settings['PREFIX'].'_vzid'].')', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
		$server_info = $serviceInfo['server_info'];
		if (!file_exists(__DIR__.'/../../myadmin-kvm-vps/templates/'.$serviceInfo['action'].'.sh.tpl')) {
			myadmin_log(self::$module, 'error', 'Call '.$serviceInfo['action'].' for '.$settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_hostname'].'(#'.$serviceInfo[$settings['PREFIX'].'_id'].'/'.$serviceInfo[$settings['PREFIX'].'_vzid'].') Does not Exist for '.self::$name, __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
		} else {
			$smarty = new \TFSmarty();
			$smarty->assign($serviceInfo);
			//$smarty->assign($settings['PREFIX'].'_vzid', isset($serviceInfo['module']) && $serviceInfo['module'] == 'quickservers' ? 'qs'.$serviceInfo[$settings['PREFIX'].'_vzid'] : (is_numeric($serviceInfo[$settings['PREFIX'].'_vzid']) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceInfo[$settings['PREFIX'].'_vzid'] : 'linux'.$serviceInfo[$settings['PREFIX'].'_vzid']) : $serviceInfo[$settings['PREFIX'].'_vzid']));
			$event['output'] = $event['output'].$smarty->fetch(__DIR__.'/../../myadmin-kvm-vps/templates/'.$serviceInfo['action'].'.sh.tpl');
		}
		$event->stopPropagation();
		//}
	}
}
