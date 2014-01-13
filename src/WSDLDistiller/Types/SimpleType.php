<?php

namespace WSDLDistiller\Types;

/**
 * Class SimpleType
 *
 * @package WSDLDistiller\Types
 * @author  Chris Wright <github@daverandom.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class SimpleType extends Type
{
    const TYPE_RESTRICTION = 0x01;
    const TYPE_LIST = 0x02;
    const TYPE_UNION = 0x04;

    /**
     * @var TypeReference
     */
    private $base;

    /**
     * @var int
     */
    private $type;

    /**
     * @param string        $namespace
     * @param string        $name
     * @param TypeReference $base
     * @param int           $type
     */
    public function __construct($namespace, $name, TypeReference $base, $type)
    {
        parent::__construct($namespace, $name);
        $this->base = $base;
        $this->type = (int)$type;
    }

    /**
     * @param TypeReference $base
     */
    public function setBase($base)
    {
        $this->base = $base;
    }

    /**
     * @return TypeReference
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = (int)$type;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }
}
