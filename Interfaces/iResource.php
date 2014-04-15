<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 15.04.14 at 17:13
 */
 namespace samson\core;

/**
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2013 SamsonOS
 * @version 0.0.1
 */
interface iResource
{
    /**
     * Generic method for gathering files and storing them in array grouping by their extension
     * as array key
     *
     * @param string $path Root folder path to start files gathering
     *
     * @return array Assosiative array of files with keys as file extensions
     */
    function gatherByExtension($path);

    /**
     * Generic method to get file type
     * @param string $path
     *
     * @return mixed
     */
    function getFileType($path);
}
 