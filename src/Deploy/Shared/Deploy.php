<?php

namespace Deploy\Shared;

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\InitEvent;
use Deploy\Core\Utils;
use Deploy\DeployBase;
use Psr\Log\LoggerInterface;

class Deploy extends DeployBase
{
    private $shared_files;
    private $shared_folders;
    private $template_extensions;

    public function onPreInitEvent(InitEvent $event)
    {
        $this->logger->debug("== Shared::PreInit");

        $this->utils->mkdir(
            "{$this->deploy_path}/shared",
            "Could not create shared folder"
        );
    }

    public function onInitEvent(InitEvent $event)
    {
        $this->logger->debug("== Shared::Init");

        $repo_dir = $event->getSourceDir() . '/';
        $shared_dir = $this->deploy_path . '/shared/';

        // create shared folders
        foreach ($this->shared_folders as $folder) {
            $this->utils->createFolder(
                $shared_dir . $folder,
                "Shared folder '{$folder}' created."
            );
        }

        // create shared files -> copy them from templates if available
        foreach ($this->shared_files as $file) {

            if ($this->utils->exists($shared_dir . $file)) {
                continue;
            }

            $template_found = false;
            foreach ($this->template_extensions as $extension) {
                if ($this->utils->exists($repo_dir . $file . '.' . $extension)) {

                    $this->utils->copy(
                        $repo_dir . $file . '.' . $extension,
                        $shared_dir . $file,
                        "Unable to copy template file {$file} to shared folder"
                    );

                    $template_found = true;
                    break;
                }
            }

            if (!$template_found) {
                $this->utils->createFile(
                    $shared_dir . $file,
                    "TODO: fill content",
                    "Unable to create shared file {$file}."
                );
            }

        }

    }

    public function onDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Shared::Deploy");

        $shared_dir = $this->deploy_path . '/shared/';
        $target_dir = $event->getTargetDir() . '/';

        foreach ($this->shared_folders as $dir) {
            if ($this->utils->exists($target_dir . $dir)) {
                $this->utils->remove(
                    $target_dir . $dir,
                    "Unable to remove dir {$dir} from current release."
                );
            }

            $this->utils->symlink(
                $shared_dir . $dir,
                $target_dir . $dir,
                "Unable to symlink shared folder {$dir}",
                "{$dir} -> shared/{$dir}"
            );
        }

        foreach ($this->shared_files as $file) {

            $dir = dirname($target_dir . $file);
            $this->utils->createFolder(
                $dir,
                'Unable to create shared file directory'
            );

            if ($this->utils->exists($target_dir . $file)) {
                $this->utils->remove(
                    $target_dir . $file,
                    "Unable to remove file {$file} from current release."
                );
            }

            $this->utils->symlink(
                $shared_dir . $file,
                $target_dir . $file,
                "Unable to symlink shared file {$file}",
                "{$file} -> shared/{$file}"
            );
        }
    }

    function __construct(
        $shared_files,
        $shared_folders,
        $template_extensions
    ) {
        $this->shared_files    = $shared_files;
        $this->shared_folders  = $shared_folders;
        $this->template_extensions = $template_extensions;
    }
}