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

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->elements);
    }
}
