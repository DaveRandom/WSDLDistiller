<?php

namespace DaveRandom\WSDLDistiller;

use InvalidArgumentException, RuntimeException,
    DaveRandom\WSDLDistiller\Services\Operation,
    DaveRandom\WSDLDistiller\Services\Service,
    DaveRandom\WSDLDistiller\Services\ServiceCollection,
    DaveRandom\WSDLDistiller\Types\Type,
    DaveRandom\WSDLDistiller\Types\SimpleType,
    DaveRandom\WSDLDistiller\Types\ComplexType,
    DaveRandom\WSDLDistiller\Types\TypeReference;

/**
 * Class Distiller
 *
 * @package DaveRandom\WSDLDistiller
 * @author  Chris Wright <github@daverandom.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class Distiller
{
    const URI_XML_SCHEMA = 'http://www.w3.org/2001/XMLSchema';
    const URI_WSDL_SCHEMA = 'http://schemas.xmlsoap.org/wsdl/';

    /**
     * @var string
     */
    private $classPrefix;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * @var \DOMXPath
     */
    private $xpath;

    /**
     * @var DerivedClass[]
     */
    private $classes = [];

    /**
     * @var string[]
     */
    private $complexTypeNames = [];

    /**
     * @var ServiceCollection
     */
    private $serviceCollection;

    /**
     * @var string[]
     */
    private $primitiveTypeMap = [
        'boolean'            => 'bool',
        'float'              => 'float',
        'double'             => 'float',
        'decimal'            => 'float',
        'integer'            => 'int',
        'nonPositiveInteger' => 'int',
        'negativeInteger'    => 'int',
        'long'               => 'int',
        'int'                => 'int',
        'short'              => 'int',
        'byte'               => 'int',
        'nonNegativeInteger' => 'int',
        'unsignedLong'       => 'int',
        'unsignedInt'        => 'int',
        'unsignedShort'      => 'int',
        'unsignedByte'       => 'int',
        'positiveInteger'    => 'int',
    ];

    /** @var SimpleType[] */
    private $simpleTypes = [];

    /** @var ComplexType[] */
    private $complexTypes;

    /** @var TypeReference */
    private $typeRefs = [];

    /** @var string[] */
    private $phpTypes = [];

    /**
     * @param string $wsdl
     * @param string $classPrefix
     * @param string $namespace
     */
    public function __construct($wsdl, $classPrefix = '', $namespace = '')
    {
        $this->classPrefix = (string)$classPrefix;

        $this->document = new \DOMDocument;
        if (!@$this->document->loadXML($wsdl)) {
            if (!@$this->document->load($wsdl)) {
                throw new InvalidArgumentException('Unable to load WSDL from the supplied argument');
            }
        }

        $this->xpath = new \DOMXPath($this->document);
        $this->xpath->registerNamespace('xs', self::URI_XML_SCHEMA);
        $this->xpath->registerNamespace('wsdl', self::URI_WSDL_SCHEMA);
        $this->namespace = $namespace;
    }

    /**
     * @param \DOMElement $element
     * @return string
     */
    private function getTargetNamespace(\DOMElement $element)
    {
        $targetNamespace = $element->namespaceURI;

        do {
            if ($element->hasAttribute('targetNamespace')) {
                $targetNamespace = $element->getAttribute('targetNamespace');
                break;
            }
        } while ($element = $element->parentNode);

        return $targetNamespace;
    }

    /**
     * @param \DOMElement $element
     * @param string      $attribute
     * @return TypeReference
     * @throws RuntimeException
     */
    private function resolveTypeRef(\DOMElement $element, $attribute)
    {
        $nameParts = explode(':', $element->getAttribute($attribute));

        if (count($nameParts) === 1) {
            $name = $nameParts[0];
            $namespace = $this->getTargetNamespace($element);
        } else if (count($nameParts) === 2) {
            list($prefix, $name) = $nameParts;
            $namespace = $element->lookupNamespaceUri($prefix);
        } else {
            throw new RuntimeException('Invalid type reference ' . $element->getAttribute($attribute));
        }

        if (!isset($this->typeRefs[$fqtn = Type::makeFQTN($namespace, $name)])) {
            $this->typeRefs[$fqtn] = new TypeReference($namespace, $name);
        }

        return $this->typeRefs[$fqtn];
    }

    private function getNameFromPrefixedName($prefixed)
    {
        $baseNameParts = explode(':', $prefixed);
        return end($baseNameParts);
    }

    private function extractComplexTypes()
    {
        foreach ($this->xpath->query('//xs:schema/xs:complexType | //xs:schema/xs:element[@name and xs:complexType]') as $complexType) {
            /** @var \DOMElement $complexType */
            $name = $complexType->getAttribute('name');
            $namespace = $this->getTargetNamespace($complexType);

            $extensionNodes = $this->xpath->query('./xs:complexContent/xs:extension', $complexType);
            if ($extensionNodes->length === 1) {
                /** @var \DOMElement $extension */
                $extension = $extensionNodes->item(0);
                $type = new ComplexType($namespace, $name, $this->getNameFromPrefixedName($extension->getAttribute('base')));
                $nodes = $this->xpath->query('./xs:sequence', $extension);
                $sequence = $nodes->length ? $nodes->item(0) : null;
            } else {
                $type = new ComplexType($namespace, $name);
                $query = $this->getNameFromPrefixedName($complexType->tagName) === 'complexType'
                    ? './xs:sequence'
                    : './xs:complexType/xs:sequence';
                $nodes = $this->xpath->query($query, $complexType);
                $sequence = $nodes->length ? $nodes->item(0) : null;
            }

            $this->complexTypes[$type->getFQTN()] = $type;

            foreach ($sequence ? $this->xpath->query('./xs:element', $sequence) : [] as $element) {
                /** @var \DOMElement $element */
                $elementName = $element->getAttribute('name');
                $typeRef = $this->resolveTypeRef($element, 'type');

                $isArray = NULL;
                $minOccurs = $element->hasAttribute('minOccurs') ? (int)$element->getAttribute('minOccurs') : 1;
                if ($element->hasAttribute('maxOccurs')) {
                    $maxOccurs = $element->getAttribute('maxOccurs');

                    if ($maxOccurs === 'unbounded') {
                        $isArray = TRUE;
                    } else {
                        $maxOccurs = (int)$maxOccurs;
                    }
                } else {
                    $maxOccurs = 1;
                }

                if (!isset($isArray)) {
                    if ($minOccurs > $maxOccurs) {
                        throw new RuntimeException(
                            "Invalid complex type element: $name::$elementName: minOccurs ($minOccurs) > maxOccurs ($maxOccurs)"
                        );
                    }

                    $isArray = $maxOccurs > 1;
                }

                $type->addElement($elementName, $typeRef, $isArray);
            }
        }
    }

    /**
     * @throws RuntimeException
     */
    private function extractSimpleTypes()
    {
        foreach ($this->xpath->query('//xs:schema/xs:simpleType') as $simpleType) {
            /** @var \DOMElement $simpleType */
            $name = $simpleType->getAttribute('name');
            $namespace = $this->getTargetNamespace($simpleType);

            /** @var \DOMNodeList $restriction */
            /** @var \DOMNodeList $list */
            /** @var \DOMElement $base */
            if (($restriction = $this->xpath->query('./xs:restriction', $simpleType)) && $restriction->length) {
                $restriction = $restriction->item(0);
                /** @var \DOMElement $restriction */
                $baseRef = $this->resolveTypeRef($restriction, 'base');
                $baseType = SimpleType::TYPE_RESTRICTION;
            } else if (($list = $this->xpath->query('./xs:list', $simpleType)) && $list->length) {
                $list = $list->item(0);
                /** @var \DOMElement $list */
                $baseRef = $this->resolveTypeRef($list, 'base');
                $baseType = SimpleType::TYPE_LIST;
            } else {
                throw new RuntimeException(
                    'Invalid simple type definition ' . Type::makeFQTN($namespace, $name)
                    . ' (unions are not supported)'
                );
            }

            $type = new SimpleType($namespace, $name, $baseRef, $baseType);
            $this->simpleTypes[$type->getFQTN()] = $type;
        }
    }

    /**
     * @throws RuntimeException
     */
    private function resolveComplexTypes()
    {
        /** @var ComplexType $complexType */
        foreach ($this->complexTypes as $complexType) {
            /** @var TypeReference[] $element */
            foreach ($complexType as $element) {
                $fqtn = $element['type']->getFQTN();

                if ($element['type']->getNamespace() === self::URI_XML_SCHEMA) {
                    if (!isset($this->phpTypes[$fqtn])) {
                        $this->phpTypes[$fqtn] = isset($this->primitiveTypeMap[$element['type']->getName()])
                            ? $this->primitiveTypeMap[$element['type']->getName()]
                            : 'string';
                    }
                } else if (!isset($this->complexTypes[$fqtn]) && !isset($this->simpleTypes[$fqtn])) {
                    if ($element['type']->getName() !== '') {
                        throw new RuntimeException('Expected type ' . $fqtn . ' was not defined in the document');
                    }

                    if (!isset($this->phpTypes[$fqtn])) {
                        $this->phpTypes[$fqtn] = 'mixed';
                    }
                }
            }

            $typeName = $complexType->getName();
            $this->phpTypes[$complexType->getFQTN()] = $this->classPrefix . $typeName;
            $this->complexTypeNames[] = $typeName;
        }
    }

    /**
     * @param SimpleType $simpleType
     * @return string
     * @throws RuntimeException
     */
    private function resolveSimpleType(SimpleType $simpleType)
    {
        if (!isset($this->phpTypes[$typeFQTN = $simpleType->getFQTN()])) {
            $base = $simpleType->getBase();
            $baseFQTN = $base->getFQTN();

            if ($base->getNamespace() === self::URI_XML_SCHEMA) {
                if (!isset($this->phpTypes[$baseFQTN])) {
                    $this->phpTypes[$baseFQTN] = isset($this->primitiveTypeMap[$base->getName()])
                        ? $this->primitiveTypeMap[$base->getName()]
                        : 'string';
                }

                $this->phpTypes[$typeFQTN] = $this->phpTypes[$baseFQTN];
            } else {
                if (!isset($this->simpleTypes[$baseFQTN])) {
                    throw new RuntimeException('Expected simple type ' . $baseFQTN . ' was not defined in the document');
                }

                $this->phpTypes[$typeFQTN] = $this->resolveSimpleType($this->simpleTypes[$baseFQTN]);
            }
        }

        return $this->phpTypes[$typeFQTN];
    }

    /**
     * @throws RuntimeException
     */
    private function resolveSimpleTypes()
    {
        foreach ($this->simpleTypes as $simpleType) {
            $this->resolveSimpleType($simpleType);
        }
    }

    private function loadTypes()
    {
        $this->extractComplexTypes();
        $this->extractSimpleTypes();

        $this->resolveComplexTypes();
        $this->resolveSimpleTypes();
    }

    private function generateClasses()
    {
        if (empty($this->phpTypes)) {
            $this->loadTypes();
        }

        foreach ($this->complexTypes as $complexType) {
            $class = new DerivedClass($this->classPrefix . $complexType->getName(), $complexType->getBase());

            foreach ($complexType as $name => $elementInfo) {
                /** @var TypeReference $typeRef */
                $typeRef = $elementInfo['type'];
                $isArray = $elementInfo['isArray'];

                $class->addField($name, $this->phpTypes[$typeRef->getFQTN()], $isArray);
            }

            $this->classes[] = $class;
        }
    }

    /**
     * TODO: Get rid of this and implement it properly!
     *
     * @param string $name
     * @return string
     */
    private function stripPrefix($name)
    {
        return implode(':', array_slice(explode(':', $name), -1));
    }

    private function generateServices()
    {
        if (empty($this->phpTypes)) {
            $this->loadTypes();
        }

        $this->serviceCollection = new ServiceCollection($this->classPrefix, $this->namespace, $this->complexTypeNames);

        // TODO: this method needs a lot more logic! Assumes a single port per service
        foreach ($this->xpath->query('//wsdl:service') as $serviceElement) {
            /** @var \DOMElement $serviceElement */
            $serviceName = $serviceElement->getAttribute('name');
            $service = new Service($serviceName, $this->classPrefix);
            $this->serviceCollection->addService($service);

            /** @var \DOMElement $port */
            $port = $this->xpath->query('.//wsdl:port', $serviceElement)->item(0);
            $bindingName = $this->stripPrefix($port->getAttribute('binding'));
            /** @var \DOMElement $binding */
            $binding = $this->xpath->query('//wsdl:binding[@name = "' . $bindingName . '"]')->item(0);
            $portTypeName = $this->stripPrefix($binding->getAttribute('type'));
            $portType = $this->xpath->query('//wsdl:portType[@name = "' . $portTypeName . '"]')->item(0);

            foreach ($this->xpath->query('.//wsdl:operation', $portType) as $operationElement) {
                /** @var \DOMElement $operationElement */
                $operationName = $operationElement->getAttribute('name');
                $operation = new Operation($operationName);
                $service->addOperation($operation);

                /** @var \DOMElement $input */
                /** @var \DOMElement $message */
                $input = $this->xpath->query('.//wsdl:input', $operationElement)->item(0);
                $messageName = $this->stripPrefix($input->getAttribute('message'));
                foreach ($this->xpath->query('//wsdl:message[@name = "' . $messageName . '"]/wsdl:part') as $part) {
                    /** @var \DOMElement $part */
                    $argName = $part->getAttribute('name');
                    $argTypeFQTN = $this->resolveTypeRef($part, 'element')->getFQTN();
                    if (!isset($this->phpTypes[$argTypeFQTN])) {
                        throw new RuntimeException('Expected type ' . $argTypeFQTN . ' was not defined in the document');
                    }

                    $operation->addArgument($argName, $this->phpTypes[$argTypeFQTN]);
                }

                /** @var \DOMElement $output */
                $output = $this->xpath->query('.//wsdl:output', $operationElement)->item(0);
                $messageName = $this->stripPrefix($output->getAttribute('message'));
                $part = $this->xpath->query('//wsdl:message[@name = "' . $messageName . '"]/wsdl:part')->item(0);
                $returnTypeFQTN = $this->resolveTypeRef($part, 'element')->getFQTN();
                if (!isset($this->phpTypes[$returnTypeFQTN])) {
                    throw new RuntimeException('Expected type ' . $returnTypeFQTN . ' was not defined in the document');
                }
                $operation->setReturnType($this->phpTypes[$returnTypeFQTN]);
            }
        }
    }

    /**
     * @return DerivedClass[]
     */
    public function getClasses()
    {
        if (!$this->classes) {
            $this->generateClasses();
        }

        return $this->classes;
    }

    /**
     * @return ServiceCollection
     */
    public function getServiceCollection()
    {
        if (!isset($this->serviceCollection)) {
            $this->generateServices();
        }

        return $this->serviceCollection;
    }
}
