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

    public static function makeFQTN(string $namespace, string $name): string
    {
        return $namespace . '#' . $name;
    }

    public function __construct(string $namespace, string $name)
    {
        $this->namespace = $namespace;
        $this->name = $name;
    }

    public function setName(string $name)
    {
        $this->name = (string)$name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getFQTN(): string
    {
        return self::makeFQTN($this->namespace, $this->name);
    }
}
