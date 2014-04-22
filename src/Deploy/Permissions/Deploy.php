<?php

namespace Deploy\Permissions;

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Utils;
use Deploy\DeployBase;
use Psr\Log\LoggerInterface;

class Deploy extends DeployBase
{
    private $files;
    private $folders;

    public function onDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Permissions::Deploy");

        $target_dir = $event->getTargetDir() . '/';

        foreach ($this->folders as $permission => $folders) {
            $octal = decoct($permission);
            foreach ($folders as $resource) {

                if (!$this->utils->exists($target_dir . $resource)) {
                    $this->utils->mkdir(
                        $target_dir . $resource,
                        "Unable to create folder {$resource}"
                    );
                }

                $this->utils->chmod(
                    $target_dir . $resource,
                    $permission,
                    "Unable to set {$octal} permissions to {$resource}.",
                    "Chmod {$octal} '{$resource}'."
                );
            }
        }

        foreach ($this->files as $permission => $files) {
            $octal = decoct($permission);
            foreach ($files as $resource) {

                if (!$this->utils->exists($target_dir . $resource)) {
                    $this->utils->createFile(
                        $target_dir . $resource,
                        "New empty file created by PHPDeploy",
                        "Unable to create file {$resource}"
                    );
                }

                $this->utils->chmod(
                    $target_dir . $resource,
                    $permission,
                    "Unable to set {$octal} permissions to {$resource}.",
                    "Chmod {$octal} '{$resource}'."
                );
            }
        }
    }

    public function __construct(array $files, array $folders) {
        $this->files = $files;
        $this->folders = $folders;
    }
}
