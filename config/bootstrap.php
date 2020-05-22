<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManager;
use ErrorReporter\ErrorReporter;
use ErrorReporter\Middleware\ErrorReporterMiddleware;

ErrorReporter::instance()->setConfig(Configure::consume('ErrorReporter') ?: []);

EventManager::instance()->on('Server.buildMiddleware', function ($event, $queue) {
    $middleware = new ErrorReporterMiddleware();
    $queue->insertAfter(ErrorHandlerMiddleware::class, $middleware);
});
