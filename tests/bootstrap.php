<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    $dotenv = new Dotenv();
    if (is_file(dirname(__DIR__).'/.env')) {
        $dotenv->bootEnv(dirname(__DIR__).'/.env');
    } else {
        $dotenv->load(dirname(__DIR__).'/.env.test');
    }
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
