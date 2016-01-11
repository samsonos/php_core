#SamsonPHP

[SamsonPHP](http://samsonphp.com) - Modular Event-based PHP framework

[![Latest Stable Version](https://poser.pugx.org/samsonos/php_core/v/stable.svg)](https://packagist.org/packages/samsonos/php_core) 
[![Build Status](https://travis-ci.org/samsonos/php_core.png)](https://travis-ci.org/samsonos/php_core)
[![Coverage Status](https://img.shields.io/coveralls/samsonos/php_core.svg)](https://coveralls.io/r/samsonos/php_core?branch=master)
[![Code Climate](https://codeclimate.com/github/samsonos/php_core/badges/gpa.svg)](https://codeclimate.com/github/samsonos/php_core) 
[![Total Downloads](https://poser.pugx.org/samsonos/php_core/downloads.svg)](https://packagist.org/packages/samsonos/php_core)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/samsonos/php_core/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/samsonos/php_core/?branch=master)
[![Stories in Ready](https://badge.waffle.io/samsonos/php_core.png?label=ready&title=Ready)](https://waffle.io/samsonos/php_core)

##Using SamsonPHP in your project
To use SamsonPHP framework in your project you must add its dependency in your ```composer.json```:
```json
    "require": {
        "samsonos/php_core": "1.*"
    }, 
```

After doing ```composer install``` or ```composer update``` all SamsonPHP classes and functions would be available.

Example usage:
```php
// Run framework
s()->start('main');
```

For further information read [Official SamsonPHP Wiki](https://github.com/samsonos/php_core/wiki)

Developed by [SamsonOS](http://samsonos.com/)

![PHPStorm](https://lh3.googleusercontent.com/-yVTWu-r8fZ4/AAAAAAAAAAI/AAAAAAAAAAA/7Sddz6VRuyg/photo.jpg)

Thanks to the best PHP IDE [PHPStorm](https://www.jetbrains.com/phpstorm/) that we are using for developing this project!
