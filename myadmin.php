<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_quickservers define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'MyAdmin QuickServers Module for MyAdmin',
	'description' => 'Allows selling of QuickServers Module',
	'help' => '',
	'module' => 'quickservers',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-quickservers-module',
	'repo' => 'https://github.com/detain/myadmin-quickservers-module',
	'version' => '1.0.0',
	'type' => 'module',
	'hooks' => [
		'quickservers.load_processing' => ['Detain\MyAdminQuickservers\Plugin', 'Load'],
		'quickservers.settings' => ['Detain\MyAdminQuickservers\Plugin', 'Settings'],
		/* 'function.requirements' => ['Detain\MyAdminQuickservers\Plugin', 'Requirements'],
		'quickservers.activate' => ['Detain\MyAdminQuickservers\Plugin', 'Activate'],
		'quickservers.change_ip' => ['Detain\MyAdminQuickservers\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminQuickservers\Plugin', 'Menu'] */
	],
];
