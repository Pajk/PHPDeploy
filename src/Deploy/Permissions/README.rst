*******************
 Permissions plugin
*******************

--------------
 Configuration
--------------

* rwx

------------
 Description
------------

First it sets permission to all files in deployed release to 755 and then it goes through all resources (files or folders) specified in ``rwx`` configuration and sets permissions to 0777.
