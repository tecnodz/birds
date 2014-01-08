<?php

xdebug_start_trace('log/trace');
require_once "bird.php";
bird::$debugEnv = true;
bird::env('dev');
$app = bird::app();
$app->fly();
unset($app);
xdebug_stop_trace();
bird::log(bird::status());
