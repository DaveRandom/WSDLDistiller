<?php

namespace DaveRandom\WSDLDistiller\Services;

/**
 * Class ServiceCollection
 *
 * @package DaveRandom\WSDLDistiller\Services
 * @author  Chris Wright <github@daverandom.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class ServiceCollection
{
    /**
     * @var string
     */
    private $classPrefix;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string[]
     */
    private $typeNames;

    /**
     * @var string
     */
    private $serviceFactoryCode;

    /**
     * @var string
     */
    private $baseServiceCode;

    /**
     * @var Service[]
     */
    private $services = [];

    private function generateBaseServiceCode()
    {
        $this->baseServiceCode = <<<CODE
class {$this->getBaseServiceName()} extends \SoapClient
{
    /**
     * @var string[string]
     */
    private static \$classMap = [

CODE;

        foreach ($this->typeNames as $typeName) {
            $this->baseServiceCode .= sprintf(
                "        %s => %s,\n",
                var_export($typeName, true),
                $typeName . '::class'
            );
        }

        $this->baseServiceCode .= <<<CODE
    ];

    /**
     * @param mixed \$wsdl
     * @param array \$options
     * @throws \\SoapFault
     */
    public function __construct(\$wsdl, array \$options = [])
    {
        \$options['classmap'] = array_merge(
            self::\$classMap,
            isset(\$options['classmap']) ? \$options['classmap'] : []
        );

        parent::SoapClient(\$wsdl, \$options);
    }
}

CODE;
    }

    private function generateServiceFactoryCode()
    {
        $this->serviceFactoryCode = <<<CODE
class {$this->getServiceFactoryName()}
{
    private \$defaultOptions;

    public function __construct(array \$defaultOptions = [])
    {
        \$this->defaultOptions = \$defaultOptions;
    }
CODE;

        foreach ($this->services as $service) {
            /** @var Service $service */
            $this->serviceFactoryCode .= <<<CODE

    /**
     * Create a client for the {$service->getName()} service
     *
     * @param mixed \$wsdl
     * @param array \$options
     * @return {$this->classPrefix}{$service->getName()}
     */
    public function create{$service->getName()}(\$wsdl, array \$options = [])
    {
        return new {$this->classPrefix}{$service->getName()}(\$wsdl, \$options + \$this->defaultOptions);
    }

CODE;
        }

        $this->serviceFactoryCode .= "}\n";
    }

    /**
     * @param string $classPrefix
     * @param string $namespace
     * @param string[] $typeNames
     */
    public function __construct($classPrefix, $namespace, array $typeNames)
    {
        $this->classPrefix = $classPrefix;
        $this->namespace = $namespace;
        $this->typeNames = $typeNames;
    }

    /**
     * @param Service $service
     */
    public function addService(Service $service)
    {
        $this->services[] = $service;
    }

    /**
     * @return Service[]
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * @return string
     */
    public function getServiceFactoryCode()
    {
        if (!isset($this->serviceFactoryCode)) {
            $this->generateServiceFactoryCode();
        }

        return $this->serviceFactoryCode;
    }

    /**
     * @return string
     */
    public function getServiceFactoryName()
    {
        return "{$this->classPrefix}ServiceFactory";
    }

    /**
     * @return string
     */
    public function getBaseServiceCode()
    {
        if (!isset($this->baseServiceCode)) {
            $this->generateBaseServiceCode();
        }

        return $this->baseServiceCode;
    }

    /**
     * @return string
     */
    public function getBaseServiceName()
    {
        return "{$this->classPrefix}BaseService";
    }
}
