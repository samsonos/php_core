<?php
namespace samsonphp\core\deprecated;

use samsonframework\core\RenderInterface;
use samsonframework\core\ViewInterface;

/**
 * Интерфейс для модуля системы
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @deprecated Use samsonframework\core\ViewInterface
 */
interface iModule extends ViewInterface, RenderInterface
{
    /** Identifier for default view data entry */
    const VD_POINTER_DEF = '';

    /** Pointer to plain html entry in view data entry */
    const VD_HTML = '__html__';

    /** Controller action cache name marker */
    const CTR_CACHE = 'cache_';

    /** Default controller name */
    const CTR_BASE = '__base';
    const CTR_CACHE_BASE = '__cache_base';

    /** Universal controller name */
    const CTR_UNI = '__handler';
    const CTR_CACHE_UNI = '__cache_handler';

    /** Post controller name */
    const CTR_POST = '__post';
    const CTR_CACHE_POST = '__cache_post';

    /** Put controller name */
    const CTR_PUT = '__put';
    const CTR_CACHE_PUT = '__cache_put';

    /** Delete controller name */
    const CTR_DELETE = '__delete';
    const CTR_CACHE_DELETE = '__delete';

    /** Controllers naming conventions */

    /** Procedural controller prefix */
    const PROC_PREFIX = '_';
    /** OOP controller prefix */
    const OBJ_PREFIX = '__';
    /** AJAX controller prefix */
    const ASYNC_PREFIX = 'async_';
    /** CACHE controller prefix */
    const CACHE_PREFIX = 'cache_';
}
