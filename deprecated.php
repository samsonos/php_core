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
    $args = func_get_args();

    // Call URL builder
    return call_user_func_array( 'url_base', $args );
}

/**
 * Универсальный обработчик ассинхронного действия контроллера модуля
 * Оберточная функция для ассинхронных контроллеров модулей.
 * Функция может принимать аргументом обработчиков как имя метода так и метод
 * конкретного класса(объекта), для этого необходимо передать массив
 * вида (КЛАСС,ИМЯ_МЕТОДА).
 * Так же функци может принимать как один параметр для этого обработчика
 * так и коллекцию параметров. Описанные получаемые параметры функция
 * определяет автоматически.
 *
 * В основе вызова обработчиков лежит функция:
 * @see call_user_func_array();
 *
 * Функция всегда возвращает ответ в виде JSON объекта в котором могут находится
 * два поля:
 *  - string error
 *  - string data
 *
 * Обработчик действия $action_handler должен иметь вид:
 *   boolean ИМЯ_ОБРАБОТЧИКА( [ПАРАМЕТР1, ПАРАМЕТР2, ... ,] & $status )
 * И записать в переменную $status все ошибки которые могли возникнуть в случаи выполнения
 * действия, и если этот обработчик вернет FALSE то значение переменной $status будет возвращено
 * клиенту в соответствующем поле JSON::error
 *
 * Обработчик успешного выполнения действия $success_handler должен иметь вид:
 *   string ИМЯ_ОБРАБОТЧИКА( [ПАРАМЕТР1, ПАРАМЕТР2, ... ,] )
 * Возвращаемое обработчиком СТРОКОВОЕ значение будет возвращено клиенту в
 * соответствующем поле JSON::data
 *
 * @param mixed $action_handler 	Обработчик действия контроллера
 * @param mixed $action_params 		Параметры для обработчик действия контроллера
 * @param mixed $success_handler 	Обработчик успешного выполнения действия контроллера
 * @param mixed $success_params 	Параметры для обработчика успешного выполнения действия контроллера
 *
 * @return string JSON 	Результат выполнения ассинхронного действия контроллера
 */
function ajax_handler( $action_handler, $action_params = NULL, $success_handler = NULL, $success_params = NULL )
{
    // Ассинхронность
    s()->async(TRUE);

    // Ответ для клиента
    $responce = array();

    // Если параметры переданы не как массив - преобразуем
    if( !is_array( $action_params ) ) $action_params = array( $action_params );

    // Создадим переменную для передачи её в метод действия по ссылке
    // в эту переменную целевой метод может вернуть любое значение
    $returnValue = '';
    // Запишем эту переменную как 3-й параметр по имени "returnValue"
    $action_params['returnValue'] = & $returnValue;

    // Если параметры переданы не как массив - преобразуем
    if( !is_array( $success_params ) ) $success_params = array( $success_params );

    // Если обработчик действия передан как фукнция класса
    if( is_array($action_handler) && sizeof($action_handler) )
    {
        $a_class = $action_handler[0];
        $a_method = $action_handler[1];
    }
    // Установим просто как метод
    else $a_method = $action_handler;

    // Если обработчик успешного выполнения действия передан как фукнция класса
    if( is_array($success_handler) && sizeof($success_handler) )
    {
        $s_class = $success_handler[0];
        $s_method = $success_handler[1];
    }
    // Установим просто как метод
    else $s_method = $success_handler;

    // Если указан обработчик действия
    if( function_exists( $a_method ) || method_exists( $a_class, $a_method) )
    {
        // Выполним обработчик действия - Если он вернул FALSE - запишем в ошибку его "возвртное" значение
        if( ! call_user_func_array( $action_handler, $action_params ) )
        {
            // Сохраним значение которое вернуло действие как его ошибку
            $responce['error'] = $action_params['returnValue'];
        }
        // Иначе если передан обработчик успешного выполнения действия - вернем его в статус
        else if( function_exists( $s_method ) || method_exists( $s_class, $s_method) )
        {
            // Запишем результат обработчик успешного выполнения метода как ответ для сервера
            $responce['data'] = call_user_func_array( $success_handler, $success_params );
        }
    }

    // Веренем ответ для клиента
    return json_encode( $responce );
}