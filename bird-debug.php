<?php

require_once "bird.php";
bird::$debugEnv = true;
xdebug_start_trace('log/trace');
$app = bird::app();
$app->fly();
unset($app);
xdebug_stop_trace();
bird::log(bird::status());
