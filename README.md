# Network Interfaces
A simple PHP library for reading and manipulating the /etc/network/interfaces file in Debian based distributions. 

Install:

```
composer require carp3/network-interfaces
```

Usage: 
```php 
<?php
//include composer autoloader
include 'vendor/autoload.php';

// 'import' NetworkInterfaces class
use NetworkInterfaces\Adaptor;
use NetworkInterfaces\NetworkInterfaces;

// create new handle from /etc/networking/interfaces
$handle = new NetworkInterfaces('/etc/networking/interfaces');

// parse file
$handle->parse();

// create new Adaptor and set configs
$adaptor = new Adaptor();
$adaptor->name = "eth2";
$adaptor->family = "inet";
$adaptor->name = "statis";
$adaptor->address = '192.168.2.100';
$adaptor->gateway = '192.168.2.1';
$adaptor->netmask = '255.255.255.0';
$adaptor->auto = true;
$adaptor->allows[] = 'hotplug';

// add adaptor to NetworkInterfaces instance
$handle->add($adaptor);


// change eth0 ip address
$handle->Adaptors['eth0']->address = '192.168.0.30';

// Write changes to /etc/networking/interfaces
$handle->write();

// bringing up new interface
$handle->up('eth2');
````
