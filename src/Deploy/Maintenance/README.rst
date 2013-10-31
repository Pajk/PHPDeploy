*******************
 Maintenance plugin
*******************

--------------
 Configuration
--------------

* *template_file
* *target_file

------------
 Description
------------

This plugin copies ``template_file`` to ``target_file`` before deploy starts and removes ``target_file`` after deploy is finished or when it fails. It does that in current deployed release, not the one which is currently being deployed. The purpose is to show a maintenance page while deploy is running.

In order to make it work, you have to set your server configuration to serve ``target_file`` when it's present. If you are using Apache, you can configure it through ``.htaccess`` file:

.. code::

    # == Enable maintenance mode
    # To disable maintenance mode on specific IP uncomment following line
    # RewriteCond %{REMOTE_ADDR} !^000\.000\.000\.000
    RewriteCond %{DOCUMENT_ROOT}/maintenance.html -f
    RewriteCond %{REQUEST_URI} !/maintenance.html$ [NC]
    RewriteCond %{REQUEST_URI} !\.(jpe?g?|png|gif) [NC]
    RewriteRule .* /maintenance.html [L]
