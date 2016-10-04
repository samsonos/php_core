<?php declare(strict_types=1);

namespace samsonphp\core\loader;

use Doctrine\Common\Annotations\AnnotationReader;
use samson\activerecord\dbMySQLConnector;
use samson\core\CompressableExternalModule;
use samson\core\CompressableService;
use samson\core\Core;
use samson\core\ExternalModule;
use samson\core\VirtualModule;
use samsonframework\container\definition\analyzer\annotation\annotation\InjectClass;
use samsonframework\container\definition\analyzer\annotation\annotation\InjectParameter;
use samsonframework\container\definition\analyzer\annotation\annotation\InjectService;
use samsonframework\container\definition\analyzer\annotation\annotation\Service;
use samsonframework\container\definition\analyzer\annotation\AnnotationClassAnalyzer;
use samsonframework\container\definition\analyzer\annotation\AnnotationMethodAnalyzer;
use samsonframework\container\definition\analyzer\annotation\AnnotationPropertyAnalyzer;
use samsonframework\container\definition\analyzer\DefinitionAnalyzer;
use samsonframework\container\definition\analyzer\reflection\ReflectionClassAnalyzer;
use samsonframework\container\definition\analyzer\reflection\ReflectionMethodAnalyzer;
use samsonframework\container\definition\analyzer\reflection\ReflectionParameterAnalyzer;
use samsonframework\container\definition\analyzer\reflection\ReflectionPropertyAnalyzer;
use samsonframework\container\definition\builder\DefinitionBuilder;
use samsonframework\container\definition\builder\DefinitionCompiler;
use samsonframework\container\definition\builder\DefinitionGenerator;
use samsonframework\container\definition\ClassDefinition;
use samsonframework\container\definition\exception\MethodDefinitionAlreadyExistsException;
use samsonframework\container\definition\parameter\ParameterBuilder;
use samsonframework\container\definition\reference\ClassReference;
use samsonframework\container\definition\reference\ServiceReference;
use samsonframework\container\definition\reference\StringReference;
use samsonframework\container\definition\resolver\xml\XmlResolver;
use samsonframework\container\definition\scope\ModuleScope;
use samsonframework\container\definition\scope\ServiceScope;
use samsonframework\core\PreparableInterface;
use samsonframework\di\Container;
use samsonframework\generator\ClassGenerator;
use samsonframework\resource\ResourceMap;
use samsonphp\core\loader\module\Module;
use samsonphp\core\loader\module\ModuleManagerInterface;
use samsonphp\event\Event;
use samsonphp\i18n\i18n;

/**
 * Class CoreLoader
 *
 * @package samsonphp\container\loader
 */
class CoreLoader
{
    /** @var ModuleManagerInterface Module manager */
    protected $moduleManager;
    /** @var ContainerManager Container manager */
    public $containerManager;

    /**
     * CoreLoader constructor.
     *
     * @param ModuleManagerInterface $moduleManager
     * @param ContainerManager $containerManager
     */
    public function __construct(
        ModuleManagerInterface $moduleManager,
        ContainerManager $containerManager
    ) {
        $this->moduleManager = $moduleManager;
        $this->containerManager = $containerManager;
    }

    /**
     * Load modules
     *
     * @throws \Exception
     */
    public function init()
    {
        $containerPath = __DIR__ . '/../../../../../www/cache';
        $containerName = 'ContainerCore';
        $containerNamespace = 'samsonphp\core\loader';
        /** @var Module $module */
        $modules = $this->moduleManager->getRegisteredModules();

        $localModulesPath = '../src';
        ResourceMap::get('cache');
        $resourceMap = ResourceMap::get($localModulesPath);
        $localModules = $resourceMap->modules;

        if (false || !file_exists($containerPath . '/' . $containerName . '.php')) {

            $builder = new DefinitionBuilder(new ParameterBuilder());
            $xmlResolver = new XmlResolver();
            $xmlResolver->resolveFile($builder, __DIR__ . '/../../../../../app/config/config.xml');

            new Service('');
            new InjectService('');
            new InjectClass('');
            new InjectParameter('');

            foreach ($modules as $module) {
                if ($module->className && !$builder->hasDefinition($module->className)) {
                    // Fix samson.php files
                    if (!class_exists($module->className)) {
                        require_once($module->pathName);
                    }
                    /** @var ClassDefinition $classDefinition */
                    $classDefinition = $builder->addDefinition($module->className);
                    if ($id = $this->getModuleId($module->pathName)) {
                        $classDefinition->setServiceName($id);
                    } else {
                        // Generate identifier from module class
                        $classDefinition->setServiceName(
                            strtolower(ltrim(str_replace(__NS_SEPARATOR__, '_', $module->className), '_'))
                        );
                    }
                    $classDefinition->addScope(new ModuleScope())->setIsSingleton(true);
                    $this->defineConstructor($classDefinition, $module->path);
                }
            }

            $classDefinition = $builder->addDefinition(VirtualModule::class);
            $classDefinition->addScope(new ModuleScope())->setServiceName('local')->setIsSingleton(true);
            $this->defineConstructor($classDefinition, getcwd());

            foreach ($localModules as $moduleFile) {
                if (!$builder->hasDefinition($moduleFile[0])) {

                    /** @var ClassDefinition $classDefinition */
                    $classDefinition = $builder->addDefinition($moduleFile[0]);
                    $classDefinition->addScope(new ModuleScope());
                    $classDefinition->setIsSingleton(true);
                    if ($id = $this->getModuleId($moduleFile[1])) {
                        $classDefinition->setServiceName($id);
                    } else {
                        throw new \Exception('Can not get id of local module');
                    }

                    $modulePath = explode('/', str_replace(realpath($localModulesPath), '', $moduleFile[1]));
                    $this->defineConstructor($classDefinition, $localModulesPath . '/' . $modulePath[1]);
                }
            }


            /**
             * Add implementors
             */
            foreach ($this->moduleManager->implements as $interfaceName => $class) {
                $builder->defineImplementors($interfaceName, new ClassReference($class));
            }

            // Init compiler
            $reader = new AnnotationReader();
            $compiler = new DefinitionCompiler(
                new DefinitionGenerator(new ClassGenerator()),
                (new DefinitionAnalyzer())
                    ->addClassAnalyzer(new AnnotationClassAnalyzer($reader))
                    ->addClassAnalyzer(new ReflectionClassAnalyzer())
                    ->addMethodAnalyzer(new AnnotationMethodAnalyzer($reader))
                    ->addMethodAnalyzer(new ReflectionMethodAnalyzer())
                    ->addPropertyAnalyzer(new AnnotationPropertyAnalyzer($reader))
                    ->addPropertyAnalyzer(new ReflectionPropertyAnalyzer())
                    ->addParameterAnalyzer(new ReflectionParameterAnalyzer())
            );

            $container = $compiler->compile($builder, $containerName, $containerNamespace, $containerPath);

        } else {

            $containerClassName = $containerNamespace. '\\' . $containerName;
            require_once($containerPath . '/' . $containerName . '.php');
            $container = new $containerClassName();
        }

        $GLOBALS['__core'] = $container->get('core');

        $this->prepareModules($modules, $container);

        /** @var array $module */
        foreach ($localModules as $module) {
            $instance = $container->get($module[0]);
            $instance->parent = $this->getClassParentModule($container, get_parent_class($instance));
        }

//        $container->get('core')->active($container->get('local'));

        return $container;
    }

    /**
     * Prepare modules
     *
     * @param array $modules
     * @param $container
     */
    protected function prepareModules(array $modules, $container)
    {
        foreach ($modules as $module) {
            $identifier = $module->name;
            if ($module->className) {
                // Fix samson.php files
                if (!class_exists($module->className)) {
                    require_once($module->pathName);
                }
                $instance = $container->get($module->className);
            } else {
                continue;
            }

            // Set composer parameters
            $instance->composerParameters = $module->composerParameters;

            // TODO: Change event signature to single approach
            // Fire core module load event
            Event::fire('core.module_loaded', [$identifier, &$instance]);

            // Signal core module configure event
            Event::signal('core.module.configure', [&$instance, $identifier]);

            if ($instance instanceof PreparableInterface) {
                // Call module preparation handler
                if (!$instance->prepare()) {
//                    throw new \Exception($identifier.' - Module preparation stage failed');
                }
            }

            $instance->parent = $this->getClassParentModule($container, get_parent_class($instance));
        }
    }

    /**
     * Define constructor
     *
     * @param ClassDefinition $classDefinition
     * @param $path
     * @throws MethodDefinitionAlreadyExistsException
     */
    protected function defineConstructor(ClassDefinition $classDefinition, $path)
    {
        $classDefinition->defineConstructor()
            ->defineParameter('path')
                ->defineDependency(new StringReference($path))
            ->end()
        ->end();
    }

    /**
     * Get module id
     *
     * @param $filePath
     * @return string|bool
     */
    protected function getModuleId($filePath)
    {
        preg_match(
            '/.*(protected|public|private)\s\$id\s=\s(\'|\")(?P<id>.*)(\'|\")\;.*/',
            file_get_contents($filePath),
            $matches
        );

        if (array_key_exists('id', $matches) && isset($matches['id']{0})) {
            return $matches['id'];
        } else {
            return false;
        }
    }

    /**
     * Find parent module by OOP class inheritance.
     *
     * @param string $className Class name for searching parent modules
     * @param array  $ignoredClasses Collection of ignored classes
     *
     * @return null|mixed Parent service instance if present
     */
    protected function getClassParentModule(
        Container $container,
        $className,
        array $ignoredClasses = [
            ExternalModule::class,
            CompressableExternalModule::class,
            \samson\core\Service::class,
            CompressableService::class
        ]
    ) {
        // Skip ignored class names
        if (!in_array($className, $ignoredClasses, true)) {
            try {
                $instance = $container->get(trim($className));
                $instance->parent = $this->getClassParentModule($container, get_parent_class($instance));
                return $instance;
            } catch (\Exception $exception) {
                return null;
            }
//            // Iterate loaded services
//            foreach ($container->getServices('module') as $service) {
//                if (get_class($service) === $className) {
//                    return $service;
//                }
//            }
        }
        return null;
    }
}
