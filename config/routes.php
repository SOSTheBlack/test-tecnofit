<?php

declare(strict_types=1);

use App\Controller\Account\Balance\WithdrawController;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::addRoute(['GET', 'HEAD'], '/health', 'App\Controller\IndexController@health');

Router::post('/account/{accountId}/balance/withdraw', WithdrawController::class);
