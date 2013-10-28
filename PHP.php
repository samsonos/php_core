<?php
namespace samson\core;

/**
 * Base class for PHP interpretator as module
 * @version 0.0.1
 * @author Vitaly Iegorov
 */
class PHP extends CompressableExternalModule
{
	protected $id = 'php';
	
	/** Get real current PHP version */
	protected $version = PHP_VERSION;	
} 