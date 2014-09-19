<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 19.09.2014 at 19:31
 */
 namespace samson\core;

/**
 * Give ability to compress code
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 */
interface iCompressable
{
    /**
     * Generic code transformation
     * @param string $input  Code to be compressed
     * @param string $output Returned compressed code
     * @return bool True if code transformation actionally happened
     */
    function transform($input, & $output);
}
 