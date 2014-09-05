<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 24.07.14 at 17:06
 */
namespace samson\core;

/**
 * Generic class to manage all web-application resources
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 * @version 1.0.0
 */
class ResourceMap
{

    /** Number of lines to read in file to determine its PHP class */
    const CLASS_FILE_LINES_LIMIT = 50;

    /** @var array Collection of classes that are Module ancestors */
    public static $moduleAncestors = array(
        '\samson\core\CompressableExternalModule' => 'CompressableExternalModule',
        '\samson\core\ExternalModule' => 'ExternalModule',
        '\samson\core\Service' => 'Service',
        '\samson\core\CompressableService' => 'CompressableService'
    );

    /** @var ResourceMap[] Collection of ResourceMaps gathered by entry points */
    public static $gathered = array();

    /**
     * Try to find ResourceMap by entry point
     *
     * @param string        $entryPoint Path to search for ResourceMap
     * @param ResourceMap   $pointer    Variable where found ResourceMap will be returned
     *
     * @return bool True if ResourceMap is found for this entry point
     */
    public static function find($entryPoint, & $pointer = null)
    {
        // Pointer to find ResourceMap for this entry point
        $tempPointer = & self::$gathered[$entryPoint];

        // If we have already build resource map for this entry point
        if (isset($tempPointer)) {
            // Return pointer value
            $pointer = $tempPointer;

            return true;
        }

        return false;
    }

    /**
     * Find ResourceMap by entry point or create a new one
     * @param string $entryPoint    Path to search for ResourceMap
     * @param bool  $force          Flag to force rebuilding Resource map from entry point
     * @return ResourceMap Pointer to ResourceMap object for passed entry point
     */
    public static function & get($entryPoint, $force = false)
    {
        /** @var ResourceMap $resourceMap Pointer to resource map*/
        $resourceMap = null;

        // If we have not already scanned this entry point or not forced to do it again
        if (!self::find($entryPoint, $resourceMap)) {
            // Create new resource map for this entry point
            $resourceMap = new ResourceMap($entryPoint);

            // Build ResourceMap for this entry point
            $resourceMap->build($entryPoint);

        } elseif ($force) { // If we have found ResourceMap for this entry point but we forced to rebuild it
            $resourceMap->build($entryPoint);
        }

        return $resourceMap;
    }

    /** @var string  Resource map entry point */
    public $entryPoint;

    /** @var array Collection of gathered resources grouped by extension */
    public $resources = array();

    /** @var  array Collection of controllers actions by entry point */
    public $controllers = array();

    /** @var string Path to \samson\core\Module ancestor */
    public $module = array();

    /** @var  array Collection of old-fashion global namespace module files by entry point */
    public $globals = array();

    /** @var  array Old-fashion model files collection by entry point */
    public $models = array();

    /** @var  array Collection of views by entry point */
    public $views = array();

    /** @var  array Collection of classes by entry point */
    public $classes = array();

    /** @var array Collection of CSS resources */
    public $css = array();

    /** @var array Collection of LESS resources */
    public $less = array();

    /** @var array Collection of SASS resources */
    public $sass = array();

    /** @var array Collection of JS resources */
    public $js = array();

    /** @var array Collection of other PHP resources */
    public $php = array();

    /** @var array Collection of COFFEE resources */
    public $coffee = array();

    /** @var array Collection of folders that should be ignored in anyway */
    public $ignoreFolders = array('.svn', '.git', '.idea', __SAMSON_CACHE_PATH, __SAMSON_TEST_PATH, 'vendor', __SAMSON_CONFIG_PATH, 'cms', 'contrib');

    /** @var array Collection of files that must be ignored by ResourceMap */
    public $ignoreFiles = array('phpunit.php');

    /**
     * Constructor
     *
     * @param string $entryPoint    ResourceMap entry point
     * @param array  $ignoreFolders Collection of folders to be ignored in ResourceMap
     * @param array  $ignoreFiles   Collection of files to be ignored in ResourceMap
     */
    public function __construct($entryPoint, array $ignoreFolders = null, array $ignoreFiles = null)
    {
        $this->entryPoint = $entryPoint;

        if (isset($ignoreFolders)) {
            // Combine passed folders to ignore with the default ones
            $this->ignoreFolders = array_merge($this->ignoreFolders, $ignoreFolders);
        }

        if (isset($ignoreFiles)) {
            // Combine passed files to ignore with the default ones
            $this->ignoreFiles = array_merge($this->ignoreFiles, $ignoreFiles);
        }

        // Store current ResourceMap in ResourceMaps collection
        self::$gathered[$this->entryPoint] = & $this;
    }

    /**
     * Determines if file is a class
     *
     * @param string $path  Path to file for checking
     * @param string $class Variable to return full class name with name space
     *
     * @return bool True if file is a class file
     */
    public function isClass($path, & $class = '')
    {
        // Class name space, by default - global namespace
        $namespace = '\\';
        // Open file handle for reading
        $file = fopen($path, 'r');
        // Read lines from file
        for ($i = 0; $i<self::CLASS_FILE_LINES_LIMIT; $i++) {
            // Read one line from a file
            $line = fgets($file);
            $matches = array();

            // Read one line from a file and try to find namespace definition
            if ($namespace == '\\' && preg_match('/^\s*namespace\s+(?<namespace>[^;]+)/iu', $line, $matches)) {
                $namespace .= $matches['namespace'].'\\';
                // Read one line from a file and try to find class pattern
            } elseif (preg_match('/^\s*class\s+(?<class>[a-z0-9]+)\s+extends\s+(?<parent>[a-z0-9\\\]+)/iu', $line, $matches)) {

                // Store module class name
                $class = $namespace.$matches['class'];

                // Define if this class is Module ancestor
                if (isset(self::$moduleAncestors[$matches['parent']]) || in_array($matches['parent'], self::$moduleAncestors)) {
                    // Save class as module ancestor
                    self::$moduleAncestors[$namespace.$matches['class']] = $matches['class'];
                    // Completed my sir!
                    return true;
                }

                // Add class to classes array
                $this->classes[] = $class;

                return false;
            }
        }

        return false;
    }

    /**
     * Determines if file is an SamsonPHP view file
     * @param string $path Path to file for checking
     * @return bool True if file is a SamsonPHP view file
     */
    public function isView($path)
    {
        // Try to match using old-style method by location and using new style by extension
        return strpos($path, __SAMSON_VIEW_PATH) !== false || strpos($path, '.vphp') !== false;
    }

    /**
     * Determines if file is an SamsonPHP Module Class ancestor file
     *
     * @param string $path Path to file for checking
     * @param string $class Variable to return module controller class name
     *
     * @return bool True if file is a SamsonPHP view file
     */
    public function isModule($path, & $class = '')
    {
        // If this is a .php file
        if (strpos($path, '.php') !== false && $this->isClass($path, $class)) {
            // Check if this is not a SamsonPHP core class
            if (strpos('CompressableExternalModule, ExternalModule, Service, CompressableService', str_replace('\samson\core\\', '', $class)) === false) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Determines if file is an SamsonPHP model file
     * @param string $path Path to file for checking
     * @return bool True if file is a SamsonPHP model file
     */
    public function isModel($path)
    {
        // Try to match using old-style method by location
        return strpos($path, __SAMSON_MODEL_PATH) !== false;
    }

    /**
     * Determines if file is an PHP file
     * @param string $path Path to file for checking
     * @return bool True if file is a PHP  file
     */
    public function isPHP($path)
    {
        // Just match file extension
        return strpos($path, '.php') !== false;
    }

    /**
     * Determines if file is an SamsonPHP global namespace file
     * @param string $path Path to file for checking
     * @return bool True if file is a SamsonPHP global namespace file
     */
    public function isGlobal($path)
    {
        // Check old-style by file name
        return basename($path, '.php') == 'global';
    }

    /**
     * Determines if file is an SamsonPHP controller file
     * @param string $path Path to file for checking
     * @return bool True if file is a SamsonPHP view file
     */
    public function isController($path)
    {
        // Check old-style by location and new-style function type by file name
        return strpos($path, __SAMSON_CONTROLLER_PATH) !== false || basename($path, '.php') == 'controller';
    }

    /**
     * Convert Resource map to old-style "load_stack_*" format
     * @deprecated
     * @return array Collection of resources in old format
     */
    public function toLoadStackFormat()
    {
        return array(
            'resources' => $this->resources,
            'controllers' => array_merge($this->controllers, $this->module),
            'models' => $this->models,
            'views' => $this->views,
            'php' => array_merge($this->php, $this->globals)
        );
    }

    /**
     * Perform resource gathering starting from $path entry point
     * @param string    $path   Entry point to start scanning resources
     * @return array Collection of resources grouped by resource file extension
     */
    public function build($path = null)
    {
        // If no other path is passed use current entry point and convert it to *nix path format
        $path = normalizepath(isset($path) ? $path : $this->entryPoint);

        // Store new entry point
        $this->entryPoint = $path;

        // Check for correct path and then try to get files
        if (file_exists($path)) {
            // Collect all resources from entry point
            $files = array();
            //TODO: Ignore cms folder - ignore another web-applications or not parse current root web-application path
            foreach (File::dir($this->entryPoint, null, '', $files, null, 0, $this->ignoreFolders) as $file) {

                // Check if this file does not has to be ignored
                if (!in_array(basename($file), $this->ignoreFiles)) {
                    $class = '';
                    // We can determine SamsonPHP view files by 100%
                    if ($this->isView($file)) {
                        $this->views[] = $file;
                    } elseif ($this->isGlobal($file)) {
                        $this->globals[] = $file;
                    } elseif ($this->isModel($file)) {
                        $this->models[] = $file;
                    } elseif ($this->isController($file)) {
                        $this->controllers[] = $file;
                    } elseif ($this->isModule($file, $class)) {
                        $this->module = array($class, $file);
                    } elseif ($this->isPHP($file)) {
                        $this->php[] = $file;
                    } else { // Save resource by file extension
                        // Get extension as resource type
                        $rt = pathinfo($file, PATHINFO_EXTENSION);

                        // Check if resource type array cell created
                        if (!isset($this->resources[$rt])) {
                            $this->resources[$rt] = array();
                        }

                        // Add resource to collection
                        $this->resources[$rt][] = $file;
                    }
                }
            }

            // Iterate all defined object variables
            foreach (get_object_vars($this) as $var => $value) {
                // If we have matched resources with that type
                if (isset($this->resources[$var])) {
                    // Bind object variable to resources collection
                    $this->$var = & $this->resources[$var];
                }
            }

        } else { // Signal error
            return e('Cannot build ResourceMap from ## - path does not exists', E_SAMSON_CORE_ERROR, $path);
        }
    }
}
