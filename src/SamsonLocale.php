<?php
namespace samson\core;

// TODO: Should moved to separate module, probably i18n

//[PHPCOMPRESSOR(remove,start)]
// I default locale is not defined
if (!defined('DEFAULT_LOCALE')) {
    // Define default locale
    define('DEFAULT_LOCALE', SamsonLocale::RU);
}
//[PHPCOMPRESSOR(remove,end)]

/**
 * Класс для поддержки локализации веб-приложения
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @package SamsonPHP 
 * @version 0.1.0
 */
class SamsonLocale
{
    /** Локализация по умолчанию */
    const DEF = DEFAULT_LOCALE;
    /** Украинская локализация */
    const UA = 'ua';
    /** Английская локализация */
    const EN = 'en';
    /** Русская локализация */
    const RU = 'ru';
    /** Румынская локализация */
    const RO = 'ro';
    /** Китайская локализация */
    const CH = 'ch';
    /** Французская локализация */
    const FR = 'fr';
    /** Корейская локализация */
    const KO = 'ko';
    /** Немецкая локализация */
    const DE = 'de';

    /**
     * Коллекция поддерживаемых локализаций
     * Используется для дополнительного контроля за локализациями
     * @var array
     */
    private static $supported = array(
        SamsonLocale::DEF,
        SamsonLocale::EN,
        SamsonLocale::UA,
        SamsonLocale::RO,
        SamsonLocale::CH,
        SamsonLocale::RU,
        SamsonLocale::FR,
        SamsonLocale::KO,
        SamsonLocale::DE,
    );

    /**
     * Alias for binding default web-application locale
     * @var string
     */
    public static $defaultLocale = DEFAULT_LOCALE;

    /**
     * Текущая локализация веб-приложения
     * @var string
     */
    public static $current_locale = '';

    /**
     * Коллекция подключенных локализаций для текущего веб-приложения
     * Локаль по умолчанию RU, имеет представление пустышку - ''
     * @var array
     */
    public static $locales = array();

    /** @var bool Flag for leaving default locale as path placeholder */
    public static $leaveDefaultLocale = true;

    /**
     * Проверить текущей значение установленной локали, и если выставлена
     * не поддерживаемая локаль - установим локаль по умолчанию
     */
    public static function check()
    {
        // Проверим значение установленной текущей локализации, если оно не входит в список доступных локализаций
        // установим локализацию по умолчанию
        if (!in_array(strtolower(self::$current_locale), self::$locales)) self::$current_locale = self::DEF;
    }

    /**
     * Установить все доступные локализации для текущего веб-приложения.
     * Локализацию по умолчанию, передавать не нужно, т.к. она уже включена в список
     * и описана в <code>SamsonLocale::DEF</code>
     *
     * Функция автоматически проверяет уже выставленное значение локализации веб-приложения
     * и вслучаи его отсутствия, выставляет локализацию по умолчанию
     *
     * @param array $available_locales Коллекция с доступными локализациями веб-приложения
     */
    public static function set(array $available_locales)
    {
        // Переберем локализации
        foreach ($available_locales as $locale) {
            $_locale = strtolower($locale);

            // Добавим в коллекцию доступных локализаций переданные
            if (in_array($_locale, self::$supported)) {
                // Ignore duplicare locale setting
                if (!in_array($_locale, self::$locales)) {
                    self::$locales[] = $_locale;
                }
            } // Проверим разрешаемые локали
            else die('Устанавливаемая локализация "' . $locale . '" - не предусмотрена в SamsonLocale');
        }

        // Проверим значение установленной текущей локализации, если оно не входит в список доступных локализаций
        // установим локализацию по умолчанию
        self::check();
    }

    /**
     * Получить все доступные локализации для текущего веб-приложения
     */
    public static function get()
    {
        return self::$locales;
    }

    /**
     * Установить/Получить текущую локализацию веб-приложения
     * @param string $locale Значение локализации веб-приложения для установки
     * @return string    Возвращается значение текущей локализации веб-приложения до момента
     *                    вызова данного метода
     */
    public static function current($locale = null)
    {
        // Сохраним старое значение локали
        $_locale = self::$current_locale;

        // If nothing is passed just return current locale
        if (!isset($locale)) {
            return $_locale;

        } else { // Switch locale
            // Save new locale
            self::$current_locale = strtolower($locale);

            // Запишем текущее значение локализации
            $_SESSION['__SAMSON_LOCALE__'] = self::$current_locale;

            // Вернем текущее значение локали сайта до момента візова метода
            return self::$current_locale;
        }
    }

    /**
     * Parse URL arguments
     * @param array $args Collection of URL arguments
     * @param bool $leaveDefaultLocale Leave default locale placeholder
     * @return boolean True if current locale has been changed
     */
    public static function parseURL(array &$args, $leaveDefaultLocale = true)
    {
        // Iterate defined site locales
        foreach (self::$locales as $locale) {
            // Search locale string as URL argument
            if (($key = array_search($locale, $args)) === 0) {
                // Change current locale
                self::current($locale);

                // If this is not default locale(empty string) - remove it from URL arguments
                if ($leaveDefaultLocale || $locale != self::DEF) {
                    // Remove argument contained locale string
                    unset($args[$key]);

                    // Reindex array
                    $args = array_values($args);
                }

                // Return true status
                return true;
            }
        }

        // If we are here - this is default locale
        // Switch to current web-application $default locale
        self::current(self::DEF);

        return false;
    }
}
