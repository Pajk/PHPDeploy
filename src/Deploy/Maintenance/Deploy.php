<?php

namespace Deploy\Maintenance;

use Deploy\Core\Event\DeployEvent;
use Deploy\DeployBase;

class Deploy extends DeployBase
{
    private $template_file;
    private $target_file;

    public function __construct($template_file, $target_file)
    {
        $this->template_file = $template_file;
        $this->target_file = $target_file;
    }

    public function onPreDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Maintenance::PreDeploy");

        $current_dir = $event->getCurrentDir();
        if (empty($current_dir)) {
            return;
        }

        $this->utils->shouldExists(
            $current_dir. '/' . $this->template_file,
            "Template file doesn't exist."
        );

        $this->utils->copy(
            $current_dir . '/' . $this->template_file,
            $current_dir . '/' . $this->target_file,
            "Unable to copy {$this->template_file} to {$this->target_file}."
        );
    }

    public function onPreDeployEventUndo(DeployEvent $event)
    {
        $this->logger->debug("== Maintenance::PreDeploy undo");

        $current_dir = $event->getCurrentDir();
        if (empty($current_dir)) {
            return;
        }

        $this->utils->remove(
            $current_dir . '/' . $this->target_file,
            "Unable to remove {$this->target_file} file."
        );
    }

    public function onPostDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Maintenance::PostDeploy");

        $current_dir = $event->getCurrentDir();
        if (empty($current_dir)) {
            return;
        }

        $this->utils->remove(
            $current_dir . '/' . $this->target_file,
            "Unable to remove {$this->target_file} file."
        );
    }
}