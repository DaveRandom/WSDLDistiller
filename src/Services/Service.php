<?php

namespace DaveRandom\WSDLDistiller\Services;

/**
 * Class Service
 *
 * @package DaveRandom\WSDLDistiller\Services
 * @author  Chris Wright <github@daverandom.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class Service
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $classPrefix;

    /**
     * @var Operation[]
     */
    private $operations = [];

    /**
     * @var string
     */
    private $code;

    private function generateCode()
    {
        $this->code = <<<CODE
class {$this->classPrefix}{$this->name} extends {$this->classPrefix}BaseService
{
CODE;

        foreach ($this->operations as $operation) {
            $this->code .= (string)$operation;
        }

        $this->code .= "}\n";
    }

    /**
     * @param string $name
     * @param string $classPrefix
     */
    public function __construct($name, $classPrefix)
    {
        $this->name = (string)$name;
        $this->classPrefix = (string)$classPrefix;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!isset($this->code)) {
            $this->generateCode();
        }

        return $this->code;
    }

    /**
     * @param Operation $operation
     */
    public function addOperation(Operation $operation)
    {
        $this->operations[] = $operation;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->classPrefix . $this->name;
    }
}
