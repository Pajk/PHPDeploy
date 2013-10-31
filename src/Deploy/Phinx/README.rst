*******************
 Phinx plugin
*******************

--------------
 Configuration
--------------

* binary_path ("bin/phinx")
* config_files (["phinx.yml"])

------------
 Description
------------

Enables support for `Phinx <https://github.com/robmorgan/phinx>`_ database migrations.

During deploy it runs ``phinx migrate`` for all ``config_files`` and stores output to ``PHINX_MIGRATE`` file and ``phinx status`` to ``PHINX_STATUS`` file. It extracts last migration id and stores it to ``PHINX_CURRENT`` file. When the deploy fails the database is rolled back to it's previous state.

During rollback the target migration is read from ``PHINX_CURRENT`` file in previous release and ``phinx rollback`` is executed with this target migration.
