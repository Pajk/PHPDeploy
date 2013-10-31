*************
ROLLBACK FLOW
*************

=============
 PRE_ROLLBACK
=============

-----
 Core
-----

* removes old log
* logs that rollback started
* gets list of all releases in ``releases`` folder
* get last deployed release timestamp
* chooses second last deployed release and store path to this release directory as target directory to RollbackEvent instance
* stores current deployed release directory path as current directory to event instance

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


=========
 ROLLBACK
=========

-----
 Core
-----

* nothing

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

* for each config from ``config_files``:
* reads target migration id from ``PHINX_CURRENT`` in target directory
* runs phinx rollback to target migration and stores output to ``PHINX_ROLLBACK`` file
* runs phinx status and store output to ``PHINX_AFTER_ROLLBACK_STATUS``

------------
 Permissions
------------

* nothing


=============
 POST_ROLLBACK
=============

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

* removes ``current`` symlink and creates a new one linked to selected target release
* copies log to ``logs/timestamp.rollback.log``
