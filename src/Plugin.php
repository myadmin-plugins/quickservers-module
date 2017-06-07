<?php

namespace Detain\MyAdminQuickservers;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Load(GenericEvent $event) {

	}

	public static function Settings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_dropdown_setting('quickservers', 'General', 'outofstock_quickservers', 'Out Of Stock Quickservers', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_QUICKSERVERS'), array('0', '1'), array('No', 'Yes', ));
	}
}
