#SamsonPHP [![Latest Stable Version](https://poser.pugx.org/samsonos/php_core/v/stable.svg)](https://packagist.org/packages/samsonos/php_core) [![Build Status](https://travis-ci.org/samsonos/php_core.png)](https://travis-ci.org/samsonos/php_core) [![Coverage Status](https://coveralls.io/repos/samsonos/php_core/badge.png)](https://coveralls.io/r/samsonos/php_core) [![Code Climate](https://codeclimate.com/github/samsonos/php_core/badges/gpa.svg)](https://codeclimate.com/github/samsonos/php_core) [![Total Downloads](https://poser.pugx.org/samsonos/php_core/downloads.svg)](https://packagist.org/packages/samsonos/php_core)

[SamsonPHP](http://samsonphp.com) - Modular Event-based PHP framework

##Using SamsonPHP in your project
To use SamsonPHP framework in your project you must add its dependency in your ```composer.json```:
```
    "minimum-stability":"dev",
    "require": {
        "samsonos/php_core": "*"
    },
```
After doing ```composer install``` or ```composer update``` composer autoloader must be included
into your init script(by default ```index.php```): ```require [PATH_TO_VENDOR_DIR]/autoload.php```.
Following this line, all SamsonPHP classes and functions would be available.

> We should use ```"minimum-stability":"dev"``` composer directive as we still cannot get
> final release version of core module and other commonly used modules, but we promise to
> do it near future

For further information read [Official SamsonPHP Wiki](https://github.com/samsonos/php_core/wiki)

Developed by [SamsonOS](http://samsonos.com/)
