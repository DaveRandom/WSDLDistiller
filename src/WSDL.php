<?php declare(strict_types=1);

namespace DaveRandom\WsdlDistiller;

use DaveRandom\XsdDistiller\Schema;

final class WSDL
{
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }
}
