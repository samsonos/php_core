<?php
/**
* Получить полный физический путь к ресурсу
* с учетом физического размещения модуля системы
*
* Этот метод "предохраняет" при указывания путей
* внутри модулей делая "независимым" их физическое
* размещение
*
* @param string 	$path 	Путь относительно модуля
* @param iModule 	$module Модуль для которого строится путь
* @deprecated
* @return string Полный путь к файлу/папке модуля
*/
function path( $path, iModule & $module = NULL ) {
	$m = isset($module) ? $module : s()->module(); return $m->path().$path;
}

/**
 * @deprecated Just use url_base()
 * @param string $url Начальный URL-Путь для построения
 * @return string Полный URL с параметрами
 */
function url_locale_base( $url = '' )
{
    // Call URL builder
    return call_user_func_array( 'url_base', func_get_args() );
}