<?php

if (extension_loaded('phar') && \Phar::running()) {
    require_once 'phar://' . \Phar::running() . '/vendor/autoload.php';
    $basePath = 'phar://' . \Phar::running();
} else {
    require_once __DIR__ . '/../vendor/autoload.php';
    $basePath = __DIR__ . '/..';
}

- require_once __DIR__ . '/../vendor/autoload.php'; 