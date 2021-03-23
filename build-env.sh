#!/bin/sh
docker-compose build
docker-compose up -d
docker-compose exec php bash -c "cd /home/wwwroot/sf && composer install"
docker-compose exec php bash