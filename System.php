<?php
namespace samson\core;

/**
 * Base class for converting samson\core to external compressable module of it self
 * @author Vitaly Iegorov
 */
class System extends CompressableExternalModule
{		
	protected $id = 'core';
	protected $author = 'Vitaly Iegorov';
	protected $version = '1.1.1';
	protected $requirements = array();
}