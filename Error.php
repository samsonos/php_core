<?php
namespace samson\core;

//
// Коды ошибок
// Новые коды должны попадать в диапазон ( E_SAMSON_FATAL_ERROR - D_SAMSON_DEBUG )
//

/** Критическая ошибка в системы, после ее срабатывания система прекращает работу */
define( 'E_SAMSON_FATAL_ERROR', 900 );
/** Ошибка при работе с ресурсами системы */
define( 'E_SAMSON_SRC_ERROR', 901 );
/** Ошибка в ядре системы */
define( 'E_SAMSON_CORE_ERROR', 999 );
/** Ошибка в АктивнойЗаписи */
define( 'E_SAMSON_ACTIVERECORD_ERROR', 998 );
/** Ошибка в SQL */
define( 'E_SAMSON_SQL_ERROR', 997 );
/** Ошибка в работе SamsonCMS */
define( 'E_SAMSON_CMS_ERROR', 996 );
/** Ошибка в AUTH */
define( 'E_SAMSON_AUTH_ERROR', 995 );
/** Ошибка в компрессоре системы */
define( 'E_SAMSON_SNAPSHOT_ERROR', 994 );
/** Ошибка в компрессоре системы */
define( 'E_SAMSON_RENDER_ERROR', 993 );

//
// Коды для отладочных сообщений
// Новые коды должны все быть больше D_SAMSON_DEBUG(10000)
//

/**
 * Отладочное сообщение
 */
define( 'D_SAMSON_DEBUG', 10000 );
/**
 * Отладочное сообщение ActiveRecord
 */
define( 'D_SAMSON_ACTIVERECORD_DEBUG', 10001 );
/**
 * Отладочное сообщение в CMS
 */
define( 'D_SAMSON_CMS_DEBUG', 10002 );
/**
 * Отладочное сообщение в AUTH
 */
define( 'D_SAMSON_AUTH_DEBUG', 10003 );


/**
 * Обработчик ошибок SamsonPHP
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 * @version 1.6
 */
class Error 
{	
	/**
	 * Глобальный флаг вывода отладочных сообщений
	 * @var boolean
	 */
	public static $OUTPUT = TRUE;
	
	/**
	 * Коллекция ошибок и предупреждений для отладки и правильной работы
	 * приложения
	 */
	private $errors = array();
	
	/**
	 * Путь к файлу с CSS представлением ошибок
	 * @var string
	 */
	public static $css = NULL;
	
	/**
	 * Указатель на внешнюю функцию для обработки завершения работы скрипта
	 * @var callable
	 */
	public static $shutdown_handler;
	
	/**	 
	 * Конструктор
	 */
	public function __construct()
	{	
		// Уберем стандартный вывод ошибок PHP
		error_reporting( false );
		
		// Зарегистрируем функцию обработчик исключений
		//set_exception_handler( array( $this, 'exception_handler'));
		
		// Обработчик ошибок
		set_error_handler( array( $this, 'handler' ) );			 
		
		// Зарегистрируем функцию обработчик завершения работы системы
		register_shutdown_function( array( $this, 'shutdown' ) );		
		
		// Сформируем "универсально" путь к CSS представлению ошибок
		if( file_exists(__SAMSON_PATH__.'css/error.css') ) self::$css = file_get_contents( __SAMSON_PATH__.'css/error.css');	
	}
	
	/**
	 * Обработчик завершения работы скрипта
	 */
	public function shutdown()
	{		
		// TODO: Create core shutdown routines
		if( !s()->async() )
		{
			// Fix performance
			//[PHPCOMPRESSOR(remove,start)]
			s()->benchmark( __FUNCTION__, func_get_args() );

            $template_html = '<!-- Total time elapsed:'.round( microtime(TRUE) - __SAMSON_T_STARTED__, 3 ).'s -->';
            if( function_exists('db')) $template_html .= '<!-- '.db()->profiler().' -->';
            $template_html .= '<!-- Memory used: '.round(memory_get_peak_usage(true)/1000000,1).' МБ -->';
            $template_html .= '<!-- Benchmark table: -->';

			$l = 0;
			$m = 0;
			foreach (s()->benchmarks as $func => $data )
			{
				// Generate params string
				$params = array();
				if(is_array( $data[2] )) foreach ( $data[2] as $value )
				{
					if( is_string($value) ) $params[] = '"'.$value.'"';
				}
				$params = implode( ',', $params );
					
				$started 		= sprintf( '%5ss', number_format( round($data[0],4), 4 ));
				$elapsed 		= sprintf( ' | %5ss', number_format( round($data[0] - $l,4), 4 ));
				$mem 			= sprintf( ' | %7s МБ',number_format($data[3]/1000000,4));
				$mem_elapsed 	= sprintf( ' | %7s МБ',number_format(($data[3]-$m)/1000000,4));		
					
				$template_html .= '<!-- '.$started.''.$elapsed.$mem.$mem_elapsed.' | '.$data[1].'('.$params.') -->';
					
				// Save previous TS
				$l = $data[0];
				$m = $data[3];
			}
			//[PHPCOMPRESSOR(remove,end)]
			
			echo $template_html;			
		}
		
		// Если установлен обработчик завершения выполнения скрипта - вызовем его
		if( isset( self::$shutdown_handler ) && ( call_user_func( self::$shutdown_handler ) === false )) return null;		
				
		//echo 'Конец';
		
		// Выведем все накопленные ошибки 
		$this->output();	
	}
	
	/**
	 * Обработчик завершения работы скрипта
	 */
	public function output()
	{			
		// Получим последнюю ошибку системы
		$lerror = error_get_last();	

		// Коды ошибок которые останавливают скрипт
		$fatal_codes = array( 1, 4, 16, 64 );		
		
		// Если была фатальная ошибка преобразуем её для вывода
		if( isset($lerror) && in_array( $lerror['type'], $fatal_codes ) ) 
		{			
			// Выполним обработчик ошибки и преобразование её в наше представление
			$this->handler( $lerror['type'], $lerror['message'], $lerror['file'], $lerror['line'], null, debug_backtrace(FALSE) );		
		}
		
		// Соберем представление ошибок в HTML
		$html = '';
		
		// Выведем файл CSS стилей, если он есть! и были ошибки
		if( sizeof($this->errors) && isset( self::$css{0})) $html .= '<style>'.self::$css.'</style>';
		
		// Индекс
		$index = 0;
	
		// Переберем ошибки которые были при выполнения скрипта
		foreach ($this->errors as & $error) 
		{		
			if( $error['class'] == '_core_debug') $html .= '<div class="_core_error _core_debug">'.$error['message'].'</div>';
			else 
			{
				$id = rand(0, 999999999).rand(0,9999999999);
				// Выведем ошибку
				$html .= '<div class="_core_error '.$error['class'].'">
						<input type="checkbox" id="eb_'.$id.'" class="_core_error_check_box">
						<label for="eb_'.$id.'" class="_core_error_label">
					    <span class="_core_error_type">'.$error['type'].'</span>
					    <span class="_core_error_file">'.$error['file'].'</span>
					    <span class="_core_error_line">, стр. '.$error['line'].'</span>
					    '.$error['message'].'</label>
					</div>';
			}
			
			// Удалим ошибку которую мы вывели что бы она не вылезла внизу страницы повторно
			unset($this->errors[ $index++ ]);
		}		
		
		// Выведем блок для завершения вывода
		if( $index ) $html .= '<div style="clear:both;"></div>';		
		
		/*
		// Если єто отпечаток сайта - отправим ошибку на почту
		if( isset($GLOBALS["__CORE_SNAPSHOT"]) && $index )
		try { 	mail_send( 'info@samsonos.com','error@samsonos.com', $html, 'Ошибка PHP:'.url()->build() );	}	
		catch (Exception $e) {}			
		*/
		
		// Если вывод ошибок включен
		if( self::$OUTPUT ) echo $html;		
	}	

	/**
	 * Обработчик ошибок PHP
	 * Сигнатура метода совпадает с требованиями PHP
	 * 
	 * @param numeric 	$errno		Код ошибки
	 * @param string 	$errstr		Описание ошибки
	 * @param string	$errfile	Файл в котором происходит ошибка
	 * @param string 	$errline	Строка в которой была ошибка
	 * @param string 	$errcontext	Контекст в котором произошла ошибка
	 */
	public function handler( $errno , $error_msg, $errfile = NULL, $errline = NULL, $errcontext = NULL, $backtrace = NULL )
	{		
		// Если вывод ошибок включен
		if( ! self::$OUTPUT ) return NULL;
		
		// Если не передан стек вызовов получим текущий
		if( ! isset( $backtrace ) ) $backtrace = debug_backtrace( FALSE );	
			
		// Если сообщение установлено
		if( !isset($error_msg) || ($error_msg == 'Undefined') || (!isset($error_msg{0})) ) return FALSE;
		
		// Указатель на "правильный" уровень стека вызовов
		if( isset($backtrace[ 1 ]) ) $callee = & $backtrace[ 1 ];
		
		// Если ошибка вызывается из функции то укажем ее
		if( isset( $callee['function'] ) ) $error_msg = '<u>' . $callee['function'] . '()</u>&nbsp;' . $error_msg;
		
		// Если ошибка вызывается из класса то укажем его
		if( isset( $callee['class'] ) ) $error_msg = '<u>' . $callee['class'] . '</u>::' . $error_msg;
		
		// Если не передан файл где была ошибка
		if( ! isset( $errfile ) ) $errfile = isset($callee['file']) ? $callee['file'] : '';
		
		// Если не передана линия где была ошибка
		if( ! isset( $errline ) ) $errline = isset($callee['line']) ? $callee['line'] : '';	
		
		// Сформируем стек вызовов для разбора ошибки
		$error_msg = debug_backtrace_html( $error_msg, $backtrace );	
		
		// Описание типа
		$error_type = '';
		
		// CSS класс ошибки
		$error_css = '';
		
		// Определим описание типа ошибки
		switch ( $errno )
		{
			case E_ERROR:						$error_type = 'Фатальная ошибка'; $error_css='_core_fatal_error';	break;
			case E_CORE_ERROR:					$error_type = 'Ошибка ядра PHP'; $error_css='_core_fatal_error';	break;
			case E_SAMSON_FATAL_ERROR:			$error_type = 'Фатальная ошибка SamsonPHP'; $error_css='_core_fatal_error';	break;
			case E_SAMSON_SNAPSHOT_ERROR:		$error_type = 'Ошибка создания отпечатка SamsonPHP'; break;
			case E_SAMSON_RENDER_ERROR:			$error_type = 'SamsonPHP rendering error'; break;
			case E_PARSE:						$error_type = 'Ошибка парсинга'; $error_css='_core_fatal_error';	break;
			case E_COMPILE_ERROR:				$error_type = 'Ошибка компиляции'; $error_css='_core_fatal_error';	break;
			case E_USER_ERROR: 					$error_type = 'Пользовательская ошибка'; 	break;
			case E_WARNING:						$error_type = 'Предупреждение'; 			break;
			case E_CORE_WARNING:				$error_type = 'Предупреждение ядра PHP';	break;		
			case E_COMPILE_WARNING:				$error_type = 'Предупреждение компиляции';break;
			case E_USER_WARNING: 				$error_type = 'Пользовательское предупреждение'; break;			
			case E_NOTICE:						$error_type = 'Замечание'; break;
			case E_USER_NOTICE:					$error_type = 'Пользовательское замечание'; break;
			case E_STRICT:						$error_type = 'Строгая ошибка';				break;
			case E_RECOVERABLE_ERROR:			$error_type = 'Востанавливаемая ошибка';	break;
			case E_DEPRECATED:					$error_type = 'Использование устаревших данных';	break;	
			case E_USER_DEPRECATED:				$error_type = 'Старая функция';				break;
			case E_SAMSON_CORE_ERROR:			$error_type = 'Ошибка SamsonPHP';			break;
			case E_SAMSON_ACTIVERECORD_ERROR:	$error_type = 'Ошибка ActiveRecord';		break;
			case E_SAMSON_CMS_ERROR:			$error_type = 'Ошибка SamsonCMS';			break;
			case D_SAMSON_DEBUG:				$error_type = 'Отладка SamsonPHP';		$error_css='_core_debug';		break;
			case D_SAMSON_CMS_DEBUG:			$error_type = 'Отладка SamsonCMS';		$error_css='_core_debug';		break;
			case D_SAMSON_ACTIVERECORD_DEBUG:	$error_type = 'Отладка ActiveRecord';	$error_css='_core_debug';	break;			
			default:							$error_type = 'Неизвестная ошибка';
		}			
		
		// Сформируем ошибку в виде массива		
		$this->errors[] = $this->toError( $errno, $error_type.':', $error_msg, $errfile, $errline, $error_css );
		
		// Если это фатальная ошибка - остановим выполнение скрипта
		if( $errno == E_SAMSON_FATAL_ERROR ) die();
	}		

	/**
	 * Преобразовать параметры ошибки в массив-ошибку
	 * 
	 * @param numeric 	$errno		Код ошибки
	 * @param string 	$desc		Описание ошибки
	 * @param string 	$errstr		Текст ошибки
	 * @param string	$errfile	Файл в котором происходит ошибка
	 * @param string 	$errline	Строка в которой была ошибка
	 * @return array Массив ошибка
	 */
	private function toError( $errno , $desc, $error_msg, $errfile = NULL, $errline = NULL, $class = NULL )
	{
		return array(
			'code' 		=> $errno,
			'type' 		=> $desc,
			'file' 		=> $errfile,
			'line' 		=> $errline,
			'message' 	=> $error_msg,
			'class'		=> $class
		);
	}
}