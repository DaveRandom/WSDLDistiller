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
    private static $classCodeTemplate = <<<CODE
{CLASS_DOC_BLOCK}
class {CLASS_NAME}
{
{FIELDS}
    /**
     * Property names that contain objects
     *
     * @var bool[]
     */
    private static \$__objectProperties = [
{OBJECT_PROPERTIES}
    ];

    /**
     * Property names that can be accessed as arrays
     *
     * @var bool[]
     */
    private static \$__arrayProperties = [
{ARRAY_PROPERTIES}
    ];

    /**
     * Create an instance of {CLASS_NAME} with properties populated from an array
     *
     * @param array \$array
     * @return {CLASS_NAME}
     */
    public static function fromArray(array \$array)
    {
        \$result = new {CLASS_NAME}();

        foreach (\$array as \$name => \$value) {
            \$expectedClassName = isset(self::\$__objectProperties[\$name]) ? self::\$__objectProperties[\$name] : null;

            if (is_array(\$value) || \$value instanceof \stdClass) {
                \$value = (array) \$value;

                if (isset(self::\$__arrayProperties[\$name])) {
                    if (ctype_digit((string) key(\$value))) {
                        if (\$expectedClassName) {
                            foreach (\$value as \$key => &\$element) {
                                if (!(\$element instanceof \$expectedClassName)) {
                                    if (is_object(\$element)) {
                                        \$element = (array) \$element;
                                    }

                                    if (is_array(\$element)) {
                                        \$element = \$expectedClassName::fromArray(\$element);
                                    } else {
                                        throw new \InvalidArgumentException('Unable to create object of class ' . \$expectedClassName . ' from supplied value at index ' . \$key);
                                    }
                                }
                            }
                        }

                        \$result->{\$name} = \$value;
                    } else if (\$expectedClassName) {
                        \$result->{\$name} = [\$expectedClassName::fromArray(\$value)];
                    } else {
                        throw new \InvalidArgumentException('Unable to interpret array at index ' . \$name);
                    }
                } else if (\$expectedClassName) {
                    \$result->{\$name} = \$expectedClassName::fromArray(\$value);
                } else {
                    throw new \InvalidArgumentException('Unable to interpret array at index ' . \$name);
                }
            } else {
                \$result->{\$name} = \$value;
            }
        }

        return \$result;
    }

    /**
     * Magic getter method
     *
     * @param string \$name
     * @return mixed
     */
    public function __get(\$name)
    {
        if (isset(self::\$__arrayProperties[\$name])) {
            if (isset(\$this->{\$name})) {
                if (!is_array(\$this->{\$name})) {
                    \$this->\$name = [\$this->{\$name}];
                }

                return \$this->{\$name};
            }

            return [];
        }

        return isset(\$this->{\$name}) ? \$this->{\$name} : null;
    }

    /**
     * Magic setter method
     *
     * @param string \$name
     * @param mixed  \$value
     */
    public function __set(\$name, \$value)
    {
        if (property_exists(\$this, \$name)) {
            \$expectedClassName = isset(self::\$__objectProperties[\$name]) ? self::\$__objectProperties[\$name] : null;

            if (isset(self::\$__arrayProperties[\$name])) {
                if (is_array(\$value)) {
                    if (\$expectedClassName) {
                        foreach (\$value as \$key => \$element) {
                            if (!(\$element instanceof \$expectedClassName)) {
                                throw new \InvalidArgumentException('Element ' . \$key . ' of supplied array is not an instance of ' . \$expectedClassName);
                            }
                        }
                    }
                } else {
                    if (\$expectedClassName && \$value !== null && !(\$value instanceof \$expectedClassName)) {
                        throw new \InvalidArgumentException('Supplied value is not an instance of ' . \$expectedClassName);
                    }

                    \$value = \$value !== null ? [\$value] : null;
                }
            } else if (\$expectedClassName) {
                if (\$value !== null && !(\$value instanceof \$expectedClassName)) {
                    throw new \InvalidArgumentException('Supplied value is not an instance of ' . \$expectedClassName);
                }
            }

            \$this->{\$name} = \$value;
        }
    }

    /**
     * Magic isset check method
     *
     * @param string \$name
     * @return bool
     */
    public function __isset(\$name)
    {
        return isset(\$this->\$name);
    }

    /**
     * Magic unsetter method
     *
     * @param string \$name
     */
    public function __unset(\$name)
    {
        if (property_exists(\$this, \$name)) {
            \$this->\$name = null;
        }
    }
}

CODE;

    private function renderClassCodeTemplate($vars)
    {
        return preg_replace_callback('/@@(?=@*+{)|@{|{(\w+)}/', function($match) use($vars) {
            if ($match[0][0] === '@') {
                return $match[0][1];
            }

            return isset($vars[$match[1]]) ? $vars[$match[1]] : $match[0];
        }, self::$classCodeTemplate);
    }

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
    private \${$name};

CODE;
        }

        $this->code = $this->renderClassCodeTemplate([
            'CLASS_DOC_BLOCK'   => $classDocBlock . ' */',
            'CLASS_NAME'        => $this->name,
            'FIELDS'            => implode("\n", $fieldCode),
            'OBJECT_PROPERTIES' => implode("\n", $objectProperties),
            'ARRAY_PROPERTIES'  => implode("\n", $arrayProperties),
        ]);
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
