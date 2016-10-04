<?php declare(strict_types=1);

namespace samsonphp\core\loader;

use Doctrine\Common\Annotations\AnnotationReader;
use samson\core\Core;
use samson\core\ExternalModule;
use samsonframework\container\Builder;
use samsonframework\container\ContainerBuilderInterface;
use samsonframework\container\ContainerInterface;
use samsonframework\container\metadata\ClassMetadata;
use samsonframework\container\metadata\MethodMetadata;
use samsonframework\containerannotation\AnnotationClassResolver;
use samsonframework\containerannotation\AnnotationMetadataCollector;
use samsonframework\containerannotation\AnnotationMethodResolver;
use samsonframework\containerannotation\AnnotationPropertyResolver;
use samsonframework\containerannotation\AnnotationResolver;
use samsonframework\containerannotation\Injectable;
use samsonframework\containerannotation\InjectArgument;
use samsonframework\containerannotation\Service;
use samsonframework\containercollection\attribute\ArrayValue;
use samsonframework\containercollection\attribute\ClassName;
use samsonframework\containercollection\attribute\Name;
use samsonframework\containercollection\attribute\Scope;
use samsonframework\containercollection\attribute\Value;
use samsonframework\containercollection\CollectionClassResolver;
use samsonframework\containercollection\CollectionMethodResolver;
use samsonframework\containercollection\CollectionParameterResolver;
use samsonframework\containercollection\CollectionPropertyResolver;
use samsonframework\containerxml\XmlMetadataCollector;
use samsonframework\containerxml\XmlResolver;
use samsonframework\core\SystemInterface;
use samsonframework\resource\ResourceMap;

/**
 * Class ContainerLoader
 *
 * @package samsonphp\container\loader
 * TODO Change class name
 */
class ContainerManager
{
    /** @var ContainerBuilderInterface Container builder */
    protected $containerBuilder;
    /** @var string Application path */
    protected $applicationPath;
    /** @var string Config path */
    protected $configPath;
    /** @var string Cache path */
    protected $cachePath;

    /**
     * ContainerLoader constructor.
     *
     * @param ContainerBuilderInterface $containerBuilder
     * @param string $applicationPath
     * @param string $configPath
     * @param string $cachePath
     */
    public function __construct(
        ContainerBuilderInterface $containerBuilder,
        string $applicationPath,
        string $configPath,
        string $cachePath
    ) {
        $this->containerBuilder = $containerBuilder;
        $this->applicationPath = $applicationPath;
        $this->configPath = $applicationPath . $configPath;
        $this->cachePath = $applicationPath . $cachePath;
    }

    /**
     * Get xml metadata collection
     *
     * @param array $metadataCollection
     * @return array
     * @throws \Exception
     */
    public function collectXmlMetadata(array $metadataCollection) : array
    {
        // If config path is exists
        if (file_exists($this->configPath)) {
            // Init resolver
            $xmlConfigurator = new XmlResolver(new CollectionClassResolver([
                Scope::class,
                Name::class,
                ClassName::class,
                \samsonframework\containercollection\attribute\Service::class
            ]), new CollectionPropertyResolver([
                ClassName::class,
                Value::class
            ]), new CollectionMethodResolver([], new CollectionParameterResolver([
                ClassName::class,
                Value::class,
                ArrayValue::class,
                \samsonframework\containercollection\attribute\Service::class
            ])));
            // Collect new metadata
            $xmlCollector = new XmlMetadataCollector($xmlConfigurator);
            return $xmlCollector->collect(file_get_contents($this->configPath), $metadataCollection);
        }
        return $metadataCollection;
    }

    /**
     * Get default property value by property name
     *
     * @param $className
     * @param $propertyName
     * @return null
     */
    public function getDefaultPropertyValue($className, $propertyName)
    {
        $reflection = new \ReflectionClass($className);
        $values = $reflection->getDefaultProperties();
        return $values[$propertyName] ?? null;
    }

    /**
     * Create metadata for module
     *
     * @param $class
     * @param $name
     * @param $path
     * @param string $scope
     * @return ClassMetadata
     */
    public function createMetadata($class, $name, $path, $scope = 'module') : ClassMetadata
    {
        $metadata = new ClassMetadata();
        $class = ltrim($class, '\\');
        $name = strtolower(ltrim($name, '\\'));
        $metadata->className = $class;
        $metadata->name = str_replace(['\\', '/'], '_', $name ?? $class);
        $metadata->scopes[] = Builder::SCOPE_SERVICES;
        $metadata->scopes[] = $scope;

        // TODO: Now we need to remove and change constructors
        $metadata->methodsMetadata['__construct'] = new MethodMetadata($metadata);

        // Iterate constructor arguments to preserve arguments order and inject dependencies
        foreach ((new \ReflectionMethod($class, '__construct'))->getParameters() as $parameter) {
            if ($parameter->getName() === 'path') {
                $metadata->methodsMetadata['__construct']->dependencies['path'] = $path;
            } elseif ($parameter->getName() === 'resources') {
                $metadata->methodsMetadata['__construct']->dependencies['resources'] = ResourceMap::class;
            } elseif ($parameter->getName() === 'system') {
                $metadata->methodsMetadata['__construct']->dependencies['system'] = Core::class;
            } elseif (!$parameter->isOptional()) {
                $metadata->methodsMetadata['__construct']->dependencies[$parameter->getName()] = '';
            }
        }
        return $metadata;
    }

    /**
     * Replace interface dependency on their implementation
     *
     * @param array $metadataCollection
     */
    public function replaceInterfaceDependencies(array $metadataCollection) {

        $list = [];
        $listPath = [];
        /** @var ClassMetadata $classMetadata */
        foreach ($metadataCollection as $classPath => $classMetadata) {
            $list[$classMetadata->className] = $classMetadata;
            $listPath[$classMetadata->name ?? $classMetadata->className] = $classPath;
        }
        $metadataCollection = $list;

        // Gather all interface implementations
        $implementsByAlias = $implementsByAlias ?? [];
        foreach (get_declared_classes() as $class) {
            $classImplements = class_implements($class);
            foreach (get_declared_interfaces() as $interface) {
                if (in_array($interface, $classImplements, true)) {
                    if (array_key_exists($class, $metadataCollection)) {
                        $implementsByAlias[$interface][] = $metadataCollection[$class]->name;
                    }
                }
            }
        }

        // Gather all class implementations
        $serviceAliasesByClass = $serviceAliasesByClass ?? [];
        foreach (get_declared_classes() as $class) {
            if (array_key_exists($class, $metadataCollection)) {
                $serviceAliasesByClass[$class][] = $metadataCollection[$class]->name;
            }
        }

        /**
         * TODO: now we need to implement not forcing to load fixed dependencies into modules
         * to give ability to change constructors and inject old variable into properties
         * and them after refactoring remove them. With this we can only specify needed dependencies
         * in new modules, and still have old ones working.
         */

        foreach ($metadataCollection as $alias => $metadata) {
            foreach ($metadata->propertiesMetadata as $property => $propertyMetadata) {
                if (is_string($propertyMetadata->dependency)) {
                    $dependency = $propertyMetadata->dependency;
                    if (array_key_exists($dependency, $implementsByAlias)) {
                        $propertyMetadata->dependency = $implementsByAlias[$dependency][0];
                    } elseif (array_key_exists($dependency, $serviceAliasesByClass)) {
                        $propertyMetadata->dependency = $serviceAliasesByClass[$dependency][0];
                    } else {

                    }
                }
            }

            // Iterate constructor arguments to preserve arguments order and inject dependencies
            $reflectionClass = new \ReflectionClass($metadata->className);
            // Check if instance has a constructor or it instance of external module
            if ($reflectionClass->hasMethod('__construct') && is_subclass_of($metadata->className, ExternalModule::class)) {
                foreach ((new \ReflectionMethod($metadata->className, '__construct'))->getParameters() as $parameter) {
                    if ($parameter->getName() === 'path') {
                        $metadata->methodsMetadata['__construct']->dependencies['path'] = dirname($listPath[$metadata->name ?? $metadata->className]);
                    } elseif ($parameter->getName() === 'resources') {
                        $metadata->methodsMetadata['__construct']->dependencies['resources'] = ResourceMap::class;
                    } elseif ($parameter->getName() === 'system') {
                        $metadata->methodsMetadata['__construct']->dependencies['system'] = 'core';
                    }
                }
            }

            foreach ($metadata->methodsMetadata as $method => $methodMetadata) {
                foreach ($methodMetadata->dependencies as $argument => $dependency) {
                    if (is_string($dependency)) {
                        if (array_key_exists($dependency, $implementsByAlias)) {
                            $methodMetadata->dependencies[$argument] = $implementsByAlias[$dependency][0];
                            //$methodMetadata->parametersMetadata[$argument]->dependency = $implementsByAlias[$dependency][0];
                        } elseif (array_key_exists($dependency, $serviceAliasesByClass)) {
                            $methodMetadata->dependencies[$argument] = $serviceAliasesByClass[$dependency][0];
                            //$methodMetadata->parametersMetadata[$argument]->dependency = $serviceAliasesByClass[$dependency][0];
                        } else {

                        }
                    }
                }
            }
        }
    }

    /**
     * @param ClassMetadata[] $metadataCollection
     * @param string $containerName
     * @param ContainerInterface $parentContainer
     * @return ContainerInterface
     */
    public function createContainer(array $metadataCollection, $containerName = 'Container', ContainerInterface $parentContainer = null) : ContainerInterface
    {
        $containerPath = $this->cachePath . $containerName . '.php';
        file_put_contents($containerPath, $this->containerBuilder->build($metadataCollection, $containerName, '', $parentContainer));
        require_once($containerPath);

        return new $containerName();
    }

    /**
     * Get annotation metadata
     *
     * @param $classes
     * @param $metadataCollection
     * @return array|\samsonframework\container\metadata\ClassMetadata[]
     */
    public function collectAnnotationMetadata($classes, $metadataCollection)
    {
        // Load annotation and parse classes
        new Injectable();
        new InjectArgument(['var' => 'type']);
        new Service(['value' => '']);

        $reader = new AnnotationReader();
        $resolver = new AnnotationResolver(
            new AnnotationClassResolver($reader),
            new AnnotationPropertyResolver($reader),
            new AnnotationMethodResolver($reader)
        );

        $annotationCollector = new AnnotationMetadataCollector($resolver);

        // Rewrite collection by entity name
        return $annotationCollector->collect($classes, $metadataCollection);
    }

    /**
     * Convert class name to alias
     *
     * @param $className
     * @return string
     */
    public function classNameToAlias(string $className) : string
    {
        return str_replace(['\\', '/'], '_', $className);
    }
}
