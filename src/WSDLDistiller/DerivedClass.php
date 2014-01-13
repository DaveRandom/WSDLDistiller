<?php

namespace WSDLDistiller;

/**
 * Class DerivedClass
 *
 * @package WSDLDistiller
 * @author  Chris Wright <github@daverandom.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class DerivedClass
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array[string][]
     */
    private $fields = [];

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private static $magicMethodsTemplate = <<<CODE

    /**
     * Property names that can be accessed as arrays
     *
     * @var string[int]
     */
    private static \$__ArrayProperties__ = [
        %s,
    ];

    /**
     * Magic getter method
     *
     * @param string \$name
     * @return mixed
     */
    public function __get(\$name)
    {
        if (isset(self::\$__ArrayProperties__[\$name])) {
            if (!is_array(\$this->\$name)) {
                \$this->\$name = \$this->\$name !== null ? [\$this->\$name] : [];
            }

            return \$this->\$name;
        }

        return null;
    }

    /**
     * Magic setter method
     *
     * @param string \$name
     * @param mixed  \$value
     */
    public function __set(\$name, \$value)
    {
        if (isset(self::\$__ArrayProperties__[\$name])) {
            if (!is_array(\$this->\$name)) {
                \$this->\$name = \$value !== null ? [\$value] : [];
            }
        }
    }

CODE;

    private function generateCode()
    {
        $fieldCode = '';
        $magicProperties = "/**\n";
        $arrayProperties = [];

        foreach ($this->fields as $name => $field) {
            if ($field['isArray']) {
                $visibilityModifier = 'private';
                $arrayBrackets = '[]';
                $arrayProperties[] = var_export($name, TRUE) . ' => true';
                $magicProperties .= " * @property {$field['type']}[] \${$name}\n";
            } else {
                $visibilityModifier = 'public';
                $arrayBrackets = '';
            }

            $fieldCode .= <<<CODE

    /**
     * @var {$field['type']}{$arrayBrackets}
     */
    {$visibilityModifier} \${$name};

CODE;
        }

        if ($magicProperties !== "/**\n") {
            $magicProperties .= " */\n";
            $magicMethods = sprintf(self::$magicMethodsTemplate, implode(",\n        ", $arrayProperties));
        } else {
            $magicMethods = $magicProperties = '';
        }

        $this->code = "{$magicProperties}class {$this->name}\n{{$fieldCode}{$magicMethods}}\n";
    }

    /**
     * Constructor
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = (string)$name;
    }

    /**
     * @param string $name
     * @param string $type
     * @param bool   $isArray
     */
    public function addField($name, $type, $isArray)
    {
        $this->fields[(string)$name] = [
            'type'    => (string)$type,
            'isArray' => (bool)$isArray,
        ];
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
