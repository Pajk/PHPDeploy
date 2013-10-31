<?php

namespace Deploy\Core;

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\InitEvent;
use Deploy\Core\Event\RollbackEvent;
use Deploy\DeployBase;

class Deploy extends DeployBase
{
    private $history;
    private $logger_file;

    public function onPreInitEvent(InitEvent $event)
    {
        $this->utils->mkdir(
            $this->deploy_path,
            "Could not create target directory '{$this->deploy_path}'.",
            null,
            500
        );

        $this->removeOldLog();

        $this->logger->debug("== Core::PreInit");

        $this->logger->info("==== Init started");
    }

    public function onInitEvent(InitEvent $event)
    {
        $this->logger->debug("== Core::Init");

        foreach (array('releases', 'logs') as $dir) {
            $this->utils->mkdir(
                "{$this->deploy_path}/{$dir}",
                "Could not create {$dir} folder"
            );
        }
    }

    /**
     * Sets event data - timestamp, targetDir and currentDir
     *
     * @param DeployEvent $event
     */
    public function onPreDeployEvent(DeployEvent $event)
    {
        $this->utils->shouldExists(
            $this->deploy_path,
            "Deploy directory doesn't exist.",
            null,
            500
        );

        $this->removeOldLog();

        $this->logger->debug("== Core::PreDeploy");

        $this->logger->info("==== Deploy started");

        $timestamp = $this->utils->getTimestamp();

        $event->setTimestamp($timestamp);

        $target_dir = $this->getReleasePath($timestamp);

        $this->utils->mkdir(
            $target_dir,
            "Unable to create target release directory {$target_dir}",
            "Created target release directory {$target_dir}"
        );

        $event->setTargetDir($target_dir);

        $current = $this->getCurrentDeployedRelease();

        if ($current) {
            $event->setCurrentDir($this->getReleasePath($current));
        } else {
            $event->setCurrentDir(null);
        }
    }

    public function onPostDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Core::PostDeploy");

        $current_path = $this->deploy_path . '/current';

        $this->utils->remove(
            $current_path,
            "Unable to remove 'current' symlink."
        );

        $this->utils->symlink(
            $event->getTargetDir(),
            $current_path,
            "Unable to create 'current' symlink to current release."
        );

        $this->utils->copy(
            $this->deploy_path . '/' . $this->logger_file,
            $this->deploy_path . '/logs/' . $event->getTimestamp() . '.deploy.log',
            "Unable to copy log file to logs directory"
        );
    }

    /**
     * Gets a list of all deployed releases and reads current release version
     * Then selects a target release for rollback (always the one which was
     * deployed previously). Stores currentDir and targetDir to event instance.
     *
     * @param RollbackEvent $event
     * @throws \RuntimeException
     */
    public function onPreRollbackEvent(RollbackEvent $event)
    {
        $this->logger->debug("== Core::PreRollback");

        $this->utils->shouldExists(
            $this->deploy_path,
            "Deploy directory doesn't exist.",
            null,
            500
        );

        $this->removeOldLog();

        $this->logger->info("==== Rollback started");

        $list = $this->getAllDeployedReleases();

        $releases = implode(',', $list);
        $this->logger->info("All deployments: {$releases}");

        $current = $this->getCurrentDeployedRelease();

        if (!$current) {
            throw new \RuntimeException("No release is deployed at this time.");
        }

        $this->logger->info("Current release: {$current}.");

        $target = $this->findTargetRollbackRelease($current, $list);

        $this->logger->info("Rollback target is {$target}.");

        $event->setCurrentDir($this->getReleasePath($current));
        $event->setTargetDir($this->getReleasePath($target));
    }

    public function onPostRollbackEvent(RollbackEvent $event)
    {
        $this->logger->debug("== Core::PostRollback");

        $current_path = $this->deploy_path . '/current';

        $this->utils->remove(
            $current_path,
            "Unable to remove 'current' symlink.",
            "Current symlink removed"
        );

        $this->utils->symlink(
            $event->getTargetDir(),
            $current_path,
            "Unable to create 'current' symlink to current release.",
            "current -> {$event->getTargetDir()}."
        );

        $this->utils->copy(
            $this->deploy_path . '/' . $this->logger_file,
            $this->deploy_path . '/logs/' . $this->utils->getTimestamp() . '.rollback.log',
            "Unable to copy log file to logs directory"
        );
    }

    private function removeOldLog()
    {
        $this->utils->remove(
            $this->deploy_path . '/' . $this->logger_file,
            'Unable to remove old log'
        );
    }

    public function __construct(
        $history,
        $logger_file
    ) {
        $this->history         = $history;
        $this->logger_file     = $logger_file;
    }

    private function getCurrentDeployedRelease()
    {
        $process = $this->utils->exec(
            array('ls', '-l', $this->deploy_path),
            "Unable to get current deployed release"
        );

        $current = $process->getOutput();
        preg_match("/current(.*)/", $current, $matches);
        if (count($matches) < 2) {
            return null;
        }
        $current = $matches[1];
        $current = substr($current, (strrpos($current, '/'))+1);
        $current = trim($current);

        return $current;
    }

    private function getAllDeployedReleases()
    {
        $process = $this->utils->exec(
            array('ls', '-t', $this->getReleasePath()),
            'Unable to list all deployed releases'
        );

        $list = $process->getOutput();

        $list = preg_replace('/\s/', ',', $list);

        $list = explode(',', $list);
        $list = array_filter($list);

        return $list;
    }

    private function findTargetRollbackRelease($current, $list)
    {
        $position = array_search($current, $list)+1;

        $count = count($list);

        if (empty($count)) {
            throw new \RuntimeException("Unable to find rollback target.");
        }

        $this->logger->info("Current release is {$position} from {$count}.");

        $position = $position + 1;

        if ($position <= 0) {
            $position = 1;
        }

        if ($position >= $count) {
            $position = $count;
        }

        $target = current(array_slice($list, ($position - 1), 1));

        if (empty($target)) {
            throw new \RuntimeException("Unable to find rollback target.");
        }

        return $target;
    }

    private function getReleasePath($release = '')
    {
        return $this->deploy_path . '/releases/' . $release;
    }
}
