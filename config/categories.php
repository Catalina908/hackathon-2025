<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ExpenseController;
use Slim\App;
use Slim\Psr7\Response;
use Slim\Routing\RouteCollectorProxy;

return [
    'groceries' => 300.00,
    'utilities' => 200.00,
    'transport' => 150.00,
    'entertainment' => 100.00,
    'health' => 120.00,
];
