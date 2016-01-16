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


class VirtualExternalModule extends ExternalModule implements CompressInterface
{

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