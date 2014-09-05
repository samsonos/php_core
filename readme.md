#SamsonPHP

[SamsonPHP](http://samsonphp.com) - Module, Event based PHP framework

##Using SamsonPHP in your project
To use SamsonPHP framework in your project you must add its dependency in your ```composer.json```:
```
    "minimum-stability":"dev",
    "require": {
        "samsonos/php_core": "*"
    },
```

> We should use ```"minimum-stability":"dev"``` composer directive as we still cannot get
> final release version of core module and other commonly used modules.

##Loading modules into SamsonPHP framework
All modules are loaded via composer.json file section ```require:...``` and follows all PSR-0 rules.

Developed by [SamsonOS](http://samsonos.com/)