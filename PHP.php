<?php
namespace samson\core;

/**
 * Base class for PHP interpretator as module
 * @author Vitaly Iegorov
 */
class PHP extends CompressableModule
{
	protected $id = 'php';
	
	/** Get real current PHP version */
	protected $version = PHP_VERSION;	
}