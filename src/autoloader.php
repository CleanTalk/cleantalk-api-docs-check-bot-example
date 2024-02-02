<?php

spl_autoload_register(function ($class) {
    if ( substr($class, 0, 10) !== 'Cleantalk\\' ) {
        /* If the class does not lie under the "ReCaptcha" namespace,
        * then we can exit immediately.
        */
        return;
    }

    $class = str_replace('\\', '/', $class);
    $path = dirname(__FILE__) . '/' . $class . '.php';
    if ( is_readable($path) ) {
        require_once $path;
    }

});
