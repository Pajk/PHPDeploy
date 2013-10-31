*******************
 Shared plugin
*******************

--------------
 Configuration
--------------

* files ([])
* folders ([])
* template_extensions (['example','dist','template','default'])

------------
 Description
------------

In every project there are some configuration files which should not be stored in git repository because they contains sensitive data such as database password. Also some directories could contain data which you don't want to be removed during deploy. These files and folders can be stored in ``shared`` directory on server.

Symbolic links to these shared files and directories are created during deploy in target release directory. All releases then share these files and you don't have to store them in repository or change after each deploy.

During ``init`` all specified ``files`` and ``folders`` are created. If a template file is found in repository (with one of ``template_extensions`` extension) it's content is used to create a new file in shared directory.
