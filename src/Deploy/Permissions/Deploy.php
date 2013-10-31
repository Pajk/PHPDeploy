<?php

namespace Deploy\Permissions;

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Utils;
use Deploy\DeployBase;
use Psr\Log\LoggerInterface;

class Deploy extends DeployBase
{
    private $rwx;

    public function onDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Permissions::Deploy");

        $target_dir = $event->getTargetDir() . '/';

        $this->utils->exec(
            "chmod -R 755 .",
            "Unable to fix permissions in deployed release",
            null,
            $target_dir
        );

        foreach ($this->rwx as $resource) {

            $this->utils->shouldExists(
                $target_dir . $resource,
                "Resource {$resource} does not exist."
            );

            $this->utils->chmod(
                $target_dir . $resource,
                0777,
                "Unable to set rwx permissions to {$resource}.",
                "Chmod '{$resource}' to rwx."
            );
        }
    }

    function __construct(array $rwx) {
        $this->rwx = $rwx;
    }
}
