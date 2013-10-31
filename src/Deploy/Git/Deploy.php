<?php

namespace Deploy\Git;

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\InitEvent;
use Deploy\DeployBase;

class Deploy extends DeployBase
{
    private $repository;
    private $branch;
    private $cached_copy_dir;
    private $binary_path;
    private $enable_submodules;

    /**
     * Checks if:
     * - git is installed
     * - repo exists
     *
     * @param \Deploy\Core\Event\InitEvent $event
     */
    public function onPreInitEvent(InitEvent $event)
    {
        $this->logger->debug("== Git::PreInit");

        $this->execGit(
            array('--version'),
            "Git not present!"
        );

        $this->execGit(
            array('ls-remote', $this->repository, 'HEAD'),
            "Repository {$this->repository} does not exists!",
            "Repository {$this->repository} exists."
        );

        $event->setSourceDir("{$this->deploy_path}/{$this->cached_copy_dir}");
    }

    public function onInitEvent(InitEvent $event)
    {
        $this->logger->debug("== Git::Init");

        $repo_dir = $event->getSourceDir();

        if (!$this->utils->exists($repo_dir)) {
            $this->execGit(
                array("clone", "--recursive", $this->repository, $repo_dir),
                "Could not clone repository {$this->repository}",
                "Repository {$this->repository} cloned to {$repo_dir}"
            );
        }

        $this->checkoutBranch($this->branch, $repo_dir);
    }

    public function onPreDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Git::PreDeploy");

        $source_dir = "{$this->deploy_path}/{$this->cached_copy_dir}";
        $event->setSourceDir($source_dir);

        $this->execGit(
            array('--version'),
            "Git not present!"
        );

        if (!$this->utils->exists($source_dir)) {
            $msg = "Source directory {$source_dir} doesn't exist, try to run init.";
            $this->logger->alert($msg);
            throw new \RuntimeException($msg);
        };

        $this->execGit(
            array('status'),
            "Local repository not present!",
            null,
            $source_dir
        );
    }

    public function onDeployEvent(DeployEvent $event)
    {
        $this->logger->debug("== Git::Deploy");

        $source_dir = $event->getSourceDir();
        $target_dir = $event->getTargetDir() . '/';

        $revision = $this->checkoutBranch($this->branch, $source_dir);

        if ($this->enable_submodules) {
            $this->utils->exec(
                "cp -R {$source_dir}/* .;
              for i in `find . -name .git` ; do
                rm -rf \$i;
              done;",
                "Could not copy code from git repository to '{$target_dir}'.",
                "Current release copied to {$target_dir}",
                $target_dir
            );
        } else {
            $this->execGit(
                array("checkout-index", "-a", "-f", "--prefix={$target_dir}"),
                "Could not copy code from git repository to '{$target_dir}'.",
                "Current release copied to {$target_dir}",
                $source_dir
            );
        }

        $this->utils->createFile(
            $target_dir . 'REVISION',
            $revision,
            "Unable to create REVISION file"
        );

    }

    protected function execGit($command, $error, $success = null, $working_dir = ".", $timeout = 500)
    {
        $cmd = array_merge(array($this->binary_path), $command);

        return $this->utils->exec($cmd, $error, $success, $working_dir, $timeout);
    }

    protected function checkoutBranch($branch, $source_dir)
    {
        $this->execGit(
            array("fetch", "origin"),
            "Unable to fetch git repository",
            "Fetched latest code version",
            $source_dir
        );

        $process = $this->execGit(
            array("rev-parse", "origin/{$branch}"),
            "Unable to extract current revision",
            null,
            $source_dir
        );
        $revision = trim($process->getOutput());

        $this->execGit(
            array("checkout", "-f", $revision),
            "Unable to checkout branch '{$branch}'",
            "Using branch '{$branch}' revision '{$revision}'",
            $source_dir
        );

        if ($this->enable_submodules) {
            $this->execGit(
                array("submodule", "update", "--init"),
                "Unable to update submodules",
                "Git submodules updated",
                $source_dir
            );
        }

        return $revision;
    }

    function __construct(
        $repository,
        $binary_path,
        $branch,
        $cached_copy_dir,
        $enable_submodules
    ) {
        $this->binary_path     = $binary_path;
        $this->branch          = $branch;
        $this->cached_copy_dir = $cached_copy_dir;
        $this->repository      = $repository;
        $this->enable_submodules = $enable_submodules;
    }

    /**
     * @param mixed $binary_path
     */
    public function setBinaryPath($binary_path)
    {
        $this->binary_path = $binary_path;
    }

    /**
     * @return mixed
     */
    public function getBinaryPath()
    {
        return $this->binary_path;
    }

    /**
     * @param mixed $branch
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;
    }

    /**
     * @return mixed
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @param mixed $cached_copy_dir
     */
    public function setCachedCopyDir($cached_copy_dir)
    {
        $this->cached_copy_dir = $cached_copy_dir;
    }

    /**
     * @return mixed
     */
    public function getCachedCopyDir()
    {
        return $this->cached_copy_dir;
    }

    /**
     * @param mixed $enable_submodules
     */
    public function setEnableSubmodules($enable_submodules)
    {
        $this->enable_submodules = $enable_submodules;
    }

    /**
     * @return mixed
     */
    public function getEnableSubmodules()
    {
        return $this->enable_submodules;
    }

    /**
     * @param mixed $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return mixed
     */
    public function getRepository()
    {
        return $this->repository;
    }
}