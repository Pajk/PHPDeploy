*******************
 Core plugin
*******************

--------------
 Configuration
--------------

* *project_name
* *deploy_path
* history (5)
* logger_file ("deploy.log")
* logger_level (Logger::DEBUG)
* logger_echo_level (Logger::INFO)
* logger_name ("deploy")

------------
 Description
------------

Core plugin's responsibility is to create directory structure, backup logs, take care of ``current`` symlink and fill basic info to event instance.

During deploy it stores ``currentDir``, ``targetDir`` and ``timestamp`` to deploy event instance.

* Current directory is a directory where currently deployed release is stored.
* Target directory is a directory where new release should be store during running deploy.
* Timestamp is a name of release currently being deployed.

During rollback it stores ``currentDir`` and ``targetDir`` to rollback event instance.

* Current directory is a directory where currently deployed release is stored.
* Target directory is a directory where previously deployed release is stored.
