# MyAdmin QuickServers Module

[![Tests](https://github.com/detain/myadmin-quickservers-module/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-quickservers-module/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-quickservers-module/version)](https://packagist.org/packages/detain/myadmin-quickservers-module)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-quickservers-module/downloads)](https://packagist.org/packages/detain/myadmin-quickservers-module)
[![License](https://poser.pugx.org/detain/myadmin-quickservers-module/license)](https://packagist.org/packages/detain/myadmin-quickservers-module)

QuickServers (Rapid Deploy Servers) module for the [MyAdmin](https://github.com/detain/myadmin) control panel. Provides automated provisioning, lifecycle management, and billing integration for dedicated-hardware VPS instances that give customers full server resources with VPS-level manageability.

## Features

- Automated server provisioning and teardown via event-driven hooks
- Service lifecycle management (enable, reactivate, disable, terminate)
- Integrated billing with prorate support
- Queue-based operations for KVM template execution
- Admin settings panel with out-of-stock controls and per-server cost configuration
- IP allocation and reverse DNS management on termination

## Requirements

- PHP 8.2 or later
- ext-soap
- Symfony EventDispatcher 5.x, 6.x, or 7.x

## Installation

```sh
composer require detain/myadmin-quickservers-module
```

## Testing

```sh
composer install
vendor/bin/phpunit
```

## License

Licensed under the [LGPL-2.1](https://opensource.org/licenses/LGPL-2.1).
