<?php
namespace samson\core;

use samsonframework\core\CompressInterface;

/**
 * Локальный модуль системы поддерживающий сжатие
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @deprecated Just implement samsonframework\core\CompressInterface
 */
class CompressableLocalModule extends Module implements CompressInterface
{
    /** @see \samson\core\iModuleCompressable::beforeCompress() */
    public function beforeCompress(& $obj = null, array & $code = null)
    {
    }

    /** @see \samson\core\iModuleCompressable::compress() */
    public function afterCompress(& $obj = null, array & $code = null)
    {
    }
}
