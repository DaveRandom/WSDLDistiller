#!/usr/bin/php
<?php

namespace WSDLDistiller;

spl_autoload_register(function ($className) {
    if (substr($className, 0, 13) === 'WSDLDistiller') {
        $file = __DIR__ . '/' . strtr($className, '\\', '/') . '.php';

        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            require $file;
        }
    }
});

function error($message)
{
    fwrite(STDERR, "Error: $message\n");
    exit(1);
}

function parse_options($argv, &$options)
{
    $phpIdentifier = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

    for ($i = 1, $l = count($argv); $i < $l; $i++) {
        $parts = explode('=', $argv[$i], 2);
        $value = count($parts) === 2 ? $parts[1] : NULL;
        $opt = strtolower(trim($parts[0], '-'));

        switch ($opt) {
            case 'prefix':
                if ($value === NULL) {
                    $value = $argv[++$i];
                }

                if (!preg_match("/^$phpIdentifier$/", $value)) {
                    error("Invalid class name prefix");
                }
                break;

            case 'namespace':
                if ($value === NULL) {
                    $value = $argv[++$i];
                }

                if (!preg_match("/^$phpIdentifier(?:\\\\$phpIdentifier)*$/", $value)) {
                    error("Invalid namespace identifier");
                }
                break;

            case 'file-names':
                if ($value === NULL) {
                    $value = $argv[++$i];
                }

                if (substr_count($value, '%s') !== 1) {
                    error('Invalid file naming pattern, must contain "%s" exactly once');
                }
                break;

            case 'output-dir':
                if ($value === NULL) {
                    $value = $argv[++$i];
                }
                break;

            default:
                error("Unknown option: $opt");
        }

        $options[$opt] = $value;
    }
}

$options = [
    'prefix'     => NULL,
    'namespace'  => NULL,
    'file-names' => '%s.php',
    'output-dir' => __DIR__ . DIRECTORY_SEPARATOR . 'wsdl_classes',
];

$fileName = basename(__FILE__);
$helpStr = <<<HELP

 Syntax: php {$fileName} [options] <wsdl-path>
  Options:
    --prefix      Prefix all class names with this string
    --namespace   Place generated files in this namespace
    --file-names  Pattern for naming files (printf with one string argument)
    --output-dir  Directory where generated files will be created

HELP;

if (!isset($argv[1])) {
    exit($helpStr);
}

if (!$wsdlPath = @realpath(array_pop($argv))) {
    error("Invalid WSDL path");
}

parse_options($argv, $options);

if (!file_exists($options['output-dir'])) {
    @mkdir($options['output-dir'], 0777, TRUE);
}
if (!is_dir($options['output-dir'])) {
    error('Output directory does not exist and it could not be created');
}

$pathFormat = rtrim($options['output-dir'], '\\/') . DIRECTORY_SEPARATOR . $options['file-names'];

$namespace = isset($options['namespace']) ? "\nnamespace {$options['namespace']};\n" : '';
$fileHeader = <<<HEADER
<?php
/**
 * This file was automatically generated by WSDLDistiller and should not be altered
 * @see: https://github.com/DaveRandom/WSDLDistiller
 */
{$namespace}

HEADER;

$distiller = new Distiller($wsdlPath, $options['prefix']);
foreach ($distiller->getClasses() as $class) {
    file_put_contents(sprintf($pathFormat, $class->getName()), $fileHeader . $class);
}

$serviceCollection = $distiller->getServiceCollection();
file_put_contents(sprintf($pathFormat, $serviceCollection->getBaseServiceName()), $fileHeader . $serviceCollection->getBaseServiceCode());
foreach ($serviceCollection->getServices() as $service) {
    file_put_contents(sprintf($pathFormat, $service->getClassName()), $fileHeader . $service);
}

file_put_contents(sprintf($pathFormat, $serviceCollection->getServiceFactoryName()), $fileHeader . $serviceCollection->getServiceFactoryCode());