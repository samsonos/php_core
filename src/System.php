<?php declare(strict_types=1);
namespace samsonphp\core;

use samson\core\ExternalModule;
use samsonframework\core\CompressInterface;

/**
 * SamsonPHP core module representation class.
 *
 * @author Vitaly Iegorov
 * @deprecated
 */
class System extends ExternalModule implements CompressInterface
{
	protected $id = 'core';

    /** {@inheritdoc} */
    public function beforeCompress(&$obj = null, array &$code = null)
    {

    }

    /** {@inheritdoc} */
    public function afterCompress(&$obj = null, array &$code = null)
    {

    }
}
