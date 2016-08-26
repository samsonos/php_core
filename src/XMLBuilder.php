<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 26.08.16 at 09:42
 */
namespace samson\core;

use samsonframework\container\metadata\ClassMetadata;

/**
 * Class XMLBuilder
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class XMLBuilder
{
    /**
     * Set XML property or method argument value.
     *
     * @param string      $value
     * @param \DOMElement $node
     * @param ClassMetadata[]       $classesMetadata
     */
    protected function setAttribute(string $value, \DOMElement $node, array $classesMetadata)
    {
        if (array_key_exists($value, $classesMetadata)) {
            $node->setAttribute('service', $value);
        } elseif (class_exists($value)) {
            $node->setAttribute('class', $value);
        } else {
            $node->setAttribute('value', $value);
        }
    }

    /**
     * Build class xml config from class metadata.
     *
     * TODO: Scan for existing config and change only not filled values.
     *
     * @param ClassMetadata[] $classesMetadata
     * @param string        $path Path where to store XML files
     */
    public function buildXMLConfig(array $classesMetadata, string $path)
    {
        foreach ($classesMetadata as $alias => $classMetadata) {
            $dom = new \DOMDocument("1.0", "utf-8");
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $root = $dom->createElement("dependencies");
            $dom->appendChild($root);

            // Build alias from class name if missing
            $alias = $alias ?? strtolower(str_replace('\\', '_', $classMetadata->className));

            $classData = $dom->createElement('instance');
            $classData->setAttribute('service', $alias);
            $classData->setAttribute('class', $classMetadata->className);

            foreach ($classMetadata->scopes as $scope) {
                $classData->setAttribute('scope', $scope);
            }

            $methodsData = $dom->createElement('methods');
            foreach ($classMetadata->methodsMetadata as $method => $methodMetadata) {
                if (count($methodMetadata->dependencies)) {
                    $methodData = $dom->createElement($method);
                    $argumentsData = $dom->createElement('arguments');
                    foreach ($methodMetadata->dependencies as $argument => $dependency) {
                        $argumentData = $dom->createElement($argument);
                        $this->setAttribute($dependency, $argumentData, $classesMetadata);
                        $argumentsData->appendChild($argumentData);
                    }
                    $methodData->appendChild($argumentsData);
                    $methodsData->appendChild($methodData);
                }
            }
            $classData->appendChild($methodsData);

            $propertiesData = $dom->createElement('properties');
            foreach ($classMetadata->propertiesMetadata as $property => $propertyMetadata) {
                if ($propertyMetadata->dependency !== null && $propertyMetadata->dependency !== '') {
                    $propertyData = $dom->createElement($property);
                    $this->setAttribute($propertyMetadata->dependency, $propertyData, $classesMetadata);
                    $propertiesData->appendChild($propertyData);
                }
            }

            $classData->appendChild($propertiesData);
            $root->appendChild($classData);
            $dom->save($path . $alias . '.xml');
        }
    }
}
