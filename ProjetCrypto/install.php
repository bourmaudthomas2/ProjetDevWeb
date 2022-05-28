<?php


exec("composer update");
exec("composer install");
exec("php bin/console doctrine:database:create --if-not-exists");
exec("php bin/console doctrine:schema:update --force");
