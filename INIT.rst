**********
 INIT FLOW
**********

=========
 PRE_INIT
=========

-----
 Core
-----

* creates ``deploy_path`` directory
* removes old log file
* logs that init started

----
 Git
----

* checks that git ``binary_path`` is correct
* checks that remote repository exists and authentication is set up correctly
* store source directory path to ``InitEvent`` instance

-------
 Shared
-------

* creates ``shared`` directory in ``deploy_path``

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

=====
 INIT
=====

-----
 Core
-----

* creates directories ``releases`` and ``logs``

----
 Git
----

* clone remote repository to source directory if local copy of repository doesn't exist
* checks out defined ``branch``

-------
 Shared
-------

* creates ``shared_folders`` defined in config
* creates ``shared_files`` defined in config
* if there is a template file (with extension which is present in ``template_extensions``) the file is created from this template file, blank file is created otherwise

---------
 Composer
---------

* downloads Composer to ``binary_path`` specified in config
* if the Composer is already downloaded it is updated to the last version

------
 Phinx
------

* nothing

------------
 Permissions
------------

* nothing

==========
 POST_INIT
==========

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

* nothing

------------
 Permissions
------------

* nothing
