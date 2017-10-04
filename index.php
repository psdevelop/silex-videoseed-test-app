<?php

require_once __DIR__.'/vendor/autoload.php';
$index = require __DIR__.'/controllers/IndexController.php';

$app = new Silex\Application();
$app['debug'] = true;

//Регистрируем провайдер-класс Doctrine DBAL
//для работы контроллера с данными БД SQLite
$app->register( new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/app.db'
    ),
));

//Регистрируем провайдер-класс шаблонизатора Twig
//для использования его в контроллерах при генерации страниц
$app->register( new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views'
));

//Монтируем единственный контроллер
$app->mount( '/', new Controllers\IndexController() );

//Запускаем приложение
$app->run();
