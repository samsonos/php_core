<?php
namespace samson\core;

/**
 * Общие методы для работы с файлами и каталогами
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 *
 */
class File
{
	/**
	 * Коллекция связей между MIME-type и расширениями файлов
	 * @var array
	 */
	public static $MIMEExtension = array
	(
		'text/css' 					=> 'css',
		'application/x-font-woff' 	=> 'woff',
		'application/x-javascript' 	=> 'js',
		'text/html;charset=utf-8'	=>'htm',
		'text/x-component' 		=> 'htc', 
		'image/jpeg' 			=> 'jpg',
		'image/pjpeg' 			=> 'jpg',
		'image/png' 			=> 'png',
		'image/x-png' 			=> 'png',
		'image/jpg' 			=> 'jpg',
		'image/gif' 			=> 'gif',
		'text/plain' 			=> 'txt',
		'application/pdf' 		=> 'pdf',
		'application/zip' 		=> 'zip',
		'application/rtf' 		=> 'rtf',
		'application/msword' 	=> 'doc',
		'application/msexcel' 	=> 'xls',
		'application/vnd.ms-excel'  => 'xls',		
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx', 			
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
		'application/octet-stream' 	=> 'sql',
		'audio/mpeg'	=> 'mp3'
	); 
	
	/**
	 * Коллекция связей между расширениями файлов и их MIME-type 
	 * @var array
	 */
	public static $ExtensionMIME = array();
	
	/**
	 * Коллекция расширений файлов которые являются картинками
	 * @var array
	 */
	public static $ImageExtension = array
	(
		'jpg' => 'jpg',
		'jpeg' => 'jpeg',
		'png' => 'png',
		'jpg' => 'jpg',
		'gif' => 'gif'
	);	
	
	/**
	 * Проверить существует ли файл по указанному пути
	 *
	 * @param string $path Путь к файлу
	 * @param string $file Имя файла для поиска
	 */
	public static function find( $path, $file ){ return file_exists( $path.'/'.$file); }
	
	/**
	 * Скопировать файлы и папки используя рекурсивный перебор
	 * 
	 * @param string $path Путь к источнику для копирования
	 * @param string $dest Путь к месту куда необходимо выполнить копирование
	 */
	public static function copy( $path, $dest )
	{			
		// Если это папка
		if( is_dir($path) )
		{
			// Создадим папку в том месте куда нужно копировать, если её там нет
			if( ! file_exists( $dest ) ) mkdir( $dest, 0755, TRUE );
			
			// Получим содержание текущей папки
			$files = scandir( $path );
			
			// Получим количетсво файлов в папке
			$files_count = sizeof( $files );
			
			// Переберем полученные файлы
			for ($i = 2; $i < $files_count; $i++) 			
			{
				// Получим имя текущего файла
				$file = & $files[ $i ];
				
				// Получим полный путь к файлу источнику
				$source_path = $path.'/'.$file;
				
				// Получим полный путь к новому файлу
				$dest_path = $dest.'/'.$file;
					
				// Углубимся в рекурсию
				self::copy( $source_path, $dest_path );						
			}
			
			return TRUE;
		}
		// Просто скопируем файл
		else if( is_file( $path ) ) 
		{
			// Если передана папка как новый файл - получим имя файла
			if( is_dir( $dest ) ) $dest .= pathinfo( $path, PATHINFO_FILENAME ).'.'.pathinfo( $path, PATHINFO_EXTENSION );
			
			return copy( $path, $dest );	
		}
		// Выходим
		else return FALSE;			
	}	
	
	/**
	 * Получить список файлов каталога
	 * @param string 	$path Путь к каталогу
	 * @param string 	$type Фильтр для типа собираемых файлов
	 * @param array  	$result Результат работі рекурсивной функции
	 * @param string 	$modyfier Модификатор пути к файлам для изменения их пути
	 * @param string 	$max_level Максимальная глубина работы метода
	 * @param integer	$level Текщий уровень рекурсии
	 * @param array		$restrict Коллекция папок которые необходимо пропускать
	 * @return array Коллекция файлов в каталоге
	 */
	public static function dir( $path, $type = NULL, $modyfier = '', & $result = array(), $max_level = NULL, $level = 0, $restrict = array( '.git','.svn','.settings') )
	{
		// Если установлено ограничение на глубину - выйдем
		if( isset( $max_level ) && $level > $max_level ) return $result;
		
		// Откроем папку
		if ( file_exists($path) &&  $handle = opendir( $path ) )
		{			
			/* Именно этот способ чтения элементов каталога является правильным. */
			while ( FALSE !== ( $entry = readdir( $handle ) ) )
			{			
				// Если это зарезервированные ресурсы - пропустим
				if( $entry == '..' || $entry == '.') continue;
				
				// Если эта папка запрещена
				if( in_array( $entry, $restrict ) ) continue;
				
				// Сформируем полный путь к ресурсу
				$full_path = $path.'/'.$entry;
					
				// Проверим является ли запись файлом
				if( is_file( $full_path ) )
				{					
					// Если установлен Фильтр по типам файлов
					if( isset( $type ) )
					{						
						// Если передан только один тип файла преобразуем в массив
						if( ! is_array( $type ) ) $type = array( $type );
						//trace(pathinfo( $full_path, PATHINFO_EXTENSION ));
						// Если расширение файла не подходит - пропускаем
						if( !in_array( pathinfo( $full_path, PATHINFO_EXTENSION ), $type )) continue;
					}
						
					// Добавим файл
					$result[] = normalizepath($modyfier.$full_path);
				}
				// Если это подкаталог то углубимся в рекурсию
				else if( is_dir( $full_path ) ) self::dir( $full_path, $type, $modyfier, $result, $max_level, ++$level, $restrict );				
			}
	
			// Закроем чтение папки
			closedir($handle);
		}
		//else return e( 'Ошибка открытия папки(##)', E_SAMSON_CORE_ERROR, array( $path ) );
	
		// Сортируем
		if( sizeof( $result )) sort( $result );
		
		// Соберем массив в строку
		return $result;
	}		
	
	/**
	 * Очистить путь
	 * Если передан путь к папке то очистим все её файлы(содержимое)
	 * Если передан путь к файлу то удали его
	 * 
	 * @param string $path Путь к удаляемому ресурсу
	 * @return TRUE / FALSE 
	 */
	public static function clear( $path, $type = NULL )
	{
		// Если передан путь к папке то удалим все файлы в ней
		if( is_dir( $path ) )
		{
			// Получим список всех файлов в папке
			$files = self::dir( $path, $type );
			
			// Получим количество файлов
			$files_count = sizeof($files);
			
			// Удалим все файлы
			for ($i = 0; $i < $files_count; $i++) unlink( $files[ $i ]);
			
			return TRUE;
		}
		// Если передан путь к файлу то просто удалим его
		else if( file_exists( $path ) )
		{
			unlink( $path );
			return TRUE;
		}
		
		return FALSE;
	}
	
	public static function remove( $dir )
	{
		foreach( glob($dir . '/*') as $file ) 
		{
        	if(is_dir($file)) self::remove( $file );
        	else unlink($file);
	    }
	    
	    rmdir( $dir );
	}
	
	/**
	* Определить расширение файла по его MIME типу
	*
	* @param string $mime_type MIME тип файла	
	* @return string Расширение файла
	*/
	public static function getExtension( $mime_type )
	{	
		// Обработаем тип получаемого файла и вернем его расширение если файл поддерживается
		if( isset( self::$MIMEExtension[ $mime_type ] ) ) return self::$MIMEExtension[ $mime_type ];
		
		// Ничего не вышло =(
		return FALSE;
	}
	
	/**
	 * Определить MIME тип файла по его расширению 
	 *
	 * @param string $path Путь к файлу или его имя
	 * @return string MIME тип файла
	 */
	public static function getMIME( $path )
	{
		// Получим расширение файла
		$file_ext = pathinfo( $path, PATHINFO_EXTENSION );				
		
		// Обработаем тип получаемого файла и вернем его расширение если файл поддерживается
		if( isset( self::$ExtensionMIME[ $file_ext ] ) ) return self::$ExtensionMIME[ $file_ext ];	
	
		// Ничего не вышло =(
		return 'application/octet-stream';
	}
	
	/**
	 * Определить является ли указанный файл картинкой
	 *
	 * @param string $path Путь к файлу или его имя
	 * @return boolean Является ли данный файл картинкой
	 */
	public static function isImage( $path )
	{
		// Получим расширение файла - всегда берем первые 3 символа
		$file_ext = trim( substr( pathinfo( $path, PATHINFO_EXTENSION ), 0, 3 ));
			
		// Проверим является ли расширение файла картинкой
		return isset( self::$ImageExtension[ $file_ext ] );
	}
}

// Сформируем обратный массив связей между расширением файла и его MIME-type
File::$ExtensionMIME = array_flip( File::$MIMEExtension );
?>