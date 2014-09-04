<?php
namespace samson\core;

/**
 * Локальный модуль системы поддерживающий сжатие
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 * @version 0.1
 */
class CompressableLocalModule extends LocalModule implements iModuleCompressable
{		
	/** @see \samson\core\iModuleCompressable::beforeCompress() */
	public function beforeCompress( & $obj = null, array & $code = null ){}
	
	/** @see \samson\core\iModuleCompressable::compress() */
	public function afterCompress( & $obj = null, array & $code = null ){}
}