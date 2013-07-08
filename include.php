<?php
// Базовые интерфейсы SamsonPHP
require( 'Interfaces/iModule.php' );
require( 'Interfaces/iModuleConnector.php' );
require( 'Interfaces/iModuleViewable.php' );
require( 'Interfaces/iModuleCompressable.php' );
require( 'Interfaces/iExternalModule.php');
require( 'Interfaces/iCore.php' );
require( 'Interfaces/iURL.php' );
require( 'Interfaces/iHandlerE404.php' );

// Базовые модули SamsonPHP
require( 'Error.php' );
require( 'Utils.php' );
require( 'Configuration.php' );
require( 'i18n.php' );
require( 'URL.php' );
require( 'Module.php' );
require( 'LocalModule.php');
require( 'ExternalModule.php');
require( 'CompressableLocalModule.php');
require( 'CompressableExternalModule.php');
require( 'ModuleConnector.php' );
require( 'CompressableModule.php' );
require( 'SingletonModule.php' );
require( 'SamsonLocale.php' );
require( 'Core.php' );
require( 'Forms.php' );
require( 'AJAX.php' );
require( 'File.php' );
require( 'deprecated.php' );