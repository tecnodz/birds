#!/bin/sh
php -S 0.0.0.0:8080 bird.php 2>&1 1>> log/bird.log &