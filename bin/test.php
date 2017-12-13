<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

var_dump((new \DaveRandom\WsdlDistiller\Parser\Parser())->parsePath('https://aqnet.aquarium-software.com/AquariumSDK/DetailFieldManagement.asmx?WSDL'));
