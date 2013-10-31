*******************
 Composer plugin
*******************

--------------
 Configuration
--------------

* binary_path ("composer.phar")
* options (["--verbose","--prefer-dist"])
* update_vendors (false)
* working_dirs (["."])
* timeout (1000)

------------
 Description
------------

This plugin downloads and executes Composer to install 3rd party libraries.

It runs ``composer install`` (or ``composer update`` depending on ``update_vendors`` settings) in all ``working_dirs`` directories. All working directory should contain ``composer.json`` file with a list of dependencies.
