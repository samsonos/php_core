<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 24.04.14 at 17:58
 */

// Global namespace


/**
 * System(Система) - Получить объект для работы с ядром системы
 * @return samson\core\Core Ядро системы
 * @deprecated Use $this->system in module context
 */
function &s()
{
    // т.к. эта функция вызывается очень часто - создадим статическую переменную
    static $_v;

    // Если переменная не определена - получим единственный экземпляр ядра
    if (!isset($_v)) {
        // Load instance from global snapshot
        if (isset($GLOBALS["__CORE_SNAPSHOT"])) {
            $_v = unserialize(base64_decode($GLOBALS["__CORE_SNAPSHOT"]));

        } else { // Create new instance
            $_v = new samson\core\Core();
        }
    }

    // Вернем указатель на ядро системы
    return $_v;
}

/**
 * Module(Модуль) - Получить Текущий модуль / Модуль по имени из стека модулей системы
 * @see        iCore::module();
 *
 * @param mixed $module Указатель на модуль системы *
 *
 * @return \samson\core\Module Текущую / Модель по её имени / или FALSE если модель не найдена
 * @deprecated Use $this->system->module() in module context
 */
function &m($module = NULL)
{
    // т.к. эта функция вызывается очень часто - создадим статическую переменную
    static $_s;

    // Если переменная не определена - получим единственный экземпляр ядра
    if (!isset($_s)) $_s = &s();

    // Вернем указатель на модуль системы
    return $_s->module($module);
}

/**
 * Error(Ошибка) - Зафиксировать ошибку работы системы
 *
 * @param string $error_msg  Текст ошибки
 * @param int    $error_code Код ошибки
 * @param mixed  $args       Специальные "жетоны" для вставки в текст ошибки
 * @param mixed  $ret_val    Value that must be returned by the function
 *
 * @return bool FALSE для остановки работы функции или условия
 * @throws Exception
 * @deprecated Use custom exceptions
 */
function e($error_msg = '', $error_code = E_USER_NOTICE, $args = NULL, & $ret_val = false)
{
    // Если передан только один аргумент то сделаем из него массив для совместимости
    $args = is_array($args) ? $args : array($args);

    // "Украсим" сообщение об ошибке используя переданные аргументы, если они есть
    if (isset($args)) {
        $error_msg = debug_parse_markers($error_msg, $args);
    }

    throw new \Exception($error_msg);

    return $ret_val;
}

/**
 * Установить все доступные локализации для текущего веб-приложения.
 * Локализацию по умолчанию, передавать не нужно, т.к. она уже включена в список
 * и описана в <code>SamsonLocale::DEF</code>
 *
 * Достуные локализации передаются в функцию в виде обычных аргументов функции:
 * Для исключения ошибок рекомендуется передавать константы класса SamsonLocale
 * <code>setlocales( SamsonLocale::UA, SamsonLocale::EN )</code>
 *
 * @see SamsonLocale::set()
 */
function setlocales()
{
    $args = func_get_args();
    if (class_exists(\samson\core\SamsonLocale::class)) {
        \samson\core\SamsonLocale::set($args);
    }
}

/**
 * Установить/Получить текущую локализацию сайта
 *
 * @see SamsonLocale::current()
 *
 * @param string $locale Значение локали
 *
 * @return string Возвращает текущее значение локали сайта до момента вызова метода
 */
function locale($locale = NULL)
{
    return \samson\core\SamsonLocale::current($locale);
}

/**
 * Check if passed locale alias matches current locale and output in success
 *
 * @param string $locale Locale alias to compare with current locale
 * @param string $output Output string on success
 *
 * @return boolean True if passed locale alias matches current locale
 */
function islocale($locale, $output = '')
{
    if (\samson\core\SamsonLocale::$current_locale == $locale) {
        echo $output;
        return true;
    }
    return false;
}

/**
 * Build string with locale to use in URL and file path
 *
 * @param string $l Locale name to use, if not passed - current locale is used
 *
 * @return string locale path if current locale is not default locale
 */
function locale_path($l = null)
{
    // If no locale is passed - get current locale
    $l = !isset($l) ? locale() : $l;

    // Build path starting with locale
    return ($l != \samson\core\SamsonLocale::DEF) ? $l . '/' : '';
}

/**
 * Build URL relative to current web-application, method accepts any number of arguments,
 * every argument starting from 2-nd firstly considered as module view parameter, if no such
 * parameters is found - its used as string.
 *
 * If current locale differs from default locale, current locale prepended to the beginning of URL
 *
 * @see URL::build()
 *
 * @return string Builded URL
 */
function url_build()
{
    // Get cached URL builder for speedup
    static $_v;
    $_v = isset($_v) ? $_v : url();

    // Get passed arguments
    $args = func_get_args();

    // If we have current locale set
    if (\samson\core\SamsonLocale::$leaveDefaultLocale && \samson\core\SamsonLocale::current() != \samson\core\SamsonLocale::DEF) {
        // Add locale as first url parameter
        array_unshift($args, \samson\core\SamsonLocale::current());
    }

    // Call URL builder with parameters
    return call_user_func_array(array($_v, 'build'), $args);
}

/**
 * Echo builded URL from passed parameters
 * @see url_build
 */
function url_base()
{
    $args = func_get_args();

    // Call URL builder and echo its result
    echo call_user_func_array('url_build', $args);
}

/**
 * Echo builded URL from passed parameters
 * @see url_build
 */
function url_hash()
{
    $args = func_get_args();

    // Call URL builder and echo its result
    $returnUrl = call_user_func_array('url_build', $args);

    // Return url with hash
    echo substr($returnUrl, 0, -1);
}

/**
 * Echo builded URL from passed parameters, prepending first parameter as current module identifier
 * @see url_build()
 */
function module_url()
{
    $args = func_get_args();

    echo call_user_func_array('url_build', array_merge(array(url()->module), $args));
}


