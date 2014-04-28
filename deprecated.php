<?php

/**
 * Module Out - получить специальный виртуальный модуль ядра системы "LOCAL" для генерации и вывода
 * промежуточных представлений, что бы избежать затирания/изменения контекста текущего модуля
 * системы. Эта функция является шорткатом вызова m('local')
 *
 * @see m()
 * @deprecated Use just m() thank's to new rendering model
 * @return iModule Указатель на виртуальный модуль "LOCAL" ядра системы
 */
function & mout(){ return m();  }

/**
 * Получить содержание представления.
 *
 * Данный метод выполняет вывод ПРЕДСТАВЛЕНИЯ(Шаблона) с подключением
 * переменных и всей логики ядра системы в отдельный буферезированный поток вывода. Нужно не путать с методом
 * которой подключает обычные PHP файлы, т.к. этот метод отвечает только за вывод представлений.
 *
 * Так же при выводе представления учитываются все установленные пути приложения.
 *
 * @param string $view 			Путь к представлению для вывода
 * @param string $vars 			Коллекция переменных которые будут доступны в выводимом представлении
 * @param string $prefix 		Дополнительный префикс который возможно добавить к именам переменных в представлении
 *
 * @see iCore::import
 * @deprecated Use m()->output()
 * @return string Содержание представления
 */
function output( $view, array $vars = NULL, $prefix = NULL )
{
    return s()->render($view,$vars);
}

/**
 * Return only class name without namespace
 * @param string $class_name class name
 * @deprecated use \samson\core\AutoLoader::getOnlyClass()
 * @return string class name without namespace
 */
function classname( $class_name )
{
    return \samson\core\AutoLoader::getOnlyClass($class_name);
}

/**
 * Return only namespace name from class name
 * @param string $class_name class name
 * @deprecated use \samson\core\AutoLoader::getOnlyClass()
 * @return string Namespace name
 */
function nsname( $class_name )
{
    return \samson\core\AutoLoader::getOnlyNameSpace($class_name);
}

/**
 * Универсальный генератор HTML элемента формы SELECT для выбора элемента
 * из БД принадлежащего определоенному классу, сущности
 *
 * @param array 	$entity_name 	Имя сущности в БД
 * @param array 	$db_array 		Коллекция элементов полученных из БД для генерации элемента формы
 * @param mixed 	$db_obj 		Указатель на выбранный объект из БД или его идентификатор
 * @param string 	$name_attr		Имя поля объекта БД отвечающего за его представление
 * @param string 	$desc_attr 		Имя поля объекта БД отвечающего за его описание
 * @return string HTML элемент формы SELECT для выбора объекта из БД
 */
function html_db_form_select_from_array( $entity_name, array $db_array, $db_obj = NULL, $name_attr = 'Name', $desc_attr = 'Description', $id_attr = NULL  )
{
    // Попытаемся безопасно получить указатель на переданный объект из БД
    if(Samson\ActiveRecord\dbSimplify::parse( $entity_name, $db_obj, $db_obj )) $obj_id = $db_obj->id;
    // Иначе оставим идкентификатор выбранного элемента как есть
    else $obj_id = $db_obj;

    // Результирующий массив данных для формирования HTML элемента формы
    $select_data = array(array('id' => 0, 'name' => 'Не выбрано'));

    // Переберем полученный объекты из БД
    $db_objs_count = sizeof( $db_array );
    for ($i = 0; $i < $db_objs_count; $i++)
    {
        // Получим указатель на текущицй объект из выборки
        $obj = & $db_array[ $i ];

        if (isset($id_attr)) $id_vall = $obj[$name_attr];
        else $id_vall = $obj->id;

        // Добавим данные в массив для HTML представления
        $select_data[] = array
        (
            'id' 	=> $id_vall,
            'name' 	=> $obj[$name_attr],
            'description' => $obj[$desc_attr]
        );
    }

    // Выполним генерацию элемента HTML
    return html_form_select_options( $select_data, $obj_id );
}

/**
 * Сформировать HTML элемент формы SELECT из строки в виде списка:
 * 	- КЛЮЧ : ПРЕДСТАВЛЕНИЕ, ...
 *
 * @param string $list 		Строка с данными для формирования
 * @param string $object_id Идентификатор или объект который должен быть выбран в списке
 * @return string HTML элемент формы SELECT
 */
function html_form_select_from_list( $list, $object_id = NULL )
{
    // Сформируем список данных
    $data = array();

    // Разобьем значения
    foreach ( explode( ',', $list ) as $value )
    {
        // Разобьем на Значение : Представление
        $value = explode( ':', $value );

        // Если мы нашли необходимый разделитель
        if( is_array($value) )
        {
            // Получим ключ значения
            $id = uniset( $value[0], '' );

            // Получим представление значения
            $name = uniset( $value[1], $id, '' );

            // Получим описание значения
            $desc = uniset( $value[2], '' );

            // Сформируем данные для HTML
            $data[] = array( 'id' => $id, 'name' => $name, 'description' => $desc );
        }
    }

    // Вернем HTML select
    return html_form_select_options( $data, $object_id );
}

/**
 * Универсальный генератор HTML элемента формы SELECT для выбора элемента
 * из БД принадлежащего определоенному классу, сущности
 *
 * @param mixed $db_obj Указатель на выбранный элемент
 * @return string HTML элемент формы SELECT для выбора объекта из БД
 */
function html_db_form_select_options( $entity_name, $db_obj = NULL, $name_attr = 'Name', $desc_attr = 'Description', $add_unselected = 'Не выбрано' )
{
    // Сформируем правильное имя класса
    $entity_name = ns_classname( $entity_name, 'samson\activerecord' );

    // Проверим существует ли указанная сущность
    if( ! class_exists( $entity_name )) return FALSE;

    // Идентификатор выбранного элемента
    $obj_id = NULL;

    // Попытаемся безопасно получить указатель на переданный объект из БД
    if (is_array($db_obj))
    {
        foreach ($db_obj as $item)
            if (is_object($item)) $obj_id[] = $item->id;
            elseif(dbQuery( $entity_name )->id($item)->first($db_item) ) $obj_id[] = $db_item->id;
    }
    elseif (is_object($db_obj)) $obj_id = $db_obj->id;
    elseif(dbQuery( $entity_name )->id($db_obj)->first($db_obj) ) $obj_id = $db_obj->id;


    // Работаем с активаными записями(не удаленными)
    $query = dbQuery( $entity_name )->cond( 'Active', 1 )->exec();

    // Выполним запрос на получение записей из БД
    if(dbQuery( $entity_name )->cond( 'Active', 1 )->exec($db_objs) )
    {
        // Результирующий массив данных для формирования HTML элемента формы
        $select_data = array();

        // Если нужно - добавим пункт не выбрано
        if( isset($add_unselected) ) $select_data[] = array('id' => 0, 'name' => $add_unselected );

        foreach ($db_objs as $obj)
        {
            // Добавим данные в массив для HTML представления
            $select_data[] = array
            (
                'id' 			=> $obj->id,
                'name' 			=> isset($obj->$name_attr) ? $obj->$name_attr : '-',
                'description' 	=> isset($obj->$desc_attr) ? $obj->$desc_attr : '-'
            );
        }

        // Выполним генерацию элемента HTML
        return html_form_select_options( $select_data, $obj_id );
    }
}

/**
 * Универсальный генератор опций("содержания") для HTML элемента формы SELECT
 * Принимает в виде исходных данных массив каждый элемент которого должен содержать
 * следующие элементы:
 *  - id - Ключ опции подставляется в HTML атрибут value
 *  - Name - Представление самой опции для отображения в HTML
 *  - Description - Описание опции подставляется в HTML атрибут title
 *
 * @param array $data Коллекция данных для вывода
 * @param string $object_id Идентификатор или объект который должен быть выбран в списке
 *
 * @return string HTML представление опций("содержания") элемента формы SELECT
 */
function html_form_select_options( array $data, $object_id )
{
    // Результат
    $html = '';

    // Получим количество элементов для выбора
    $data_count = sizeof( $data );

    // Переберем все элеметы и сформируем для них HTML представление
    for ($i = 0; $i < $data_count; $i++)
    {
        // Получим элемент для отображения
        $obj = & $data[ $i ];

        // Получим основные параметры элемента
        $id = uniset( $obj['id'], '');

        // Представление элемента
        $name = uniset( $obj['name'], '' );

        // Описание элемента
        $description = uniset( $obj['description'], '');

        // Проверим необходимо ли выбирать этот элемент
        if (is_array($object_id)) $selected = (in_array($id, $object_id)) ? 'selected' : '';
        else $selected = ($id == $object_id) ? 'selected' : '';

        // Сформируем представление опции элемента формы
        $html .= '<option title="'.$description.'" '.$selected.' value="'.$id.'">'.$name.'</option>';
    }

    // Вернем результат
    return $html;
}

/**
 * Преобразовать массив в ненумерованный HTML список <UL>
 *
 * @param array $a Массив для преобразования
 * @return string HTML представление массива в виде ненумерованного списка
 */
function html_ul_from_array( array $a )
{
    // Результат
    $html = '';

    // Если передан массив и он не пустой
    if( is_array( $a ) && sizeof($a) )
    {
        // Переберем массив
        foreach ( $a as $v ) $html .= '<li>'.$v.'</li>';

        // Доформируем список
        $html = '<ul>'.$html.'</ul>';
    }

    // Вернем результат
    return $html;
}

/**
 * Сгенерировать из массива значений HTML список элементов(LI)
 *
 * В параметр <code>$array</code> можно передавать как 2-х мерный массив
 * так и результат запроса к БД
 *
 * Если передан идентификатор выбранного элемента списка и он присутствует в списке
 * тогда он помечается специальным CSS классом <code>selected</code>
 *
 * @param mixed 	$array 			2-х мерный массив значений из которого формируется список, или результат запроса к БД
 * @param string 	$id_field		Название поля массива в котором хранится идентификатор элемента
 * @param string 	$name_field		Название поля массива в котором хранится представление элемента
 * @param string 	$selected_id 	Идентификатор выбранного элемента списка
 * @param string 	$id_field_name	Название аттрибута элемента списка в которое будет записан идентификатор элемента
 * @param string 	$limit 			Ограничение на количетсво элементов в создаваемом списке
 * @return string	HTML список элементов полученный из переданного массива
 */
function html_li( $array, $id_field = 'id', $name_field = 'Name', $selected_id = NULL, $id_field_name = 'value', $limit = -1, $class = 'class="item"' )
{
    // Результат
    $result = '';

    // Если передан массив
    if( is_array( $array ) )
    {
        // Если задано ограничение на размер массива - обрежем его
        if( $limit > 0 ) $array = array_slice( $array, 0, $limit );

        // Переберем массив
        foreach ( $array as $k => $item )
        {
            // Проверим является ли текущий элемент выбранным
            $class = ($item[ $id_field ] == $selected_id) ? 'class="selected"' : '';

            // Определим идентификатор элемента если он не найден в элементе
            $id = is_array( $item ) || is_object( $item ) ? $item[ $id_field ] : $k;

            // Определим значение элемента
            $value = is_string( $item ) ? $item : $item[ $name_field ];

            // Сформируем представление элемента списка
            $result .= '<li '.$id_field_name.'="'.$id.'" '.$class.'>'.$value.'</li>';
        }
    }

    // Вернем результат
    return $result;
}

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

/**
 * Сформировать правильное имя класса с использованием namespace, если оно не указано
 * Функция нужна для обратной совместимости с именами классов без NS
 *
 * @param string $class_name Имя класса для исправления
 * @param string $ns		 Пространство имен которому принадлежит класс
 * @deprecated use \samson\core\AutoLoader::className() and pass full class name to it without splitting into class name and namespace
 * @return string Исправленное имя класса
 */
function ns_classname( $class_name, $ns = 'samson\activerecord' )
{
    if (strpos($class_name, '\\') === false) {
        $class_name = $ns.'\\'.$class_name;
    }

    return \samson\core\AutoLoader::className($class_name);
}

/**
 * Transform classname with namespace to universal form
 * @param string $class_name Classname for transformation
 * @deprecated use \samson\core\AutoLoader::className()
 * @return mixed Transformed classname in universal format
 */
function uni_classname( $class_name )
{
    return \samson\core\AutoLoader::className($class_name);
}
