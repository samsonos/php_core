<?php
namespace samson\core;

/**
 * Интерфейс для обработки маршрутов в системе по 
 * средствам разпознования URL(Uniform recource locator)
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 0.2
 */
interface iURL
{	
	/** Ключ для хранения отметок URL-маршрутов в сессии */
	const S_BOOKMARK_KEY = '_url_bookmark';
	
	/** Ключ для хранения коллекции прошлых URL-маршрутов в сессии */
	const S_PREVIOUS_KEY = '_url_previous';
	
	/** Объем хранилища прошлых URL-маршрутов в сессии */
	const S_PREVIOUS_SIZE = 10;
	
	/**
	 * Добавить/получить закладку
	 * 
	 * Метод позволяет записывать закладки в специальную коллекцию
	 * для дальнейшей работы с ними 
	 * 
	 * @param string 	$name 	Имя закладки
	 * @param boolean 	$return	Флаг получения значения закладки, если ничего не передано то выполняется
	 * 							запись текущего URL-маршрута в закладку
	 */
	public function bookmark( $name = NULL, $return = false );
	
	/**
	 * Построить "правильный" полный URL путь к ресурсу
	 *
	 * @param string $url URL Путь к ресурсу
	 * @return string Сгенерированный URL
	 */
	public function build( $url = '' );
	
	/**
	 * Получить/Установить путь к корню приложения
	 * @param string Новый устанавливаемый путь к корню приложения
	 * @return string Текущий путь к корню приложения
	 */
	public function base( $url_base = NULL );
	
	/**
	 * Выполнить переадресацию на указанный URL
	 *
	 * @param string $url Ссылка для перехода
	 */
	public function redirect( $url = NULL );	
	
	/**
	 * Получить данные по истории URL-маршрутов
	 * @param interger $number Порядковый номер в стеке URL-маршрутов
	 * @return iURL Объект URL маршрут
	 */
	public function history( $number = 0 );
}
