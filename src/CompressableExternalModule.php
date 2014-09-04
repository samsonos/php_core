<?php
namespace samson\core;

/**
 * Модуль системы поддерживающий сжатие
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 * @version 0.1
 */
class CompressableExternalModule extends ExternalModule implements iModuleCompressable
{		
	/** @see \samson\core\iModuleCompressable::beforeCompress() */
	public function beforeCompress( & $obj = null, array & $code = null ){}
	
	/** @see \samson\core\iModuleCompressable::compress() */
	public function afterCompress( & $obj = null, array & $code = null ){}
}