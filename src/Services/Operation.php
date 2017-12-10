<?php

namespace DaveRandom\WSDLDistiller\Services;

/**
 * Class Operation
 *
 * @package DaveRandom\WSDLDistiller\Services
 * @author  Chris Wright <github@daverandom.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class Operation
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $arguments = [];

    /**
     * @var string
     */
    private $returnType;

    /**
     * @var string
     */
    private $code;

    private function generateCode()
    {
        $docBlock = '';
        $argumentsIn = $argumentsOut = [];

        foreach ($this->arguments as $name => $type) {
            $docBlock .= "@param {$type} \${$name}\n     * ";
            $argumentsIn[] = in_array($type, ['bool', 'int', 'float', 'string']) ? "\${$name}" : "{$type} \${$name}";
            $argumentsOut[] = "\${$name}";
        }

        $docBlock .= "@return {$this->returnType}\n     * @throws \\SoapFault";
        $argumentsIn = implode(', ', $argumentsIn);
        $argumentsOut = implode(', ', $argumentsOut);

        $this->code = <<<CODE

    /**
     * {$docBlock}
     */
    public function {$this->name}({$argumentsIn})
    {
        return \$this->__soapCall('{$this->name}', [{$argumentsOut}]);
    }

CODE;
    }

    /**
     * @param string
     */
    public function __construct($name)
    {
        $this->name = $name;
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
     * @param string $name
     * @param string $type
     */
    public function addArgument($name, $type)
    {
        $this->arguments[$name] = $type;
    }

    /**
     * @param string $type
     */
    public function setReturnType($type)
    {
        $this->returnType = $type;
    }
}
