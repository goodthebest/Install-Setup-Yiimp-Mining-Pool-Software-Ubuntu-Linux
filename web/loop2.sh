#!/bin/bash

PHP_CLI='php -d max_execution_time=120'

date

cd /var/web
while true; do
        ${PHP_CLI} runconsole.php cronjob/runLoop2
        sleep 60
done
exec bash

