<?php
/**
 * Created by PhpStorm.
 * User: kotenko
 * Date: 16.01.2016
 * Time: 15:27
 */
namespace samson\core;

use samsonframework\core\CompressInterface;
use samsonframework\core\ResourcesInterface;
use samsonframework\core\SystemInterface;

/**
 * Virtual SamsonPHP module needed to imitate 3rd party packages
 * as they are SamsonPHP modules for internal seamless usage.
 *
 * @package samson\core
 */
class VirtualModule extends ExternalModule implements CompressInterface
{
    /**
     * VirtualModule constructor.
     *
     * @param string             $path
     * @param ResourcesInterface $resources
     * @param SystemInterface    $system
     * @param null               $moduleId
     */
    public function __construct($path, ResourcesInterface $resources, SystemInterface $system, $moduleId = null)
    {
        $this->id = $moduleId;

        parent::__construct($path, $resources, $system);
    }

    /**
     * This method should be used to override generic compression logic.
     *
     * @param mixed $obj Pointer to compressor instance
     * @param array|null $code Collection of already compressed code
     *
     * @return bool False if generic compression needs to be avoided
     */
    public function beforeCompress(&$obj = null, array &$code = null)
    {
        // TODO: Implement beforeCompress() method.
    }

    /**
     * This method is called after generic compression logic has finished.
     *
     * @param mixed $obj Pointer to compressor instance
     * @param array|null $code Collection of already compressed code
     *
     * @return bool False if generic compression needs to be avoided
     */
    public function afterCompress(&$obj = null, array &$code = null)
    {
        // TODO: Implement afterCompress() method.
    }
}