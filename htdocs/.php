<?php
require_once dirname(dirname(__FILE__)).'/apps/lib/vendor/Birds/bird.php';
$app = Birds\bird::app('birds', 'dev');
$app->fly();
