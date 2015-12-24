<?php
namespace samson\core;

use samsonframework\core\CompressInterface;

/**
 * Интерфейс определяющий возможность сжатия модуля 
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 0.1
 * @deprecated Use samsonframework\core\CompressInterface
 */
interface iModuleCompressable extends CompressInterface
{
    /**
     * Предобработчик сжатия модуля
     *
     * @param object $obj Объект который выполняет процесс сжатия модуля
     * @param array $code Соллекция кода модуля которую необходимо заполнить
     */
    public function beforeCompress(&$obj = null, array &$code = null);

    /**
     * Постобработчик сжатия модуля
     *
     * @param object $obj Объект который выполняет процесс сжатия модуля
     * @param array $code Соллекция кода модуля которую необходимо заполнить
     */
    public function afterCompress(&$obj = null, array &$code = null);
}
