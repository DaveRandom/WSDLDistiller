<?php

namespace WSDLDistiller\Types;

/**
 * Class ComplexType
 *
 * @package WSDLDistiller\Types
 * @author  Chris Wright <github@daverandom.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class ComplexType extends Type implements \IteratorAggregate
{
    /**
     * @var TypeReference[]
     */
    private $elements = [];

    /**
     * @var string
     */
    private $base;

    public function __construct(string $namespace, string $name, string $base = 'WSDLDistillerBaseType')
    {
        parent::__construct($namespace, $name);

        $this->base = $base;
    }

    /**
     * @param string        $name
     * @param TypeReference $typeRef
     * @param bool          $isArray
     */
    public function addElement($name, TypeReference $typeRef, $isArray)
    {
        $this->elements[$name] = [
            'type'    => $typeRef,
            'isArray' => (bool)$isArray,
        ];
    }

    public function setBase(string $base)
    {
        $this->base = $base;
    }

    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->elements);
    }
}
