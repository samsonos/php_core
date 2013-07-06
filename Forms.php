<?php

use Samson\ActiveRecord\dbSimplify;

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
	if(dbSimplify::parse( $entity_name, $db_obj, $db_obj )) $obj_id = $db_obj->id;
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
	$entity_name = ns_classname($entity_name,'samson\activerecord');	
	
	// Проверим существует ли указанная сущность
	if( ! class_exists( $entity_name )) return FALSE;
	
	// Идентификатор выбранного элемента
	$obj_id = NULL;
	
	// Попытаемся безопасно получить указатель на переданный объект из БД
	if (is_array($db_obj))
	{
		foreach ($db_obj as $item)
		if (dbSimplify::parse( $entity_name, $item, $item )) $obj_id[] = $item->id;
	}
	elseif (dbSimplify::parse( $entity_name, $db_obj, $db_obj )) $obj_id = $db_obj->id;
	
	// Работаем с активаными записями(не удаленными)
	$query = dbQuery( $entity_name )->cond( 'Active', 1 )->exec(); 
	
	// Выполним запрос на получение записей из БД
	if( dbSimplify::query( $query, $db_objs, TRUE ) )
	{
		// Результирующий массив данных для формирования HTML элемента формы
		$select_data = array();	
		
		// Если нужно - добавим пункт не выбрано
		if( isset($add_unselected) ) $select_data[] = array('id' => 0, 'name' => $add_unselected );
		
		// Переберем полученный объекты из БД
		$db_objs_count = sizeof( $db_objs );
		for ($i = 0; $i < $db_objs_count; $i++) 
		{
			// Получим указатель на текущицй объект из выборки
			$obj = & $db_objs[ $i ];
			
			// Добавим данные в массив для HTML представления
			$select_data[] = array
			(
				'id' 			=> $obj->id,
				'name' 			=> $obj->$name_attr,			
				'description' 	=> $obj->$desc_attr
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
?>