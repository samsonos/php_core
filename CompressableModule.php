<?php
namespace Samson\Core;

/**
 * Модуль системы поддерживающий сжатие
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 * @version 0.1
 */
class CompressableModule extends ModuleConnector implements iModuleCompressable
{		
	/** @see \Samson\Core\iModuleCompressable::beforeCompress() */
	public function beforeCompress( & $obj = null, array & $code = null ){}
	
	/** @see \Samson\Core\iModuleCompressable::compress() */
	public function afterCompress( & $obj = null, array & $code = null ){}
}