***********
DEPLOY FLOW
***********

===========
 PRE_DEPLOY
===========

-----
 Core
-----

* removes old log file
* logs that deploy started
* generates target directory name, stores it in ``DeployEvent`` instance and creates this directory
* directory name is a timestamp in YmdHis format, this is also stored in event instance as ``timestamp``

----
 Git
----

* stores source dir (local repository path) to event instance
* checks that git ``binary_path`` is correct
* checks that local repository is in place

-------
 Shared
-------

* nothing

---------
 Composer
---------

* checks that ``binary_path`` is correct
* checks that all ``working_dirs`` from config exist

------
 Phinx
------

* nothing

------------
 Permissions
------------

* nothing

=======
 DEPLOY
=======

-----
 Core
-----

* nothing

----
 Git
----

* checks out ``branch`` specified in config
* exports code from repository to target directory
* creates a ``REVISION`` file with current commit hash

-------
 Shared
-------

* links all ``shared_folders`` and ``shared_files``

---------
 Composer
---------

* run Composer in all ``working_dirs`` specified in config
* if ``update_vendors`` is set to true it runs ``composer update`` otherwise just ``composer install``

------
 Phinx
------

* checks that ``binary_path`` is correct
* for each phinx config from ``config_files`` it does following
* runs phinx migrate and stores output to ``PHINX_MIGRATE`` file
* runs phinx status and stores output to ``PHINX_STATUS`` file
* extract current migration id from status output and stores it to ``PHINX_CURRENT`` file

------------
 Permissions
------------

* for all resources specified in ``rwx`` config it sets 0777 permissions with chmod

============
 POST_DEPLOY
============

----
 Git
----

* nothing

-------
 Shared
-------

* nothing

---------
 Composer
---------

* nothing

------
 Phinx
------

* nothing

------------
 Permissions
------------

* nothing

-----
 Core
-----

* removes ``current`` symlink
* link ``current`` to target directory which is set in DeployEvent instance
* copies log to ``logs/timestamp.deploy.log``
