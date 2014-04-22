*******************
 Permissions plugin
*******************

--------------
 Configuration
--------------

* files -  [file_permission => [file1, file2, ...]]
* folders - [folder_permission => [folder1, folder2, ...]]

------------
 Description
------------

It goes through all files and folders specified in plugin's configuration and sets appropriate permissions.
Specified permission is passed directly to ``chmod`` command.
