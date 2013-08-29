<?php
use samson\core\SamsonLocale;

/**
 * Get only folder structure from path.
 * Function can also be used for getting namespace withou classname
 * If file name specified in path it will be removed, if no
 * filename in path - nothing will happen
 * 
 * @return string Folder structure 
 */
function pathname( $path )
{
	// If win or NS path with slash('\')
	if( ($p = strrpos( $path, __NS_SEPARATOR__ )) === false )
	{
		// Get position on last *nix slash
		$p = strrpos( $path, '/' );
	}
		
	// Cut unnessesary part of the path 
	return $p !== false ? substr( $path, 0, $p ) : $path; 
}

/**
 * Normalize path to *nix style with slash('/'), removing
 * double slashes
 * 
 * @param string $path Path to be normalized
 * @return mixed Normalized path
 */
function normalizepath( $path ){ return str_replace( array('\\\\','///', '//','\\'), '/', $path );}

/**
 * Return only class name without namespace
 * @param string $class_name class name
 * @return string class name without namespace
 */
function classname( $class_name )
{
	if( ($p = strrpos( $class_name, __NS_SEPARATOR__ )) !== false ) $class_name = substr( $class_name, $p + 1 );
	 
	return $class_name;
}

/**
 * Return only namespace name from class name
 * @param string $class_name class name
 * @return string Namespace name
 */
function nsname( $class_name ){ return substr( $class_name, 0, strrpos( $class_name, __NS_SEPARATOR__ ));}

/**
 * Изменить регистр ключей массива, с поддержкой UNICODE
 * 
 * @param array $arr	Массив для преобразования ключей
 * @param int 	$c		Регистр в которой необходимо преобразовать ключи массива
 * @return array Массив с преобразованными ключами
 */
function & array_change_key_case_unicode( array & $arr, $c = CASE_LOWER) 
{	
	// Результирующий массив
	$ret = array();
	
	// Определим в какой регистр преобразовывать ключи
	$c = ($c == CASE_LOWER) ? MB_CASE_LOWER : MB_CASE_UPPER;
	
	// Создадим новый массив с преобразованными ключами
	foreach ( $arr as $k => $v ) $ret[ mb_convert_case( $k, $c, "UTF-8" ) ] = $v;
	
	// Верем полученный массив
	return $ret;
}

/**
 * Выполнить рекурсивную очистку массива от незаполненый ключей
 * с его последующей перенумерацией.
 * @param array $input Массив для рекурсивной очистки и перенумерации
 * @return array Перенумерованный очищеный массив
 */
function array_filter_recursive( array & $input )
{
	// Переберем полученный массив
	// Используем foreach потому что незнаем какой массив
	// имеет ли он ключи или только нумерацию
	foreach ( $input as & $value )
	{
		// Если это подмассив
		if ( is_array( $value ) )
		{
			// Выполним углубление в рекурсию и перенумерируем полученный "очищенный" массив
			$value = array_values( array_filter_recursive( $value ) );
		}
	}

	// Выполним фильтрацию массива от пустых значений
	return array_filter($input);
}

/**
 * Обновить индексацию в таблице "Маппинга"
 * 
 * @param string $mapper_selector
 */
function mapping_renew_indeces( $mapper_selector )
{
	// Коллекция для хранения индексов таблиц 
	$e = array();
	
	// Переберем все записи в общей таблице "Маппинга"
	foreach ( $mapper_selector()->all() as $r)
	{
		// Если для данной таблицы мы еще не создали коллекцию индексов
		if( ! isset( $e[ $r->Entity ] ) )$e[ $r->Entity ] = array();
	
		// Сформируем уникальный индекс для записи в общей и внутренней таблице
		$key = $r->Entity . '_' . sizeof($e[ $r->Entity ]);
	
		// Сохраним индекс
		$e[ $r->Entity ][] = $key;
	
		// Запишем индекс в текущую строку общей таблицы
		$r->EntityID = $key;
	
		// Сохраним строку
		$r->save();
	}
}

/**
 * Оберточная функция для print_r только выводит все красиво в HTML
 *
 * @param mixed $data Объект для представления
 */
function print_a( $data, $return = FALSE )
{	
	// Преобразуем стандартную функцию вывода массива/объекта для HTML
	$out = str_replace( array("\n"," "), array('<br>','&nbsp;'), print_r( $data, TRUE ));	
	
	// Если необходимо вывести результат в поток вывода
	if( !$return ) echo $out; 
	
	// Вернем что получили
	return $out;  
}

/**
 * Обработать шаблон сообщения со специальными маркерами
 * для их выделения в нем
 *
 * @param string 	$pattern 	Строка для обработки
 * @param array 	$args
 */
function debug_parse_markers( $pattern, array & $args = NULL )
{
	// Текущий номер аргумента для подмены
	$a_ind = 0;

	// Сохраним количество аргументов условия
	$a_count = sizeof( $args );

	// Текущий отступ в поисковом шаблоне
	$l_pos = 0;

	// Защита от ошибок
	if( strlen( $pattern ) > 0 && $a_count > 0 )
	{
		// Выполним циклическую обработку шаблона с поиском маркера
		// учитываю позицию предыдкщего маркера
		while( ($l_pos = strpos( $pattern, '##', $l_pos )) !== FALSE  )
		{
			// Проверим не вылазит ли индекс аргумента за пределы
			if( $a_ind < $a_count )
			{
				// Получим значение для текущего маркера
				$a_value =  '<b>' . $args[ $a_ind++ ] . '</b>';
					
				// Заменим найденный маркер
				$pattern = substr_replace( $pattern, $a_value, $l_pos, 2 );
			}
		}
	}

	// Вернем результат
	return $pattern;
}

/**
 * Трассировка сообщения в поток вывода
 * Оберточная функция для echo с переходом но новую строку
 * 
 * @param string 	$text 		Текст для вывода
 * @param boolean 	$totextarea	Выводить результат в textarea
 */
function trace( $text = '', $totextarea = false )
{
	if( !$totextarea ) echo '' .print_a($text,TRUE).'<br>'."\n"; 
	else echo '<textarea>'.print_r( $text,true).'</textarea>';
	
	return FALSE; 
}

function & debug_parse_args( array $args )
{
	// Соберем сюда описание аргументов функции
	$result = array();	
	
	// Переберем аргументы функции
	$args_count = sizeof( $args ); 
	for ($j = 0; $j < $args_count; $j++ )
	{
		// Получим аргумент
		$arg = & $args[ $j ];
	
		// Опредилим тип арргумента и сформируем для него "понятное" представление
		switch( gettype($arg) )
		{
			case 'array': 	
				// Вырежем первые 5 элементов массива
				$array_values = array_slice( $arg, 0, 5 );
				// Рекурсивно разберем элементны массива
				$result[] = 'Array#'.sizeof($arg).'(' . implode(', ', debug_parse_args($array_values)). ')'; 	
			break;
			case 'string': 	$result[] = '&#34;' . $arg . '&#34;'; 		break;
			case 'object':
				$class = 'Class:' . get_class( $arg );
				// Если передана запись из БД
				if(get_parent_class($arg) == 'dbRecord') $class .= '('.$arg->id.')';				
				else if(get_class($arg) == 'Module') $class .= '('.$arg->id().')';
				$result[] = $class;
			break;
			case 'NULL': 	$result[] = 'NULL'; 						break;
			// Незнаем как преобразовать
			default:		$result[] = $arg;
		}
	}
	
	// Вернем результат
	return $result;	
}

function debug_to_string( $errno = NULL, $file = NULL, $line = NULL, $class = NULL, $function = NULL, $args = NULL )
{
	// Если первым аргументом передан массив, создадим из него переменные
	if( is_array( $errno ) ) { extract( $errno ); $errno = NULL; }
	
	// Сформируем представление функции, с аргументами
	$html = '';
	
	// Если указаны аргументы функции
	if( isset( $errno ) ) $html .= $errno.', ';
	
	// Если указаны аргументы функции
	if( isset( $file ) ) $html .= $file.', ';
	
	// Если указана строка
	if( isset( $line ) ) $html .= '<b>стр. '.$line.'</b>, ';
	
	// Если это метод класса - укажем и его
	if( isset( $class ) ) $html .= '<b>'.$class.'::</b>';	
	
	// Если указана функция
	if( isset( $function ) ) $html .= '<b>'.$function.'</b>( ';
	
	// Если указаны аргументы функции
	if( isset( $args ) ) $html .= implode( ', ', debug_parse_args( $args ) ) . ' )';
	
	return $html;
}

/**
* Сгенерировать HTML представление для стека вызовов
* @param array $backtrace Стек вызовов
* @return string HTML представление для стека вызовов
*/
function debug_backtrace_html( $message, array $backtrace = NULL )
{
	// Сюда соберем результат
	$html = array();

	// Получим размера стека вызовов
	$stack_size = sizeof( $backtrace );

	// Переберем элементы стека вызовов начиная с 3-го элемента
	for ($i = 1; $i < $stack_size; $i++) $html[] = debug_to_string( $backtrace[ $i ] );	
	
	// Create unique id fo input [checkbox]
	$id = '__error_backtrace_'.rand(0, 1000);
	
	// Вернем преобразованный в HTML стек вызовов
	return '<ul class="_core_error_text">
				<li class="_core_error_function">
					<input type="checkbox" class="elapsed_click" id="'.$id.'">
					<label for="'.$id.'"></label>' . $message . '
					<ul class="_core_error_stack"><li>' . implode( '</li>
						<li>' ,$html ) . '</li>
					</ul>
				</li>
			</ul>';
}

/**
 * Профайлер с выводом отладочного сообщения, отметки времени, и промежутка
 * времени прошедшего с последнего вызова данной функции
 * 
 * @param string $text Отладочное сообщение об отметки времени
 * @return string Сообщение об профайлинге
 */
function elapsed( $text = '' )
{
	// Переменная для сохранения последнего момента времени вызова данного метода
	static $l = __SAMSON_T_STARTED__;
			
	// Получим текущую отметку времени
	$c = microtime(TRUE); 
	//trace(number_format($c-__SAMSON_T_STARTED__,5).' - '.number_format($c-$l,5).' -- '.print_a($text,TRUE));
	// Выведем сообщение про текущую отметку времени и время прошедшее с последнего времени
	e( number_format($c-__SAMSON_T_STARTED__,5).' - '.number_format($c-$l,5).' -- '.print_a($text,TRUE), D_SAMSON_DEBUG );
	
	// Сохраним отметку времени последнего вызова данной функции
	$l = $c; 
}

/**
 * Универсальный метод для установки значения переменной с учетом
 * приоритетов устанавливаемых значений.
 * Значение переменной выставляется только в том случаи если оно еще не выставлено
 * и равно NULL. Тогда метод по очереди подставляет переданные значения для
 * переменной, и устанавливает ей первое выставленное значение не равное NULL
 *
 * @param mixed $value Указатель на устанавливаемую переменную
 * @param mixed $value1 Альтернативное значение переменной
 * @param mixed $value2 Альтернативное значение переменной
 */
function & uniset( & $value = NULL, $value1 = NULL, $value2 = NULL )
{	
	// Если устанавливаемая переменная не задана
	if( ! isset($value) ) 
	{
		// Если задан второй параметр то установим его иначе третий
		$value = ( isset( $value1 ) ? $value1 : $value2); 
	}
	
	// Вернем указатель на установленную переменную
	return $value; 
}

/**
 * Сгенерировать HTML элементы не нумерованного списка доступных локализаций сайта
 * @return string HTML элементы не нумерованного списка 
 */
function html_locale_ul()
{
	// Соберем HTML представление сюда
	$result = '';	
	
	// Описание действия
	$desc = array(
		'UA' => 'title="Відкрити українську версію сайта"',
		'RU' => 'title="Открыть русскую версию сайта"',
		'EN' => 'title="Open English version"',
	);

	// Переберем разрешенные локали для сайта
	foreach( SamsonLocale::get() as $locale )
	{		
		// Определим если это текущая локаль	
		$class = locale() == $locale ? 'class="active"' : '';
	
		// Установим правильное представление локали для вывода
		$locale_name = $locale == '' ? 'RU' : strtoupper($locale);
						
		// Сформируем элемент списка
		$result .= '<li '.$desc[ $locale_name ].' id="'.$locale_name.'_locale"><a '.$class.' href="'.url()->build( url()->text(), 'locale', $locale).'">'.$locale_name.'</a></li>';
	}
	
	return $result;
}

/**
 * Перевести в верхний регистр первую букву в строке
 * 
 * @param string $string 	Строка для преобразования
 * @param string $encoding 	Кодировка
 * @return string Преобразованная строка
 */
function utf8_ucfirst( $string, $encoding = 'utf-8'  )
{
	$strlen = mb_strlen($string, $encoding);
	
	$firstChar = mb_substr($string, 0, 1, $encoding);
	
	$then = mb_substr($string, 1, $strlen - 1, $encoding);
	
	return mb_strtoupper($firstChar, $encoding) . $then;	
}

/**
 * Обрезать строку "аккуратно" до определенной длины
 * 
 * @param string 	$string Входная строка
 * @param integer 	$width	Желаемая длина строка в символах
 * @param string 	$break  Символ разделения строк
 * @param string 	$cut	Флаг обрезания больших слов
 * @return mixed Обрезанную строку
 */
function utf8_wordwrap($string, $width=75, $break="\n", $cut=FALSE)
{
	if($cut) {
		// Match anything 1 to $width chars long followed by whitespace or EOS,
		// otherwise match anything $width chars long
		$search = '/(.{1,'.$width.'})(?:\s|$)|(.{'.$width.'})/uS';
		$replace = '$1$2'.$break;
	} else {
		// Anchor the beginning of the pattern with a lookahead
		// to avoid crazy backtracking when words are longer than $width
		$search = '/(?=\s)(.{1,'.$width.'})(?:\s|$)/uS';
		$replace = '$1'.$break;
	}
	return preg_replace($search, $replace, $string);
}

/**
 * Обрезать строку до определенной длины и если операция была выполнена
 * то добавить маркер в конец строки
 * 
 * @param string 	$str			Строка для обрезания
 * @param integer 	$length			Размер выходной строки
 * @param string 	$limit_marker	Строка добавляемая в случаи обрезания строки
 * @return string Обрезанную строку с маркером обрезания если оно было выполнено
 */
function utf8_limit_string( $str, $length, $limit_marker = '...' )
{	
	// Если длина строки превышает требуемую
	if( strlen( $str ) > $length ) $str = mb_substr( $str, 0, $length, 'UTF-8' ).$limit_marker;
	
	// Вернем строку
	return $str;
}

/**
 * Закодировать данные для отправки в E-mail
 *
 * @param string $str 		Данные для кодировки
 * @param string $charset	Кодировка
 * @return string	Закодированные данные для передачи
 */
function mail_encode( $str, $charset ) { return '=?' . $charset . '?B?' . base64_encode($str) . '?='; }

/**
 * Отправить HTML письмо
 *
 * @param string $to		Кому
 * @param string $from		От кого
 * @param string $message	Письмо
 * @param string $subject	Тема письма
 */
function mail_send( $to, $from = 'info@samsonos.com', $message = '', $subject = '', $from_user = '' )
{
	// Обработаем кирилицу в поле: От кого
	$from_user = mail_encode( $from_user, 'UTF-8');

	// Обработаем кирилицу в поле: Тема письма
	$subject = mail_encode( $subject, 'UTF-8' );

	// Установим необходимые заголовки
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
	$headers .= 'From: '.$from_user.'<'.$from.'>'."\r\n";

	// Добавим в сообщение HTML тэги
	$message = '<html><head></head><body>'.$message.'</body></html>';

	// Если письмо отправленно вернем 1
	return mail( $to, $subject, $message, $headers );	
}

/**
 * Просклонять слово
 *
 * @param array $words массив с 3-мя элеметами - вариантами написания слов
 * @param int $n число
 * @return string Сгенерированный код формы для отправки на LIQPAY
 */
function incline_word($words,$n){
	if($n%100>4 && $n%100<20){
		return $words[2];
	}
	$a = array(2,0,1,1,1,2);
	return $words[$a[min($n%10,5)]];
}

/**
 * Отформатировать дату на русском языке
 *
 * @param int $date метку времени Unix
 * @return string дата на русском языке
 */
function date_format_ru($date)
{
	$date = strtotime($date);
	$day=date("d", $date);
	$month_en=date("F", $date);
	$year=date("Y", $date);
	$days_of_week_en=date("l", $date);
	$month_ru=array(
			'January'=>'января',
			'February'=>'февраля',
			'March'=>'марта',
			'April'=>'апреля',
			'May'=>'мая',
			'June'=>'июня',
			'July'=>'июля',
			'August'=>'августа',
			'September'=>'сентября',
			'October'=>'октября',
			'November'=>'ноября',
			'December'=>'декабря',
	);
	$days_of_week_ru=array(
			'Monday'=>'Понедельник',
			'Tuesday'=>'Вторник',
			'Wednesday'=>'Среда',
			'Thursday'=>'Четверг',
			'Friday'=>'Пятница',
			'Saturday'=>'Суббота',
			'Sunday'=>'Воскресенье',
	);
	$month= $month_ru[$month_en];
	$days_of_week=$days_of_week_ru[$days_of_week_en];
	$date="$days_of_week, $day $month $year года";
	return $date;
}

/**
 * Получить все классы наследующие переданный класс
 * 
 * @param string $parent Имя класса родителя
 * @return array Коллекцию классов наследующих указанный класс
 */
function get_child_classes( $parent )
{
	$ancestors = array();
	
	// Перерберем все задекларированные классы и посмотрим те которые наследуют нужный класс
	foreach ( get_declared_classes() as $class )
	{
		// Если класс наследует нужный нам класс 
		if( in_array( $parent, class_parents( $class ) )) $ancestors[] = $class;		
	}
	
	return $ancestors;
}