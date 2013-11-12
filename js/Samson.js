var __SAMSONPHP_STARTED = new Date().getTime();

/**
 * Специальный объект для передачи данных в JavaScript из 
 * фреймворка SamsonPHP
 * @type SamsonPHP
 */
var SamsonPHP = 
{
	/**
	 * Строка запроса из URL
	 * @type {String}
	 */
	_uri :'',
		
	/**
	 * Идентификатор текущего модуля системы
	 * @type {String}
	 */
	_moduleID : '',
	
	/**
	 * URL путь к корню текущего веб-приложения
	 * @type {String}
	 */
	_url_base : '', 
	
	/**
	 * Получить затраченное время с момента начала выполнения скрипта и с последнего вызова
	 * данной функции
	 * @memberOf SamsonPHP
	 * 
	 * @returns {String} Профайлинговую информацию о затраченом времени ( С начала | C последнего вызова )
	 */
	elapsed : function()
	{ 
		// Если мы еще не вызывали данную функцию
		if( this.l === undefined ) this.l = __SAMSONPHP_STARTED;
		
		// Текущая отметка времени
		var c = new Date().getTime();				
		
		// Выведем данные о профайлинге
		var t = (c-__SAMSONPHP_STARTED)+' | '+(c-this.l);
		
		// Зафиксируем текущую отметку времени
		this.l = c; 
		
		// Вернем результат
		return t;
	},
	
	/**
	 * Получить идентификатор текущего модуля системы
	 * 
	 * @memberOf SamsonPHP
	 * 
	 * @returns {String} Идентификатор текущего модуля системы
	 */
	moduleID : function(){ return SamsonPHP._moduleID;	},
	
	/**
	 * Получить путь к корню текущего веб-приложения
	 * Метод может автоматически сформировать URL если в него передано
	 * любое количество аргументов
	 * 
	 * @memberOf SamsonPHP
	 * 
	 * @param {String} [arg] Параметры для формирования URL
	 * @returns {String} Путь к корню текущего веб-приложения
	 */
	url_base : function()
	{ 	
		// Результирующая ссылка
		var result = [];
		 
		// Переберем все переданные аргументы и добавим их в массив
		if( arguments ) for (var n = 0; n < arguments.length; n++) result.push( arguments[ n ] );
		
		// Вернем "правильный" к требуемому ресурсу 
		return SamsonPHP._url_base + result.join( '/' );	
	},	
	
	/**
	 * Получить путь к корню текущего веб-приложения относительно текущего модуля
	 * Метод может автоматически сформировать URL если в него передано
	 * любое количество аргументов
	 * 
	 * @memberOf SamsonPHP
	 * 
	 * @param {String} [arg] Параметры для формирования URL
	 * @returns {String} Путь к корню текущего веб-приложения
	 */
	module_url : function()
	{ 		
		// Результирующая ссылка
		var result = [];
		 
		// Переберем все переданные аргументы и добавим их в массив
		if( arguments ) for (var n = 0; n < arguments.length; n++) result.push( arguments[ n ] );
		
		// Вернем "правильный" к требуемому ресурсу 
		return SamsonPHP._url_base + SamsonPHP._moduleID + '/' + result.join( '/' );	
	},	
	
	/**
	 * Выполнить универсальный запрос для выполнения действия контроллера модуля
	 *  
	 * @memberOf SamsonPHP
	 * @param url				Адресс действия контроллера модуля
	 * @param responseHandler	Обработчик ответа контроллера
	 * @param errorHandler		Обработчик ошибок произошедших во время выполнения действия контроллера
	 * @param beforeHandler		Предобработчик запроса, должен вернуть truе, что бы запрос выполнился
	 * @return SamsonPHP Указатель на самого себя
	 */
	action : function( url, responseHandler, errorHandler, beforeHandler )
	{
		// Определим результат валидации
		var beforeResult = ( beforeHandler ) ? beforeHandler() : true;	
		
		// Если предобработчик вернул положительный результат
		if( beforeResult )
		{
			// Если задана ссылка - выполним ассинхронный запрос к действию контроллера
			if( url ) s.ajax( url, function( serverResponse )
			{		
				// Выполним универсальный обработчик ответа от контроллера
				SamsonPHP.handleRequest( serverResponse, responseHandler, errorHandler ); 
			});
		}
		
		return this;
	},
	
	/**
	 * Универсальный обработчик ответа полученного после выполнения действия контроллера модуля
	 * Объект может содержать следующую структуру( * обязательный элемент):
	 * 		Имя поля	| Описание
	 * 		------------+--------------------------------------------------------------------------------------------
	 * 		"status" *	| Результат выполнения действия контроллера (1,0)
	 * 		"error"		| Текст ошибки которая произошла во время выполнения действия контроллера	 
	 * 
	 * @param serverResponse 	Ассинхронный ответ полученный от сервера в формате JSON
	 * @param responseHandler	Обработчик ответа контроллера
	 * @param errorHandler		Обработчик ошибок произошедших во время выполнения действия контроллера
	 */
	handleRequest : function( serverResponse, responseHandler, errorHandler )
	{
		// Преобразуем ответ от сервера в объект
		try{serverResponse = JSON.parse( serverResponse );}
		// Обработаем исключение
		catch(e){ alert('Ошибка обработки ответа полученного от сервера, повторите попытку отправки данных'); };		
					
		// Получим сущность отвечающую за ошибки
		var serverError = serverResponse.error;
		
		// Получим сущность отвечающую за статус выполнения запроса
		var serverStatus = serverResponse.status;
		
		// Если ответ от сервера разпознан и в нем нет ошибок
		if( serverResponse &&  ( !serverError || (serverError && !serverError.length)) )
		{			
			// Иначе обновим таблицу полученными данными 
			if( serverStatus ) 
			{
				// Если задан обработки ответа от сервера
				if( responseHandler ) responseHandler( serverResponse );			
			}
		}		
		// Если были ошибки
		else if( serverError )
		{
			// Если передан обработчик - вызовем его
			if( errorHandler ) errorHandler( serverError );
			// Иначе просто выведем ошибку в браузер
			else alert( serverError );
		}
	},
	
	/**
	 * Специальные обработчик для срабатываниня ассинхронного действия по нажатию на элемент
	 * 
	 * @param clickable 		Элемент на который необходимо нажать для активации действия
	 * @param responseHandler	Обработчик ассинхронного ответа от сервера
	 * @param errorHandler		Обработчик ошибки полученной от сервера
	 * @param beforeHandler		Предобработчик запроса, должен вернуть truе, что бы запрос выполнился
	 * @param baseURL			Начальный URL к которому добавляется значение галочки
	 */
	bindClick : function( clickable, responseHandler, errorHandler, beforeHandler, baseURL )
	{
		// Переберем все элементы в переданной коллекции
		clickable.each(function( obj )
		{			
			// Повесим обработчик на нажатие
			obj.click(function()
			{ 
				// Определим результат валидации
				var beforeResult = ( beforeHandler ) ? beforeHandler( obj ) : true;				
				
				// Получим ссылку на которую необходимо перейти
				var url = ( baseURL ) ? baseURL( obj ) : '';
					
				// Если предобработчик вернул положительный результат
				if( beforeResult ) SamsonPHP.action( url, responseHandler, errorHandler); 
				
			}, true);					
		});
		
		// Вернем указатель на самого себя для цепирования
		return this;
	},
	
	/**
	 * Специальные обработчик для срабатываниня ассинхронного действия по нажатию на ссылку
	 * 
	 * @param clickable 		Элемент на который необходимо нажать для активации действия
	 * @param responseHandler	Обработчик ассинхронного ответа от сервера
	 * @param errorHandler		Обработчик ошибки полученной от сервера
	 * @param beforeHandler		Предобработчик запроса, должен вернуть truе, что бы запрос выполнился
	 * @param baseURL			Начальный URL к которому добавляется значение галочки
	 */
	bindLink : function( link, responseHandler, errorHandler, beforeHandler, baseURL )
	{
		return SamsonPHP.bindClick( link, responseHandler, errorHandler, beforeHandler, function(obj)
		{
			return obj.a('href');
		});		
	},
	
	/**
	 * Специальные обработчик для срабатываниня ассинхронного действия по клацанию на галочку
	 * 
	 * @param clickable 		Элемент на который необходимо нажать для активации действия
	 * @param responseHandler	Обработчик ассинхронного ответа от сервера
	 * @param errorHandler		Обработчик ошибки полученной от сервера
	 * @param beforeHandler		Предобработчик запроса, должен вернуть truе, что бы запрос выполнился
	 * @param baseURL			Начальный URL к которому добавляется значение галочки
	 */
	bindCheckbox : function( checkbox, responseHandler, errorHandler, beforeHandler, baseURL )
	{
		return SamsonPHP.bindClick( checkbox, responseHandler, errorHandler, beforeHandler, function(obj)
		{
			return baseURL + obj.val();
		});
	},
};