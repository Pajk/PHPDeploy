****************
 PHP Deploy Tool
****************

.. image:: https://travis-ci.org/Pajk/PHPDeploy.png?branch=master
    :target: https://travis-ci.org/Pajk/PHPDeploy

PHPDeploy is a command line application written completely in PHP language. Its purpose is to make deploy of a web application as easy and fast as possible. It's meant to be executed directly on the server and it uses local copy of your code repository. PHPDeploy is based on Symfony 2 components and it's very flexible and extensible. Most of the builtin features are separated into independent plugins and new plugins can be easily created.

**Table of Contents**

.. contents::
    :local:
    :depth: 1
    :backlinks: none

=====
Getting started
=====

PHPDeploy is dependent only on php, git and curl so using this tool is very convenient and there is probably no need to install anything you already don't have on your server.

SSH to your server, clone this repository, install 3rd party libraries with Composer, enable necessary plugins and fill config file.

.. code::

    $ git clone https://github.com/Pajk/PHPDeploy.git myappdeploy
    $ cd myappdeploy
    $ ./init.sh
    $ vim config/plugins.php
    $ vim config/config.php
    $ ./run deploy -vv

``plugins.php`` file contains a list of enabled plugins. Beware that it's an ordered list, plugins are registered in stated sequence and also defined event listeners are registered in this order.

Every plugin has it's own set of options you can use in your ``config.php``, you can find them all in this documentation or take a look directly to the code.

Now when you have all set up and configured, you have to initialize your deployment by running ``./run init``. Then you can deploy a version of your application by running ``./run deploy``. Symlink ``current`` then link to latest deployed release.

PHPDeploy contains only one runnable script which is called ``run``. With this script you can run init, deploy and rollback commands.

--------
run init
--------

Create a target deploy directory and initialize directory structure - folders ``releases`` and ``logs``. Also ``cached-copy`` and ``shared`` if you have enabled builtin plugins Git and Shared. It creates a local copy of your repository and also create all shared directories and files.

Please see `INIT.rst`_ for more details.

----------
run deploy
----------

Fetches last changes from remote repository and export code to new directory in ``releases`` folder. After all plugins are done (eg. composer installs 3rd party dependencies, shared files are symlinked, Phinx database migrations are migrated) the ``current`` symlink is created and it links to the deployed release. Every action is logged to a log file and after deploy is finished, this log is stored in ``logs`` directory.

Please see `DEPLOY.rst`_ for more details.

------------
run rollback
------------

Finds second last deployed release and returns to this release. When database migrations are enabled it also rollbacks your database to previous state (assuming that your migrations are reversible).

Please see `ROLLBACK.rst`_ for more details.

======================
 Built-in Plugins
======================

This is a list of all built-in plugins. Every plugin has a set of options which you can change in ``config/config.php`` file. Most of them have reasonable defaults but you can change them to satisfy your needs.

* `Core <https://github.com/Pajk/PHPDeploy/blob/master/src/Deploy/Core/README.rst>`_
* `Git <https://github.com/Pajk/PHPDeploy/blob/master/src/Deploy/Git/README.rst>`_
* `Shared <https://github.com/Pajk/PHPDeploy/blob/master/src/Deploy/Shared/README.rst>`_
* `Composer <https://github.com/Pajk/PHPDeploy/blob/master/src/Deploy/Composer/README.rst>`_
* `Phinx <https://github.com/Pajk/PHPDeploy/blob/master/src/Deploy/Phinx/README.rst>`_
* `Permissions <https://github.com/Pajk/PHPDeploy/blob/master/src/Deploy/Permissions/README.rst>`_
* `Maintenance <https://github.com/Pajk/PHPDeploy/blob/master/src/Deploy/Maintenance/README.rst>`_

======================
 How to create a new plugin
======================

Please see `PLUGIN_HOWTO.rst`_ for more details.

==========
 Resources
==========

Thanks to these great tools and applications. PHPDeploy wouldn't exist without them.

`Capistrano <https://github.com/capistrano/capistrano>`_, `Phing <http://www.phing.info/>`_, `Phingistrano <https://github.com/CodeMeme/Phingistrano>`_, `Git <http://git-scm.com/>`_, `Composer <http://getcomposer.org/>`_, `Symfony Components <http://symfony.com/doc/current/components/index.html>`_ (Config, Console, Dependency Injection, Event Dispatcher, Filesystem Process), `Monolog <https://github.com/Seldaek/monolog/>`_, `Phinx <https://github.com/robmorgan/phinx>`_

======
Author
======

`Pavel <https://github.com/Pajk>`_

=======
License
=======

PHPDeploy is released under the `MIT License <http://opensource.org/licenses/MIT>`_, please see `LICENSE`_.

.. _INIT.rst: https://github.com/Pajk/PHPDeploy/blob/master/INIT.rst
.. _DEPLOY.rst: https://github.com/Pajk/PHPDeploy/blob/master/DEPLOY.rst
.. _ROLLBACK.rst: https://github.com/Pajk/PHPDeploy/blob/master/ROLLBACK.rst
.. _LICENSE: https://github.com/Pajk/PHPDeploy/blob/master/LICENSE
.. _PLUGIN_HOWTO.rst: https://github.com/Pajk/PHPDeploy/blob/master/PLUGIN_HOWTO.rst
