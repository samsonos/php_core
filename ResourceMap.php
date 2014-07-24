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
        $pointer = & self::$gathered[$entryPoint];

        // If we have already build resource map for this entry point
        if (isset($pointer)) {
            // Return existing ResourceMap
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

        } else if($force){ // If we have found ResourceMap for this entry point but we forced to rebuild it
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

    /** @var array Collection of COFFEE resources */
    public $coffee = array();

    /** @var array Collection of folders that should be ignored in anyway */
    public $ignoreFolders = array('.svn', '.git', '.idea', __SAMSON_CACHE_PATH);

    /**
     * Constructor
     * @param string    $entryPoint     ResourceMap entry point
     * @param array     $ignoreFolders  Collection of folders to be ignored in ResourceMap
     */
    public function __construct($entryPoint, $ignoreFolders = null)
    {
        $this->entryPoint = $entryPoint;

        if (isset($ignoreFolders)) {
            // Combine passed folders to ignore with the default ones
            $this->ignoreFolders = array_merge($this->ignoreFolders, $ignoreFolders);
        }

        // Store current ResourceMap in ResourceMaps collection
        self::$gathered[$this->entryPoint] = & $this;
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
     * Determines if file is an SamsonPHP controller file
     * @param string $path Path to file for checking
     * @return bool True if file is a SamsonPHP view file
     */
    public function isController($path)
    {
        // If this is a .php file
        if(strpos($path, '.php') !== false) {

            // Try to match using old-style method by location
            if (strpos($path, __SAMSON_CONTOROLLER_PATH) !== false) {
                return true;
            } else if(basename($path, '.php') == 'controller') { // New function style controller by file name
                return true;
            } else { // Harder way - analyzing files
                elapsed('Reading controller '.$path);
                $file = fopen($path, 'r');
                // Read lines from file
                for($i = 0; $i<self::CLASS_FILE_LINES_LIMIT; $i++) {
                    // Read one line from a file and try to find extends class pattern
                    if (preg_match('/extends\s+ExternalModule/iu', fgets($file))) {
                        return true;
                    }
                }
                fclose($file);
            }
        }

        return false;
    }

    /**
     * Perform resource gathering starting from $path entry point
     * @param string    $path   Entry point to start scanning resources
     * @return array Collection of resources grouped by resource file extension
     */
    public function build($path = null)
    {
        //[PHPCOMPRESSOR(remove,start)]
        s()->benchmark( __FUNCTION__, func_get_args(), __CLASS__);
        //[PHPCOMPRESSOR(remove,end)]

        // If no other path is passed use current entry point and convert it to *nix path format
        $path = normalizepath(isset($path) ? $path : $this->entryPoint);

        // Store new entry point
        $this->entryPoint = $path;

        // Check for correct path and then try to get files
        if (file_exists($path)) {
            // Collect all resources from entry point
            $files = array();
            //TODO: Ignore cms folder - ignore another web-applications or not parse current root web-application path
            foreach (File::dir($this->entryPoint, null, '', $files, NULL, 0, $this->ignoreFolders) as $file) {
                // We can determine SamsonPHP view files by 100%
                if($this->isView($file)) {
                    $this->views[] = $file;
                } else if($this->isModel($file)) {
                    $this->models[] = $file;
                } else if($this->isController($file)) {
                    $this->controllers[] = $file;
                } else { // Save resource by file extension
                    // Get extension as resource type
                    $rt = pathinfo($file, PATHINFO_EXTENSION );

                    // Check if resource type array cell created
                    if (!isset($this->resources[$rt])) {
                        $this->resources[$rt] = array();
                    }

                    // Add resource to collection
                    $this->resources[$rt][] = $file;
                }
            }

            // Iterate all defined object variables
            foreach (get_object_vars($this) as $var => $value) {
                // If we have matched resources with that type
                if(isset($this->resources[$var])) {
                    // Bind object variable to resources collection
                    $this->$var = & $this->resources[$var];
                }
            }

        } else { // Signal error
            return e('Cannot build ResourceMap from ## - path does not exists', E_SAMSON_CORE_ERROR, $path);
        }
    }
}
 