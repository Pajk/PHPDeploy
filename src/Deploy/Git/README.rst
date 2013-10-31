*******************
 Git plugin
*******************

--------------
 Configuration
--------------

* *repository
* branch ("master")
* cached_copy_dir ("cached-copy")
* binary_path ("git")
* enable_submodules (false)

------------
 Description
------------

Takes care of cloning remote repository, updating it to newest version, copying the code to release directory. If ``enable_submodules`` is set to true it supports git submodules, otherwise they are not copied to target release directory. During deploy it creates a ``REVISION`` file with commit hash on which is the release based. It fills ``sourceDir`` in event instance so other plugins (such as shared files and folders plugin) can access source files directly as well.
