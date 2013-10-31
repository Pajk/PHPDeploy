#!/bin/sh

if [ ! -f composer.phar ]
then
    echo "== Downloading Composer"
    curl -sS https://getcomposer.org/installer | php
    chmod a+x composer.phar
fi

echo "== Downloading 3rd party libraries"
php composer.phar install

if [ ! -f config/plugins.php ]
then
    echo "== Enabling builtin plugins"
    cp config/plugins.php.example config/plugins.php
fi

if [ ! -f config/config.php ]
then
    echo "== Creating example configuration"
    cp config/config.php.example config/config.php
fi
