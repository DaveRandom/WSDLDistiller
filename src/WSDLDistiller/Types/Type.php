<?php

namespace WSDLDistiller\Types;

/**
 * Class Type
 *
 * @package WSDLDistiller\Types
 * @author  Chris Wright <github@daverandom.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
abstract class Type
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @param string $namespace
     * @param string $name
     * @return string
     */
    public static function makeFQTN($namespace, $name)
    {
        return $namespace . '#' . $name;
    }

    /**
     * @param string $namespace
     * @param string $name
     */
    public function __construct($namespace, $name)
    {
        $this->namespace = (string)$namespace;
        $this->name = (string)$name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = (string)$name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = (string)$namespace;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getFQTN()
    {
        return self::makeFQTN($this->namespace, $this->name);
    }
}
