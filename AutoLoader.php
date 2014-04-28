<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 24.04.14 at 18:09
 */
 namespace samson\core;

/**
 * Generic SamsonPHP class auto loader
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 * @version 0.1.0
 */
class AutoLoader 
{
    /** Namespace separator character marker */
    const NS_SEPARATOR = '\\';

    /** Module files cache for optimizing */
    protected static $fileCache = array();

    /**
     * Generate "correct" class name dependently on the current PHP version,
     * if PHP version is lower 5.3.0 it does not supports namespaces and function
     * will convert class name with namespace to class name
     *
     * @param string $className Full class name with namespace
     *
     * @return string Supported class name
     */
    public static function className($className)
    {
        // If this is an old PHP version - remove namespaces
        if (__SAMSON_PHP_OLD === true) {

            // If class name has first symbol as NS_SEPARATOR - remove it to avoid ugly classes _samson_core_...
            if ($className{0} == self::NS_SEPARATOR) {
                $className = substr($className, 1);
            }

            // Convert NS_SEPARATOR to namespace
            $className = strtolower(str_replace(self::NS_SEPARATOR, '_', $className));
        }

        return $className;
    }

    /**
     * Return only class name without namespace
     *
     * @param string $className Full class name with namespace
     *
     * @return string Class name without namespace
     */
    public static function getOnlyClass( $className )
    {
        // Try to find namespace separator
        if (($p = strrpos( $className, self::NS_SEPARATOR )) !== false ) {
            $className = substr( $className, $p + 1 );
        }

        return $className;
    }

    /**
     * Return only namespace name from class name
     *
     * @param string $className Full class name with namespace
     *
     * @return string Namespace without class
     */
    public static function getOnlyNameSpace( $className )
    {
        // Try to find namespace separator
        if (($p = strrpos( $className, self::NS_SEPARATOR )) !== false ) {
            $className = substr( $className, 0, $p);
        }

        return $className;
    }

    /**
     * All our modules do not follow PSR-0 and classes located as they wish to, so we will have
     * to scan all files in module location and build all classes tree.
     *
     * @param      $className
     * @param      $nameSpace
     * @param null $file
     *
     * @return bool
     */
    protected static function oldModule($className, $nameSpace, & $file = null)
    {
        // Convert to linux path, windows will convert it automatically if necessary
        $ns = str_replace(self::NS_SEPARATOR, '/', $nameSpace);

        // Build all possible module location for backward compatibility
        $path1 = __SAMSON_VENDOR_PATH.str_replace('samson/', 'samsonos/', $ns);
        $path2 = __SAMSON_VENDOR_PATH.str_replace('samson/', 'samsonos/php/', $ns);
        $path3 = __SAMSON_VENDOR_PATH.str_replace('samson/', 'samsonos/php_', $ns);

        if (file_exists($path1)) {
            $path = $path1;
        } else if (file_exists($path2)) {
            $path = $path2;
        } else if (file_exists($path3)) {
            $path = $path3;
        } else {
            return e('Class location ## not found by namespace ##', E_SAMSON_CORE_ERROR, array($className, $ns));
        }

        $path .= '/';

        // Build files tree once for each module path
        if (!isset(self::$fileCache[$path])) {
            self::$fileCache[$path] = File::dir($path, 'php');
        }

        // Simple method - trying to find class by class name
        if ($files = preg_grep( '/\/'.$className.'\.php/i', self::$fileCache[$path])) {

            // Проверим на однозначность
            if (sizeof($files) > 1) {
                return e('Cannot autoload class(##), too many files matched ##', E_SAMSON_CORE_ERROR, array($className,$files) );
            }

            // Return last array element as file path
            $file = end($files);

            // Everything is OK
            return true;
        }

        return false;
    }

    /**
     * Auto loader main logic
     *
     * @param string $class Full class name with namespace
     *
     * @return bool False if something went wrong
     */
    public static function load($class)
    {
        // Get just class name without ns
        $className = self::getOnlyClass($class);
        // Get just ns without class name
        $nameSpace = self::getOnlyNameSpace($class );

        // If this is not core class
        if ($nameSpace != __NAMESPACE__) {
            // Convert namespace to base directory add class
            $baseFolder = str_replace('\\', DIRECTORY_SEPARATOR, $nameSpace).DIRECTORY_SEPARATOR;

            echo('loading class for file:'.$class.'-'.$className.'('.__SAMSON_VENDOR_PATH.$baseFolder.$className.'.php'.')<br>');

            // Load class by file name
            if (file_exists(__SAMSON_VENDOR_PATH.$baseFolder.$className.'.php')) {
                require(__SAMSON_VENDOR_PATH.$baseFolder.$className.'.php');
            // old school compatibility will removed when old modules will be updated
            } else if(self::oldModule($className, $nameSpace, $path)) {
                require($path);
            } else { // Signal error
                return e('Class name ## not found', E_SAMSON_CORE_ERROR, $class);
            }

        } else { // Load core classes separately using this class location
            require(__DIR__.'/'.$className.'.php');
        }
    }
}

// Set this loader
spl_autoload_register(array('\samson\core\AutoLoader','load'));
 