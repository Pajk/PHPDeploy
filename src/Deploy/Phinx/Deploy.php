<?php

namespace Deploy\Phinx;

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\RollbackEvent;
use Deploy\Core\Utils;
use Deploy\DeployBase;
use Psr\Log\LoggerInterface;

class Deploy extends DeployBase
{
    private $binary_path;
    private $config_files;

    public function onDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Phinx::Deploy");

        $binary = $event->getTargetDir() . '/' . $this->binary_path;

        $this->utils->shouldExists(
            $binary,
            "Phinx binary not found in {$binary}"
        );

        foreach ($this->config_files as $config) {

            $config_path = $event->getTargetDir() . '/' . $config;
            $config_dirname = dirname($config_path);

            $this->utils->shouldExists(
                $config_path,
                "Phinx config {$config} not found"
            );

            $process = $this->utils->exec(
                array($binary, 'migrate', '-c', $config_path),
                "Unable to migrate database using {$config_path}",
                "Database migrated using {$config_path}"
            );

            $this->utils->createFile(
                $config_dirname . '/PHINX_MIGRATE',
                $process->getOutput(),
                'Unable to store phinx migrate output to PHINX_MIGRATE file'
            );

            $process = $this->utils->exec(
                array($binary, 'status', '-c', $config_path),
                "Unable to get database status using {$config_path}"
            );

            $status = $process->getOutput();
            $this->utils->createFile(
                $config_dirname . '/PHINX_STATUS',
                $status,
                'Unable to store phinx status output to PHINX_STATUS file'
            );

            $current_migration = $this->extractCurrentMigration($status);

            $this->utils->createFile(
                $config_dirname . '/PHINX_CURRENT',
                $current_migration,
                'Unable to store current phinx migration id to PHINX_CURRENT file',
                "Current migration is {$current_migration}"
            );
        }
    }

    public function onDeployEventUndo(DeployEvent $event)
    {
        $this->logger->debug("== Phinx::Deploy undo");

        $current_dir = $event->getCurrentDir();
        if (empty($current_dir)) {
            return;
        }

        $binary = $event->getTargetDir() . '/' . $this->binary_path;

        $this->utils->shouldExists(
            $binary,
            "Phinx binary not found in {$binary}"
        );

        foreach ($this->config_files as $config) {

            $current_config_path = $current_dir . '/' . $config;
            $current_config_dirname = dirname($current_config_path);

            $target_config_path = $event->getTargetDir() . '/' . $config;
            $target_config_dirname = dirname($target_config_path);

            if (!$this->utils->exists($current_config_dirname . '/PHINX_CURRENT')) {
                continue;
            }

            $target_migration = file_get_contents($current_config_dirname . '/PHINX_CURRENT');
            $target_migration = trim($target_migration);

            $this->logger->info("Phinx rollback to {$target_migration} using {$target_config_path}");

            $this->utils->shouldExists(
                $target_config_path,
                "Phinx config {$target_config_path} not found"
            );

            $process = $this->utils->exec(
                array($binary, 'rollback', '-c', $target_config_path, '-t', $target_migration),
                "Unable to migrate database using {$target_config_path}",
                "Database migrated using {$target_config_path}",
                $target_config_dirname
            );

            $this->utils->createFile(
                $target_config_dirname . '/PHINX_UNDO',
                $process->getOutput(),
                "Unable to save rollback output to PHINX_UNDO."
            );

            $this->utils->createFile(
                $target_config_dirname . '/PHINX_UNDO_TARGET',
                $target_migration,
                "Unable to save undo target release to PHINX_UNDO_TARGET."
            );

            $process = $this->utils->exec(
                array($binary, 'status', '-c', $target_config_path),
                "Unable to get database status using {$target_config_path}"
            );

            $this->utils->createFile(
                $target_config_dirname . '/PHINX_AFTER_UNDO_STATUS',
                $process->getOutput(),
                "Unable to save status after rollback to PHINX_AFTER_UNDO_STATUS."
            );
        }
    }

    public function onRollbackEvent(RollbackEvent $event)
    {
        $this->logger->debug("== Phinx::Rollback");

        $target_dir = $event->getTargetDir();

        if (empty($target_dir)) {
            return;
        }

        $binary = $target_dir . '/' . $this->binary_path;

        $this->utils->shouldExists(
            $binary,
            "Phinx binary not found in {$binary}"
        );

        foreach ($this->config_files as $config) {

            $current_config_path = $event->getCurrentDir() . '/' . $config;
            $current_config_dirname = dirname($current_config_path);

            $target_config_path = $target_dir . '/' . $config;
            $target_config_dirname = dirname($target_config_path);

            $target_migration = file_get_contents($target_config_dirname . '/PHINX_CURRENT');
            $target_migration = trim($target_migration);

            $this->logger->info("Phinx rollback to {$target_migration} using {$current_config_path}");

            $this->utils->shouldExists(
                $current_config_path,
                "Phinx config {$current_config_path} not found"
            );

            $process = $this->utils->exec(
                array($binary, 'rollback', '-c', $current_config_path, '-t', $target_migration),
                "Unable to migrate database using {$current_config_path}",
                "Database migrated using {$current_config_path}"
            );

            $this->utils->createFile(
                $current_config_dirname . '/PHINX_ROLLBACK',
                $process->getOutput(),
                "Unable to save rollback output to PHINX_ROLLBACK."
            );

            $this->utils->createFile(
                $current_config_dirname . '/PHINX_ROLLBACK_TARGET',
                $target_migration,
                "Unable to save rollback target release to PHINX_ROLLBACK_TARGET."
            );

            $process = $this->utils->exec(
                array($binary, 'status', '-c', $current_config_path),
                "Unable to get database status using {$current_config_path}"
            );

            $this->utils->createFile(
                $current_config_dirname . '/PHINX_AFTER_ROLLBACK_STATUS',
                $process->getOutput(),
                "Unable to save status after rollback to PHINX_AFTER_ROLLBACK_STATUS."
            );
        }
    }

    function __construct(
        $binary_path,
        $config_files
    ) {
        $this->binary_path     = $binary_path;
        $this->config_files    = $config_files;
    }

    private function extractCurrentMigration($status)
    {
        preg_match_all('/^     up  (\d+)  \w+/m', $status, $m);

        if (count($m) > 0 && count($m[1]) > 0) {
            $current = $m[1][count($m[1])-1];
        } else {
            $current = 0;
        }

        return $current;
    }
}