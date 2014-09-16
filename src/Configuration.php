<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 15.09.14 at 12:10
 */
 namespace samson\core;

/**
 * Generic SamsonPHP core configuration system
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 */
class Configuration 
{
    /** @var string Current configuration environment */
    public $environment;

    /**
     * Load configuration for specified environment.
     * All module configurators must be stored within configuration base path,
     * by default this is stored in __SAMSON_CONFIG_PATH constant.
     *
     * Every environment configuration must stored in sub-folder with the name of this
     * environment within base configuration folder.
     *
     * Configurators located at base root configuration folder considered as generic
     * module configurators.
     *
     * @param string $basePath    Base path to configuration root folder
     * @param string $environment Configuration environment title
     *
     * @return bool False if something went wrong
     */
    public function load($basePath = __SAMSON_CONFIG_PATH, $environment = null)
    {
        // Store current configuration environment
        $this->environment = $environment;

        // Build path to environment configuration folder
        $configurationPath = $basePath.'/'.(isset($environment) ? $environment.'/' : '');
        if (file_exists($configurationPath)) {
            return e('Environment(##) configuration path(##) does not exists', E_SAMSON_CORE_ERROR, array($environment, $configurationPath));
        }

        // At this point we consider that all configuration classes has been required

        // Iterate all declared classes
        foreach (get_declared_classes() as $class) {
            // If this class is Configurator ancestor
            if (is_subclass_of($class, '\samson\core\Configurator')) {
                // Lowercase class name
                $className = strtolower($class);

                // Find environment declaration in class name
                if (strpos($className, $environment) !== false) {

                    $configurator = new $class();
                }
            }
        }
    }
}
 