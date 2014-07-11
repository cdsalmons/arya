<?php

spl_autoload_register(function($class) {
    if (strpos($class, 'Arya\\') === 0) {
        $name = substr($class, strlen('Arya'));
        require __DIR__ . '/../lib' . strtr($name, '\\', DIRECTORY_SEPARATOR) . '.php';
    }
});

require __DIR__ . '/../vendor/Auryn/src/bootstrap.php';
require __DIR__ . '/../vendor/FastRoute/src/bootstrap.php';
