<?php
/**
 * This file was automatically generated by WSDLDistiller and should not be altered
 * @see https://github.com/DaveRandom/WSDLDistiller
 * @version 2016.07.13
 */

{NAMESPACE}
/**
 * Class WSDLDistillerBaseType
 *
 * Base class for all types generated from the WSDL
 */
abstract class WSDLDistillerBaseType
{
    protected static $__objectProperties = [];
    protected static $__arrayProperties = [];

    /**
     * Create an instance of the inheriting class with properties populated from an array
     *
     * @param array $array
     * @return static
     */
    public static function fromArray(array $array)
    {
        $result = new static();

        foreach ($array as $name => $value) {
            $expectedClassName = isset(static::$__objectProperties[$name]) ? static::$__objectProperties[$name] : null;

            if (is_array($value) || $value instanceof \stdClass) {
                $value = (array)$value;

                if (isset(static::$__arrayProperties[$name])) {
                    if (ctype_digit((string)key($value))) {
                        if ($expectedClassName) {
                            foreach ($value as $key => &$element) {
                                if (!($element instanceof $expectedClassName)) {
                                    if (is_object($element)) {
                                        $element = (array)$element;
                                    }

                                    if (is_array($element)) {
                                        $element = $expectedClassName::fromArray($element);
                                    } else {
                                        throw new \InvalidArgumentException('Unable to create object of class ' . $expectedClassName . ' from supplied value at index ' . $key);
                                    }
                                }
                            }
                        }

                        $result->{$name} = $value;
                    } else if ($expectedClassName) {
                        $result->{$name} = [$expectedClassName::fromArray($value)];
                    } else {
                        throw new \InvalidArgumentException('Unable to interpret array at index ' . $name);
                    }
                } else if ($expectedClassName) {
                    $result->{$name} = $expectedClassName::fromArray($value);
                } else {
                    throw new \InvalidArgumentException('Unable to interpret array at index ' . $name);
                }
            } else {
                $result->{$name} = $value;
            }
        }

        return $result;
    }

    /**
     * Magic getter method
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset(static::$__arrayProperties[$name])) {
            if (isset($this->{$name})) {
                if (!is_array($this->{$name})) {
                    $this->$name = [$this->{$name}];
                }

                return $this->{$name};
            }

            return [];
        }

        return isset($this->{$name}) ? $this->{$name} : null;
    }

    /**
     * Magic setter method
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $expectedClassName = isset(static::$__objectProperties[$name]) ? static::$__objectProperties[$name] : null;

            if (isset(static::$__arrayProperties[$name])) {
                if (is_array($value)) {
                    if ($expectedClassName) {
                        foreach ($value as $key => $element) {
                            if (!($element instanceof $expectedClassName)) {
                                throw new \InvalidArgumentException('Element ' . $key . ' of supplied array is not an instance of ' . $expectedClassName);
                            }
                        }
                    }
                } else {
                    if ($expectedClassName && $value !== null && !($value instanceof $expectedClassName)) {
                        throw new \InvalidArgumentException('Supplied value is not an instance of ' . $expectedClassName);
                    }

                    $value = $value !== null ? [$value] : null;
                }
            } else if ($expectedClassName) {
                if ($value !== null && !($value instanceof $expectedClassName)) {
                    throw new \InvalidArgumentException('Supplied value is not an instance of ' . $expectedClassName);
                }
            }

            $this->{$name} = $value;
        }
    }

    /**
     * Magic isset check method
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * Magic unsetter method
     *
     * @param string $name
     */
    public function __unset($name)
    {
        if (property_exists($this, $name)) {
            $this->$name = null;
        }
    }

    public function __debugInfo()
    {
        $result = [];

        foreach (get_object_vars($this) as $prop => $value) {
            if (substr($prop, 0, 2) !== '__') {
                $result[$prop] = $value;
            }
        }

        return $result;
    }
}
