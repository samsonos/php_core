<?php
namespace samson\core;

/** Стандартный путь к папке со словарями */
define('__SAMSON_I18N_PATH', __SAMSON_APP_PATH.'/i18n' );

/** Стандартный путь главному словарю сайта */
define('__SAMSON_I18N_DICT', __SAMSON_I18N_PATH.'/dictionary.php' );

/**
 * Интернализация / Локализация
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 1.0
 */
class i18n 
{	
	/** Текущая локаль */
	public $locale = 'en';
	
	/** Коллекция данных для перевода*/
	public $data = array( 'ru' => array() );
	
	/** Конструктор */
	public function __construct()
	{		
		// Если существует главный словарь 
		if( file_exists( __SAMSON_I18N_DICT ) ) 
		{			
			// Загрузим его содержимое
			$data = include(__SAMSON_I18N_DICT);	
			
			// Пробежимся по локалям в словаре
			foreach ( $data as $locale => $dict )
			{			
				// Создадим словарь для локали
				$this->data[ $locale ] = array();
				
				// Преобразуем ключи 
				foreach ( $dict as $k => $v ) $this->data[ $locale ][ (trim($k)) ] = $v;
			}		 
		}
	}
	
	/**
	 * Translate(Перевести) фразу
	 *
	 * @param string $key 		Ключ для поиска перевода фразы
	 * @param string $locale 	Локаль в которую необходимо перевести
	 * @return string Переведенная строка или просто значение ключа
	 */
	function translate( $key, $locale = NULL )
	{
		// Если требуемая локаль не передана - получим текущую локаль
		if( !isset( $locale ) ) $locale = locale();
		
		// Получим словарь для нужной локали
		$dict = & $this->data[ $locale ];
		
		// Получим хеш строки
		$md5_key = (trim( $key ));
		
		// Попытаемся найти запись в словаре
		if( isset( $dict[ $md5_key ] ) ) return $dict[ $md5_key ];
		// Просто вернем ключ		
		else return $key;
	}
}

/**
 * Translate(Перевести) фразу 
 * 
 * @param string $key 		Ключ для поиска перевода фразы
 * @param string $locale 	Локаль в которую необходимо перевести
 * @return string Переведенная строка или просто значение ключа
 */
function t( $key, $locale = NULL )
{
	// т.к. эта функция вызывается очень часто - создадим статическую переменную
	static $_v;
	
	// Если переменная не определена - получим единственный экземпляр ядра
	if( !isset($_v)) $_v = new i18n();
	
	// Вернем указатель на ядро системы
	echo $_v->translate( $key, $locale );
}