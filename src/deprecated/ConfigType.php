<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 12.09.14 at 22:20
 */

/**
 * Виды режимов конфигурация работы фреймворка
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 0.1
 */
class ConfigType
{
    /** Конфигурация по умолчанию*/
    const ALL 			= 0;
    /** Конфигурация для разработки	*/
    const DEVELOPMENT 	= 1;
    /** Конфигурация для тестирования */
    const TEST 			= 2;
    /** Конфигурация для продакшина */
    const PRODUCTION 	= 3;
}
