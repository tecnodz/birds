#!/bin/sh
php -S 0.0.0.0:8080 -dzend.enable_gc=0 bird-debug.php 2>&1 1>> log/bird.log &