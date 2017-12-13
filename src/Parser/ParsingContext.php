<?php declare(strict_types=1);

namespace DaveRandom\WsdlDistiller\Parser;

use DaveRandom\WsdlDistiller\Parser\Types\ElementDefinitionRegistry;
use DaveRandom\WsdlDistiller\Parser\Types\TypeDefinitionRegistry;
use DaveRandom\WsdlDistiller\Parser\Types\TypeRegistry;

final class ParsingContext
{
    /** @var \DOMDocument */
    public $document;

    /** @var \DOMXPath */
    public $xpath;

    /** @var TypeDefinitionRegistry */
    public $typeDefinitions;

    /** @var ElementDefinitionRegistry */
    public $rootElementDefinitions;

    /** @var TypeRegistry */
    public $types;

    /** @var FullyQualifiedNameRegistry */
    public $fullyQualifiedNames;

    /** @var \ArrayObject[] */
    public $memberStores = [];

    /** @var bool[] */
    public $resolvingTypes = [];

    public function __construct(\DOMDocument $document, \DOMXPath $xpath)
    {
        $this->document = $document;
        $this->xpath = $xpath;

        $this->typeDefinitions = new TypeDefinitionRegistry;
        $this->types = new TypeRegistry;
        $this->fullyQualifiedNames = new FullyQualifiedNameRegistry;
    }
}
