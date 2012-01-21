<?php
require_once 'SplClassLoader.php';
$Lycan_loader = new SplClassLoader('Lycan', realpath(dirname(__FILE__) . '/../'));
$Lycan_loader->register();
