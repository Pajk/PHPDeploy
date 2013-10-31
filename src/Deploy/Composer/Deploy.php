<?php

namespace Deploy\Composer;

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\InitEvent;
use Deploy\Core\Event\RollbackEvent;
use Deploy\Core\Utils;
use Deploy\DeployBase;
use Psr\Log\LoggerInterface;

class Deploy extends DeployBase
{
    private $binary_path;
    private $working_dirs;
    private $options;
    private $update_vendors;
    private $timeout;

    /**
     * Checks if composer is downloaded
     * if it's not, it downloads it
     *
     * @param InitEvent $event
     */
    public function onInitEvent(InitEvent $event)
    {
        $this->logger->debug("== Composer::Init");

        if (!$this->utils->exists($this->binary_path)) {
            $composer = file_get_contents('http://getcomposer.org/composer.phar');
            $this->utils->createFile(
                $this->binary_path,
                $composer,
                "Unable to download Composer to {$this->binary_path}",
                "Composer downloaded to {$this->binary_path}"
            );
        } else {
            $this->utils->exec(
                array('php', $this->binary_path, 'self-update'),
                "Unable to update composer",
                "Composer updated"
            );
        }
    }

    public function onPreDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Composer::PreDeploy");

        $this->utils->shouldExists(
            $this->binary_path,
            "Composer binary not found in {$this->binary_path}"
        );

        $source_dir = $event->getSourceDir() . '/';
        foreach ($this->working_dirs as $dir) {
            $this->utils->shouldExists(
                $source_dir . $dir,
                "Composer working dir {$source_dir}{$dir} not found"
            );
        }
    }

    public function onDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Composer::Deploy");

        $target_dir = $event->getTargetDir();

        $this->syncVendors($target_dir);
    }

    public function onDeployEventUndo(DeployEvent $event)
    {
        $this->logger->debug("== Composer::Deploy undo");

        $current_dir = $event->getCurrentDir();

        if (empty($current_dir)) {
            return;
        }

        $this->syncVendors($current_dir);
    }

    public function onRollbackEvent(RollbackEvent $event)
    {
        $this->logger->debug("== Composer::Rollback");

        $target_dir = $event->getTargetDir();

        $this->syncVendors($target_dir);
    }

    protected function syncVendors($target_dir)
    {
        foreach ($this->working_dirs as $dir) {
            $composer_dir = $target_dir . '/' . $dir;

            $this->utils->shouldExists(
                $composer_dir,
                "Composer working dir {$target_dir}{$dir} not found"
            );

            if ($this->update_vendors) {
                $this->utils->exec(
                    array_merge(array('php', $this->binary_path, 'update', '-d', $composer_dir), $this->options),
                    "Unable to update vendors in {$composer_dir}",
                    "Updated vendors in {$composer_dir}",
                    '.',
                    $this->timeout
                );
            } else {
                $this->utils->exec(
                    array_merge(array('php', $this->binary_path, 'install', '-d', $composer_dir), $this->options),
                    "Unable to install vendors in {$composer_dir}",
                    "Installed vendors in {$composer_dir}",
                    '.',
                    $this->timeout
                );
            }
        }
    }

    function __construct(
        $binary_path,
        $working_dirs,
        $options,
        $update_vendors,
        $timeout
    ) {
        $this->binary_path     = $binary_path;
        $this->working_dirs    = $working_dirs;
        $this->options         = $options;
        $this->update_vendors  = $update_vendors;
        $this->timeout         = $timeout;
    }
}