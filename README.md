birds
=====

Birds is a new CMS/framework concept for web and mobile applications. It aims to be simple, fast and reliable, able to handle extremely complex datasets with ease to build a website or webapp — all this under 4MB of memory per proccess and 0.3s of duration.

## Standalone instance ##

Basic installation:
    
    user@localhost:~$ php Birds/bird.php install \
    	--apps-dir=Birds --site=bird \
    	--domain=localhost --domain=localhost.localdomain

To run in a local development server, just start PHP Web Server pointing to birds.php:

    user@localhost:~/Birds$ php -S 0.0.0.0:8080 bird.php

Or optionally run `Birds/bws.sh` which will trigger a debugging and profiled webserver.