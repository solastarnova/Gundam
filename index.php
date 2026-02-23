<?php

require_once __DIR__ . '/bootstrap.php';

$router = require __DIR__ . '/routes/web.php';
$router->dispatch();
