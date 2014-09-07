<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 24.04.14 at 18:01
 */

/**
 * View variable( Переменная представления ) - Вывести значение переменной представления
 * текущего модуля системы в текущий поток вывода.
 *
 * @see iModule
 * @see iCore::active()
 *
 * Это дает возможность использовать функцию в представлениях для более компактной записи:
 * <code><?php v('MODULE_VAR')?></code>
 *
 * Для возравщения значения переменной, без её вывода в поток, необходимо использовать
 *    Для переменный представления:
 *        m( MODEL_NAME )->set( VAR_NAME ) либо $VIEW_VAR_NAME
 *    Для переменных модуля:
 *        m( MODEL_NAME )->VAR_NAME;
 *
 * @param string $name Имя переменной представления текущего модуля
 * @param null   $realName
 */
function v( $name, $realName = NULL )
{
    // Получим указатель на текущий модуль
    $m = & m();

    // Если передана ПРАВДА - используем первый параметр как имя
    if( is_bool( $realName ) && ($realName === true)) $realName = '__dm__'.$name;
    else $realName = '__dm__'.$realName;

    // Если задано специальное значение - выведем его
    if( isset($realName) && $m->offsetExists( $realName ))echo $m[ $realName ];
    // Если дополнительный параметр не задан и у текущего модуля задана требуемое
    // поле - выведем его значение в текущий поток вывода
    else if ( $m->offsetExists( $name )) echo $m[ $name ];
    // Otherwise just output
    else echo $name;
}

/**
 * IV(If view variable) - output view variable only if is correctly set for view output *
 */
function iv( $name, $realName = NULL )
{
    // Cache current module pointer
    $m = & m();

    // If view variable is set - echo it
    if ( isvalue( $m, $name) ) echo $m[ $name ];
}

/**
 * View variable for Input( Переменная представления ) - Вывести значение переменной представления
 * текущего модуля системы в текущий поток вывода c декодирования HTML символов.
 *
 * Используется для HTML полей ввода
 *
 * @see v()
 * @param string $name 	Имя переменной представления текущего модуля
 */
function vi( $name ){ $m = & m();  if( $m->offsetExists( $name )) echo htmlentities($m[ $name ], ENT_QUOTES,'UTF-8');}

/**
 * Figure out if module view variable value is correctly set for view output
 *
 * @param \samson\core\iModule	$m		Pointer to module
 * @param string 	$name 	View variable name
 * @param mixed 	$value	Value to compare
 * @return boolean If view variable can be displayed in view
 */
function isvalue( $m, $name, $value = null )
{
    // If we have module view variable
    if( isset($m[ $name ]) )
    {
        // Get value
        $var = $m[ $name ];

        //trace($name.'-'.$var.'-'.gettype( $var ).'-'.$value);

        // Get variable type
        switch( gettype( $var ) )
        {
            // If this is boolean and it matches $value
            case 'boolean': return (isset($value) ? $var == $value : $var);
            // If this is number and it matches $value
            case 'integer': return (isset($value) ? $var === intval($value): $var);
            // If this is double and it matches $value
            case 'double':  return (isset($value) ? $var === doubleval($value): $var);
            // If this is double and it matches $value
            case 'float':  return (isset($value) ? $var === floatval($value): $var);
            // If this is not empty array
            case 'array':   return sizeof($var);
            // If this is a string and it matches $value or if no $value is set string is not empty
            case 'string':  return  (!isset($value) && isset($var{0})) ||
            (isset($value) && $var === strval($value)) ;
            // If this is an object consider it as ok
            case 'object': return true;
            // Not supported for now
            case 'NULL':
            case 'unknown type':
            default: return false;
        }
    }
}

/**
 * Is Value( Является ли значением) - Проверить является ли переменная представления
 * указанным значением. Метод проверяет тип переменной и в зависимости от этого проверяет
 * соответствует ли переменная представления заданному значению:
 *  - проверяется задана ли переменная вообще
 *  - если передана строка то проверяется соответствует ли она заданному значению
 *  - если передано число то проверяется равно ли оно заданому значению
 *
 * Все сравнения происходят при преобразовании входного значения в тип переменной
 * представления.
 *
 * По умолчанию выполняется сравнение значения переменной представления
 * с символом '0'. Т.к. это самый частый вариант использования когда необходимо получить значение
 * переменной объекта полученного из БД, у которого все поля это строки, за исключением
 * собственно описанных полей.
 *
 * @param string 	$name 		Module view variable name
 * @param mixed 	$value 		Value for checking
 * @param string	$success	Value for outputting in case of success
 * @param string	$failure	Value for outputting in case of failure
 * @param boolean	$inverse	Value for outputting in case of success
 * @return boolean Соответствует ли указанная переменная представления переданному значению
 */
function isval( $name, $value = null, $success = null, $failure = null, $inverse = false)
{
    // Pointer to current module
    $m = & m();

    // Flag for checking module value
    $ok = isvalue( $m, $name, $value );

    // If inversion is on
    if( $inverse ) $ok = ! $ok;

    // If we have success value - output it
    if( $ok && isset( $success) ) v($success);
    // If we have failure value - output it
    else if( isset($failure)) v($failure);

    return $ok;
}

/**
 * Is Variable exists, also checks:
 *  - if module view variable is not empty array
 *  - if module view variable is not empty string
 *
 * @param string 	$name Имя переменной для проверки
 * @param string	$success	Value for outputting in case of success
 * @param string	$failure	Value for outputting in case of failure
 * @return boolean True if variable exists
 */
function isv( $name, $success = null, $failure = null ){ return isval($name, null, $success, $failure ); }

/**
 * Is Variable DOES NOT exists, also checks:
 *  - if module view variable is empty array
 *  - if module view variable is empty string
 *
 * @param string 	$name Имя переменной для проверки
 * @param string	$success	Value for outputting in case of success
 * @param string	$failure	Value for outputting in case of failure
 * @return boolean True if variable exists
 */
function isnv( $name, $success = null, $failure = null ){ return isval( $name, null, $success, $failure, true ); }

/**
 * Is NOT value - checks if module view variable value does not match $value
 *
 * @param string 	$name 	 Module view variable name
 * @param mixed 	$value 	 Value for checking
 * @param string	$success Value for outputting in case of success
 * @param string	$failure Value for outputting in case of failure
 * @return boolean True if value does NOT match
 */
function isnval( $name, $value = null, $success = null, $failure = null ){ return isval($name, $value, $success, $failure, true ); }

/**
 * Echo HTML link tag with text value from module view variable
 *
 * @param string $name	View variable name
 * @param string $href	Link url
 * @param string $class	CSS class
 * @param string $id	HTML identifier
 * @param string $title	Title tag value
 */
function vhref( $name, $href = null, $class = null, $id = null,  $title = null )
{
    $m = & m();

    // If value can be displayed
    if( isvalue( $m, $name ) || isvalue( $m, $href ) )
    {
        $name = isset( $m[ $name ] ) ? $m[ $name ] : $name;

        $href = isset( $m[ $href ] ) ? $m[ $href ] : $href;

        echo '<a id="'.$id.'" class="'.$class.'" href="'.$href.'" title="'.$title.'" >'.$name.'</a>';
    }
}

/**
 * Render IMG html tag
 * @param string $src 	Module view variable name to parse and get path to image
 * @param string $id	Image identifier
 * @param string $class	Image CSS class
 * @param string $alt	Image alt text
 * @param string $dummy	Dummy image path not set *
 */
function vimg( $src, $id='', $class='', $alt = '', $dummy = null )
{
    // Закешируем ссылку на текущий модуль
    $m = & m();

    // Проверим задана ли указанная переменная представления в текущем модуле
    if( $m->offsetExists( $src )) $src = $m[ $src ];
    //
    elseif( isset($dummy))$src = $dummy;

    // We always build path to images fully independant of web-application or module relatively to base web-app
    if( $src{0} != '/' ) $src = '/'.$src;

    // Выведем изображение
    echo '<img src="'.$src.'" id="'.$id.'" class="'.$class.'" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 * Output view variable as date with formating
 * If view variable exists then output it as date with formatting
 *
 * @param string $name 	 Module view variable name
 * @param string $format Date format string
 * @param string $function Function callback to render date
 */
function vdate( $name, $format = 'h:i d.m.y', $function = 'date' )
{
    // Cache current module
    $m = & m();

    // If view variable is set - echo with format
    if ( $m->offsetExists( $name )) echo $function( $format, strtotime($m[ $name ]));

}

/**
 * Is Module ( Является ли текущий модуль указанным ) - Проверить совпадает ли имя текущего модуля с указанным
 *
 * @param string $name Имя требуемого модуля для сравнения с текущим
 * @return boolean Является ли имя текущего модуля равным переданному
 */
function ism( $name ){ return (m()->id() == $name); };