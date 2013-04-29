<?php
require_once dirname(dirname(__FILE__)).'/apps/lib/vendor/Birds/bird.php';
Birds\bird::app('birds', 'dev')->fly();
Birds\bird::log('-- '.substr(microtime(true) - BIRD_TIME,0,9).'s Mem: '.Birds\bird::bytes(memory_get_usage(),3).' / '.Birds\bird::bytes(memory_get_peak_usage(),3)." ".Birds\bird::scriptName());
