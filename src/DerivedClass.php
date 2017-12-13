<?php

namespace DaveRandom\WsdlDistiller;

/**
 * Class DerivedClass
 *
 * @package DaveRandom\WSDLDistiller
 * @author  Chris Wright <github@daverandom.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
final class DerivedClass
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $base;

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
    private static $classCodeTemplate = <<<CODE
{CLASS_DOC_BLOCK}
class {CLASS_NAME} extends {CLASS_BASE}
{
{FIELDS}
    /**
     * Property names that contain objects
     *
     * @var bool[]
     */
    protected static \$__objectProperties = [
{OBJECT_PROPERTIES}
    ];

    /**
     * Property names that can be accessed as arrays
     *
     * @var bool[]
     */
    protected static \$__arrayProperties = [
{ARRAY_PROPERTIES}
    ];
}

CODE;

    private function generateCode()
    {
        $classDocBlock = "/**\n * Class {$this->name}\n *\n";
        $fieldCode = $arrayProperties = $objectProperties = [];

        foreach ($this->fields as $name => $field) {
            $arrayBrackets = '';

            if ($field['isArray']) {
                $arrayBrackets = '[]';
                $arrayProperties[] = '        ' . var_export($name, true) . ' => true,';
            }

            if (!in_array($field['type'], ['bool', 'int', 'float', 'string'])) {
                $objectProperties[] = '        ' . var_export($name, true) . ' => ' . var_export($field['type'], true) . ',';
            }

            $classDocBlock .= " * @property {$field['type']}{$arrayBrackets} \${$name}\n";

            $fieldCode[] = <<<CODE
    /**
     * @var {$field['type']}{$arrayBrackets}
     */
    protected \${$name};

CODE;
        }

        $this->code = render_template(self::$classCodeTemplate, [
            'CLASS_DOC_BLOCK'   => $classDocBlock . ' */',
            'CLASS_NAME'        => $this->name,
            'CLASS_BASE'        => $this->base,
            'FIELDS'            => implode("\n", $fieldCode),
            'OBJECT_PROPERTIES' => implode("\n", $objectProperties),
            'ARRAY_PROPERTIES'  => implode("\n", $arrayProperties),
        ]);
    }

    /**
     * Constructor
     *
     * @param string $name
     * @param string $base
     */
    public function __construct($name, $base)
    {
        $this->name = (string)$name;
        $this->base = (string)$base;
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
