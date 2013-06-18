<?php 
namespace Samson\Core;

use Samson\ResourceCollector\ResourceCollector;

/**
 * Ядро фреймворка SamsonPHP
 * 
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 * @version 4.5.5
 */
final class Core implements iCore
{	
	/** Текущая версия фреймворка */
	const VERSION = '5.1.1';
	
	/**
	 * Стек загруженных в ядро РЕАЛЬНЫХ и ВИРТУАЛЬНЫЙ модулей
	 * @var array
	 */
	public $module_stack = array();
	
	/** Указатель а обработчик 404 ошибки */
	protected $e404 = null;
	
	/**
	 * Текущий активный модуль с которым работает ядро
	 * @var Module
	 */
	protected $active = null;
	
	/**
	 * Флаг вывода шаблона
 	 * Используется для ассинхронных ответов без прорисовки шаблона
	 */
	protected $async = FALSE;	
	
	/** Главный шаблон системы */
	protected $template_path = __SAMSON_DEFAULT_TEMPLATE;
	
	/** Путь к текущемуу Веб-приложению */
	protected $system_path = '';

	/** Данные о загружаемом в данный момент модуле */
	protected $loaded_module = array();
	 
	/**	@see iCore::async() */
	public function async( $async = NULL )
	{ 
		// Если передан аргумент
		if( func_num_args() ){$this->async = $async; return $this;} 
		// Аргументы не переданы - вернем статус ассинхронности вывода ядра системы
		else return $this->async; 
	}		
		
	/**	@see iCore::template() */
	public function template( $template = NULL )
	{
		// Если передан аргумент
		if( func_num_args() ){ $this->template_path = $this->active->path().$template;	} 
		// Аргументы не переданы - вернем текущий путь к шаблону системы
		else return $this->template_path;
	}
	
	/** @see iCore::path() */
	public function path( $path = NULL )
	{ 			
		// Если передан аргумент
		if( func_num_args() )  
		{						
			// Сформируем новый относительный путь к главному шаблону системы
			$this->template_path = $path.$this->template_path;  
	
			// Сохраним относительный путь к Веб-приложению
			$this->system_path = $path;
			
			// Установим путь к локальному модулю
			$this->module_stack['local']->path( $this->system_path );
			
			// Выполним инициализацию ядра
			$this->init();
			
			// Продолжил цепирование
			return $this;
		}
		
		// Вернем текущее значение
		return $this->system_path; 		
	}	
	
	/**	@see iModule::active() */
	public function & active( iModule & $module = NULL )
	{
		// Сохраним старый текущий модуль		
		$old = & $this->active;
		
		// Если передано значение модуля для установки как текущий - проверим и установим его
		if( isset( $module ) ) $this->active = & $module;

		// Вернем значение текущего модуля  
		return $old;
	}
			
	/**	@see iCore::module() */
	public function & module( & $_module = NULL )
	{					
		// Ничего не передано - вернем текущуй модуль системы
		if( !isset($_module) && isset( $this->active ) ) return $this->active;	
		// Если уже передан какой-то модуль - просто вернем его
		else if( is_object( $_module ) ) return $_module;
		// Если передано имя модуля	
		else
		{
			// Получим регистро не зависимое имя модуля 
			$_module = mb_strtolower( $_module, 'UTF-8' );			
			
			// Если передано имя модуля то попытаемся его найти в стеке модулей
			if( isset( $this->module_stack[ $_module ] ) ) return $this->module_stack[ $_module ];			
		}		
		
		// Ничего не получилось вернем ошибку
		return e('Не возможно получить модуль(##) системы', E_SAMSON_CORE_ERROR, array( $_module ) );
	}
	
	/** @see iCore::duplicate() */
	public function duplicate( $id, $new_id )
	{
		// Получим регистро не зависимый идентификатор модуля
		$ci_id = mb_strtolower( $id, 'UTF-8' );
		
		// Если указанный модуль существует и он загружен в ядро
		if( isset( $this->module_stack[ $ci_id ] ) )
		{			
			// Получим регистро не зависимый идентификатор клонируемого модуля
			$nci_id = mb_strtolower( $new_id, 'UTF-8' );			
			
			// Создадим дубликат модуля
			$this->module_stack[ $nci_id ] = & $this->module_stack[ $ci_id ]->duplicate( $nci_id );	
			
			// Продолжим цепирование
			return $this;
		}
		// Выведем фатальную ошибку
		else return e( 'Попытка клонирования не существующего модуля системы(##)', E_SAMSON_FATAL_ERROR, array($id) );	
	}
	
	/** @see iCore::unload() */
	public function unload( $_id )
	{
		// Если модуль загружен в ядро
		if( isset($this->module_stack[ $_id ]) )
		{	
			// Очистим коллекцию загруженых модулей
			unset( $this->module_stack[ $_id ] );			
		}		
	}

	/** @see iCore::load() */
	public function load( $id, $path = NULL, $params = array() )
	{			
		// Получим регистро не зависимый идентификатор модуля
		$ci_id = mb_strtolower( $id, 'UTF-8' );	
		
		// Если в систему загружена нужная конфигурация для загружаемого модуля - получим её
		if( isset( Config::$data[ $ci_id ] ) ) $params = array_merge( Config::$data[ $ci_id ], $params );	
		
		// Если мы еще не загрузили модуль в ядро
		if( ! isset( $this->module_stack[ $ci_id ] ) )
		{		
			// Если не указан путь к модулю - считаем что он локальный
			if( ($path === 'local') || (!isset( $path )) )
			{						
				// Создадим локальный модуль
				$module = new CompressableModule( $ci_id, $this->system_path, $params  );				
				
				// Сохраним путь к файлу модели
				$model_path = $this->system_path.__SAMSON_MODEL_PATH.'/'.$ci_id.'.php';
				
				// Подключим файл модели если он существует
				if( file_exists( $model_path ) ) require $model_path;
				
				// Путь к файлу контроллера модуля
				$controller_path = $this->system_path.__SAMSON_CONTOROLLER_PATH.'/'.$ci_id.'.php';
				
				// Подключим файл контроллера если он существует
				if( file_exists( $controller_path ) ) require $controller_path;
			}			
			// Путь к модулю задан - считаем его внешний			
			else 
			{	
				// Обязательно добавим в конец пути к модуля слеш для правильности формирования
				// внутенних путей модуля
				if( $path[ strlen($path) - 1 ] != '/' ) $path .= '/';
				
				// Получим аргументы функции как параметры загружаемого модуляы
				$this->loaded_module = array( 
					'id' 		=> $id, 
					'path' 		=>	$path, 
					'params'	=> $params, 
					'files' 	=> FILE::dir( $path, 'php' )
				);
				
				// Получим список загруженных классов
				$classes = get_declared_classes();
			
				// Совместимость со старыми модулями
				if( file_exists( $path.'include.php') )
				{
					// Прочитаем старый файл подключения
					$include = file_get_contents($path.'include.php');
					
					// Уберем из него все не нужное
					$include = str_replace( array('<?php','<?','?>'), '', preg_replace('/(require|require_once)\s*[^;]*\;/', '', $include ));
					
					// Загрузим оставшиеся в систему
					eval($include);
				}

				// Переберем все файлы модуля и подключим их
				foreach ( $this->loaded_module['files'] as $file ) 
				{					
					// Пропустим папку представлений
					if( strpos( $file, __SAMSON_VIEW_PATH.'/' )  ) continue;
					
					// Совместимость со старыми модулями
					if( basename($file) == 'include.php' ) continue;					
					// Просто подключи файл модуля
					else require_once( $file );									
				}	
				
				// Получим список новых загруженных классов			
				foreach ( array_diff( get_declared_classes(), $classes ) as $class ) 
				{
					// Если этот класс является потомком модуля
					if( in_array( 'Samson\Core\ModuleConnector', class_parents( $class )))
					{								
						// Создадим экземпляр класса для подключения модуля
						$connector = new $class( $ci_id, $path, $params );
		
						// Получим родительский класс
						$parent_class = get_parent_class( $connector );
		
						// Проверим родительский класс
						if( $parent_class !== 'ModuleConnector' )
						{					
							// Переберем загруженные в систему модули
							foreach ( $this->module_stack as & $m )
							{
								// Если в систему был загружен модуль с родительским классом
								if( get_class($m) == $parent_class ) $connector->parent = & $m;
							}
						}

						// Прекратим поиски
						break;
					}
				}				
			}		
		}
		
		// Продожим "ЦЕПИРОВАНИЕ"
		return $this;
	}		
	
	public function generate_template( $template_html, $css_url, $js_url )
	{
		// Добавим путь к ресурсам для браузера
		$head_html = "\n".'<base href="'.url()->base().'">';
		// Добавим отметку времени для JavaScript
		$head_html .= "\n".'<script type="text/javascript">var __SAMSONPHP_STARTED = new Date().getTime();</script>';
		
		// Соберем "правильно" все CSS ресурсы модулей
		$head_html .= "\n".'<link type="text/css" rel="stylesheet" href="'.$css_url.'">';
		// Соберем "правильно" все JavaScript ресурсы модулей
		$head_html .= "\n".'<script type="text/javascript" src="'.$js_url.'"></script>';
		
		// Добавим поддержку HTML для старых IE
		$head_html .= "\n".'<!--[if lt IE 9]>';
		$head_html .= "\n".'<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>';
		$head_html .= "\n".'<![endif]-->';
			
		// Выполним вставку главного тега <base> от которого зависят все ссылки документа
		// также подставим МЕТА-теги для текущего модуля и сгенерированный минифицированный CSS
		$template_html = str_ireplace( '<head>', '<head>'.$head_html, $template_html );
		
		// Так сказать - копирайт =)
		$copyright = '<a style="display:none" target="_blank" href="http://samsonos.com" title="Сайт разработан SamsonOS">Сайт разработан SamsonOS</a>';
		// Вставим указатель JavaScript ресурсы в конец HTML документа
		$template_html = str_ireplace( '</body>', $copyright.'</body>', $template_html );
			
		// Добавим в конец предложения по работе
		$template_html .= '<!-- PHP фреймфорк SamsonPHP v '.Core::VERSION.' http:://samsonos.com  -->';
		$template_html .= '<!-- Нравится PHP/HTML, хочешь узнать много нового и зарабатывать на этом деньги? Пиши info@samsonos.com -->';
		
		return $template_html;
	}
	
	/** @see \Samson\Core\iCore::e404() */
	public function e404( $callable = null )
	{
		// Если передан аргумент функции то установим новый обработчик e404 
		if( func_num_args() ) 
		{
			$this->e404 = $callable;
			
			// Продолжим цепирование
			return $this;
		}
		// Проверим если задан обработчик e404
		else if( is_callable( $this->e404 ) )
		{
			// Вызовем обработчик
			$result = call_user_func( $this->e404, url()->module(), url()->method() );
			
			// Если метод ничего не вернул - считаем что все ок!
			return isset( $result ) ? $result : A_SUCCESS;		
		}
		// Стандартное поведение
		else 
		{
			// Установим HTTP заголовок что такой страницы нет
			header('HTTP/1.0 404 Not Found');
			
			// Установим представление
			$this->active->html('<h1>Запрашиваемая страница не найдена</h1>')->title('Страница не найдена');

			// Вернем успешный статус выполнения функции
			return A_SUCCESS;
		}	
	}
	
	/**	@see iCore::start() */
	public function start( $default )
	{			
		//[PHPCOMPRESSOR(remove,start)]				
		// Проинициализируем оставшиеся конфигурации и подключим внешние модули по ним
		Config::init($this);					
		//[PHPCOMPRESSOR(remove,end)]
		
		// Проинициализируем НЕ ЛОКАЛЬНЫЕ модули
		foreach ($this->module_stack as $id => $module )
		{		
			// Только внешние модули и их оригиналы
			if( get_class($module) != 'Samson\Core\Module' && $module->id() == $id )
			{
				//elapsed('Initializing module: '.$id);
				$module->init();
			}
		}	
		
		// Результат выполнения действия модуля
		$module_loaded = A_FAILED;

		// Получим идентификатор модуля из URL и сделаем идентификатор модуля регистро-независимым 
		$module_name = mb_strtolower( url()->module(), 'UTF-8');		
			
		// Если не задано имя модуля, установим модуль по умолчанию
		if( ! isset( $module_name{0} ) ) $module_name = $default;	
	
		// Если модуль был успешно загружен и находится в стеке модулей ядра
		if( isset( $this->module_stack[ $module_name ] ) )
		{		
			// Установим требуемый модуль как текущий
			$this->active = & $this->module_stack[ $module_name ];			
			
			// Определим класс текущего моуля
			$module_class = get_class( $this->active );				

			// Создадим виртуальный дубликат текущего модуля для вывода представлений
			$this->duplicate( $module_name, '_output' );				

			// Попытаемся выполнить действие модуля указанное в URL, переданим тип HTTP запроса
			$module_loaded = $this->active->action( url()->method() );				
		}
	
		// Если мы не выполнили ни одного контроллера, обработаем ошибку 404
		if( $module_loaded === A_FAILED ) $module_loaded = $this->e404();			

		// Сюда соберем весь вывод системы
		$template_html = '';
	
		// Если вывод разрешен - выведем шаблон на экран
		if( ! $this->async && ($module_loaded !== A_FAILED) )
		{				
			// Сгенерируем шаблон представления
			$template_html = output( $this->template_path, m()->toView() );
			
			// Подготовим HTML код для заполнения шапки шаблона
			$head_html = '';
			
			// Создадим МЕТА теги для выводимой страницы			
			if( isset($this->active->keywords) ) 	$head_html .= '<meta name="keywords" content="' . $this->active->keywords . '">';
			if( isset($this->active->description) ) $head_html .= '<meta name="description" content="' . $this->active->description . '">';
				
			//[PHPCOMPRESSOR(remove,start)]		
			// Сгенерируем необходимые элементы в HTML шаблоне
			$template_html = $this->generate_template( $template_html, ResourceCollector::$collected['css'][ 'url' ], ResourceCollector::$collected['js'][ 'url' ] );
			//[PHPCOMPRESSOR(remove,end)]
			
			// Добавим специальную системную комманду для инициализации фреймворка в JavaScript
			$head_html .= '<script type="text/javascript">var __SAMSONPHP_STARTED = new Date().getTime();if(SamsonPHP){SamsonPHP._uri = "'.url()->text().'"; SamsonPHP._moduleID = "'.$this->active->id().'";SamsonPHP._url_base = "'.url()->base().'";SamsonPHP._locale = "'.locale().'";}</script>';			
			
			// Выполним вставку главного тега <base> от которого зависят все ссылки документа
			// также подставим МЕТА-теги для текущего модуля и сгенерированный минифицированный CSS 
			$template_html = str_ireplace( '</head>', $head_html.'</head>', $template_html );				
			
			// Профайлинг PHP
			$template_html .= '<!-- '.profiler().' -->';
			$template_html .= '<!-- Использовано памяти: '.round(memory_get_usage(true)/1000000,1).' МБ -->';			
			if( function_exists('db')) $template_html .= '<!-- '.db()->profiler().' -->';
		}
		
		// Выведем все что мы на генерили
		echo $template_html;	
	}
	
	/** Инициализировать ядро */
	private function init()
	{			
		// Переберем все локальные модули веб-приложения
		foreach ( FILE::dir( $this->system_path.__SAMSON_CONTOROLLER_PATH ) as $controller )
		{
			// Получим идентификатор локального модуля
			$module_id = str_replace('.php', '', basename($controller) );
		
			// Загрузим локальный модуль в ядро
			$this->load( $module_id );
		}
	}
	
	/** Обработчик автоматической загрузки классов модулей */
	public function __autoload ( $class )
	{		
		// Получим имя самого класса
		$class_name = basename($class);
		
		// Если мы знаем какой модуль загружается в данный моммент
		if( isset( $this->loaded_module ) )
		{
			// Найдем в списке файлов нужный нам по имени класса, так как мы незнаем
			// структуру каталогов модуля поищем класс символьным поиском в списке всех файлов
			// модуля
			if($files = preg_grep( '/\/'.$class_name.'\.php/', $this->loaded_module['files']))
			{					
				// Проверим на однозначность
				if( sizeof($files) > 1 ) return e('Невозможно определить файл для класса: ##', E_SAMSON_CORE_ERROR, $class );
				
				// Получим путь к файлу и подключим нужный файл
				foreach ( $files as $k => $file ) require_once( $file );				
			}			
		}	
	}
	
	/** Конструктор */
	public function __construct()
	{			
		// Установим полный путь к рабочей директории
		$this->system_path = __SAMSON_CWD__.'/';		
		
		// Свяжем коллекцию загруженных модулей в систему со статической коллекцией
		// Созданных экземпляров класса типа "Модуль"
		$this->module_stack = & Module::$instances;		
			 
		// Создадим главный системный модуль
		new CompressableModule( 'system', __SAMSON_PATH__ );		
		
		// Добавим главный локальный модуль, установим путь к нему если это "удаленное" приложение
		new CompressableModule( 'local', $this->system_path );
	
		// Создадим специальный модуль для вывода представление		
		new Module( '_output', $this->system_path );
	
		// Временно установим указатель на единственный модуль системы
		$this->active = & $this->module_stack[ '_output' ];		
				
		// Инициализируем локальные модуль
		$this->init();
			
		// Выполним инициализацию конфигурации модулей загруженных в ядро
		Config::load();

		// Установим обработчик автоматической загрузки классов 
		spl_autoload_register( array( $this, '__autoload'));
	}
	
	/** Магический метод для десериализации объекта */
	public function __wakeup(){	$this->active = & $this->module_stack['local']; }
	
	/** Магический метод для сериализации объекта */
	public function __sleep(){ return array( 'module_stack', 'e404' ); }
}