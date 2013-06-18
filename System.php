<?php
namespace Samson\Core;

/**
 * Base class for converting Samson\Core to external compressable module of it self
 * @author Vitaly Iegorov
 */
class System extends CompressableModule
{		
	protected $id = 'core';
	protected $author = 'Vitaly Iegorov';
	protected $version = '1.1.1';
}