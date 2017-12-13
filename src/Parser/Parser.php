<?php declare(strict_types=1);

namespace DaveRandom\WsdlDistiller\Parser;

use DaveRandom\WsdlDistiller\Parser\Exceptions\InvalidDocumentException;
use DaveRandom\WsdlDistiller\Parser\Exceptions\LoadErrorException;
use DaveRandom\WSDLDistiller\Parser\Exceptions\MissingElementException;
use DaveRandom\WsdlDistiller\Parser\Exceptions\ParseErrorException;
use DaveRandom\WsdlDistiller\WSDL;
use DaveRandom\XsdDistiller\Parser\Exceptions\ParseErrorException as XsdParseErrorException;
use DaveRandom\XsdDistiller\Parser\Parser as XsdParser;
use DaveRandom\XsdDistiller\Schema;
use Room11\DOMUtils\LibXMLFatalErrorException;
use const DaveRandom\WsdlDistiller\RESOURCES_ROOT_DIR;
use const DaveRandom\WsdlDistiller\WSDL_SCHEMA_URI;
use const DaveRandom\XsdDistiller\XML_SCHEMA_URI;

final class Parser
{
    private $xsdParser;

    /*
    private static function stripPrefix(string $name): string
    {
        // TODO: Get rid of this and implement it properly!
        return \implode(':', \array_slice(\explode(':', $name), -1));
    }

    /*
     * @param ParsingContext $ctx
     * @return ServiceCollection
     * @throws InvalidReferenceException
     * @throws MissingDefinitionException
     * /
    private function generateServices(ParsingContext $ctx): ServiceCollection
    {
        $result = new ServiceCollection($ctx->classPrefix, $ctx->namespace, $ctx->complexTypeNames);

        // todo: support multiple ports per service
        foreach ($ctx->xpath->query('//wsdl:service') as $serviceElement) {
            /** @var \DOMElement $serviceElement * /
            $serviceName = $serviceElement->getAttribute('name');
            $service = new Service($serviceName, $ctx->classPrefix);
            $result->addService($service);

            /** @var \DOMElement $port * /
            $port = $ctx->xpath->query('.//wsdl:port', $serviceElement)->item(0);
            $bindingName = self::stripPrefix($port->getAttribute('binding'));

            /** @var \DOMElement $binding * /
            $binding = $ctx->xpath->query('//wsdl:binding[@name = "' . $bindingName . '"]')->item(0);
            $portTypeName = self::stripPrefix($binding->getAttribute('type'));
            $portType = $ctx->xpath->query('//wsdl:portType[@name = "' . $portTypeName . '"]')->item(0);

            foreach ($ctx->xpath->query('.//wsdl:operation', $portType) as $operationElement) {
                /** @var \DOMElement $operationElement * /
                $operationName = $operationElement->getAttribute('name');
                $operation = new Operation($operationName);
                $service->addOperation($operation);

                /** @var \DOMElement $message * /
                $input = $ctx->xpath->query('.//wsdl:input', $operationElement)->item(0);
                $messageName = self::stripPrefix($input->getAttribute('message'));
                foreach ($ctx->xpath->query('//wsdl:message[@name = "' . $messageName . '"]/wsdl:part') as $part) {
                    /** @var \DOMElement $part * /
                    $argName = $part->getAttribute('name');
                    $argTypeFQN = $this->resolveTypeRef($ctx, $part, 'element')->getFullyQualifiedName();
                    if (!isset($ctx->phpTypes[$argTypeFQN])) {
                        throw new MissingDefinitionException("Expected type {$argTypeFQN} was not defined in the document");
                    }

                    $operation->addArgument($argName, $ctx->phpTypes[$argTypeFQN]);
                }

                /** @var \DOMElement $output * /
                $output = $ctx->xpath->query('.//wsdl:output', $operationElement)->item(0);
                $messageName = self::stripPrefix($output->getAttribute('message'));
                $part = $ctx->xpath->query('//wsdl:message[@name = "' . $messageName . '"]/wsdl:part')->item(0);
                $returnTypeFQN = $this->resolveTypeRef($ctx, $part, 'element')->getFullyQualifiedName();

                if (!isset($ctx->phpTypes[$returnTypeFQN])) {
                    throw new MissingDefinitionException("Expected type {$returnTypeFQN} was not defined in the document");
                }

                $operation->setReturnType($ctx->phpTypes[$returnTypeFQN]);
            }
        }

        return $result;
    }
    */

    public function __construct()
    {
        $this->xsdParser = new XsdParser;
    }

    /**
     * @param \DOMDocument $document
     * @throws InvalidDocumentException
     */
    private function validateDocument(\DOMDocument $document): void
    {
        if ($document->documentElement->namespaceURI !== WSDL_SCHEMA_URI || $document->documentElement->localName !== 'definitions') {
            throw new InvalidDocumentException(
                'Root element of WSDL must be <definitions> in the ' . WSDL_SCHEMA_URI . ' namespace'
            );
        }

        if (!$document->schemaValidate(RESOURCES_ROOT_DIR . '/wsdl.xsd')) {
            throw new InvalidDocumentException("Schema validation failed");
        }
    }

    /**
     * @param ParsingContext $ctx
     * @return Schema
     * @throws MissingElementException
     * @throws XsdParseErrorException
     */
    private function parseTypes(ParsingContext $ctx): Schema
    {
        $schemaNode = $ctx->xpath->query('/wsdl:types/xs:schema');

        if ($schemaNode->length !== 1) {
            throw new MissingElementException('XML schema element for types missing');
        }

        return $this->xsdParser->parseDocumentFragment($schemaNode->item(0));
    }

    /**
     * @param \DOMDocument $document
     * @return WSDL
     * @throws ParseErrorException
     */
    public function parseDocument(\DOMDocument $document): WSDL
    {
        $this->validateDocument($document);

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('xs', XML_SCHEMA_URI);
        $xpath->registerNamespace('wsdl', WSDL_SCHEMA_URI);

        $ctx = new ParsingContext($document, $xpath);

        try {
            $schema = $this->parseTypes($ctx);
        } catch (XsdParseErrorException $e) {
            throw new InvalidDocumentException("Error parsing XSD schema in types element");
        }

        //todo
        //$services = $this->generateServices($ctx);

        return new WSDL($schema);
    }

    /**
     * @param string $xml
     * @return WSDL
     * @throws ParseErrorException
     * @throws LoadErrorException
     */
    public function parseXml(string $xml): WSDL
    {
        try {
            return $this->parseDocument(\Room11\DOMUtils\domdocument_load_xml($xml));
        } catch (LibXMLFatalErrorException $e) {
            throw new LoadErrorException("Unable to load WSDL: Error parsing document: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    /**
     * @param string $path
     * @return WSDL
     * @throws ParseErrorException
     * @throws LoadErrorException
     */
    public function parsePath(string $path): WSDL
    {
        $doc = new \DOMDocument();

        if (!$doc->load($path)) {
            throw new LoadErrorException('Unable to load WSDL: Could not retrieve document from supplied path');
        }

        return $this->parseDocument($doc);
    }
}
