<?php declare(strict_types=1);

namespace DaveRandom\WsdlDistiller;

const WSDL_SCHEMA_URI = 'http://schemas.xmlsoap.org/wsdl/';

\define(__NAMESPACE__ . '\\LIB_ROOT_DIR', \realpath(__DIR__ . '/..'));
\define(__NAMESPACE__ . '\\RESOURCES_ROOT_DIR', \realpath(LIB_ROOT_DIR . '/resources'));
\define(__NAMESPACE__ . '\\TEMPLATES_ROOT_DIR', \realpath(LIB_ROOT_DIR . '/templates'));

\assert(LIB_ROOT_DIR !== false, new \Error("LIB_ROOT_DIR is false"));
\assert(RESOURCES_ROOT_DIR !== false, new \Error("RESOURCES_ROOT_DIR is false"));
\assert(TEMPLATES_ROOT_DIR !== false, new \Error("TEMPLATES_ROOT_DIR is false"));
