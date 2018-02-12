<?php

use Sakalys\Components\ProxyService\AddressCollector;

require '../vendor/autoload.php';
define('ROOT_PATH', dirname(__FILE__) . '/..');


$collector = new AddressCollector(ROOT_PATH . '/storage/cookies');

$collector->getList();