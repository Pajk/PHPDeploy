<?php

namespace Deploy;

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\InitEvent;
use Deploy\Core\Event\RollbackEvent;
use Deploy\Core\Utils;
use Psr\Log\LoggerInterface;

abstract class DeployBase
{
    /** @var  LoggerInterface */
    protected $logger;

    /** @var  Utils */
    protected $utils;

    /** @var  string absolute path */
    protected $deploy_path;

    /**
     * @param mixed $deploy_path
     */
    public function setDeployPath($deploy_path)
    {
        $this->deploy_path = $deploy_path;
    }

    /**
     * @return mixed
     */
    public function getDeployPath()
    {
        return $this->deploy_path;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param \Deploy\Core\Utils $utils
     */
    public function setUtils($utils)
    {
        $this->utils = $utils;
    }

    /**
     * @return \Deploy\Core\Utils
     */
    public function getUtils()
    {
        return $this->utils;
    }

    public function onPreInitEvent(InitEvent $event) {}
    public function onPreInitEventUndo(InitEvent $event) {}
    public function onInitEvent(InitEvent $event) {}
    public function onInitEventUndo(InitEvent $event) {}
    public function onPostInitEvent(InitEvent $event) {}
    public function onPostInitEventUndo(InitEvent $event) {}

    public function onPreDeployEvent(DeployEvent $event) {}
    public function onPreDeployEventUndo(DeployEvent $event) {}
    public function onDeployEvent(DeployEvent $event) {}
    public function onDeployEventUndo(DeployEvent $event) {}
    public function onPostDeployEvent(DeployEvent $event) {}
    public function onPostDeployEventUndo(DeployEvent $event) {}

    public function onPreRollbackEvent(RollbackEvent $event) {}
    public function onPreRollbackEventUndo(RollbackEvent $event) {}
    public function onRollbackEvent(RollbackEvent $event) {}
    public function onRollbackEventUndo(RollbackEvent $event) {}
    public function onPostRollbackEvent(RollbackEvent $event) {}
    public function onPostRollbackEventUndo(RollbackEvent $event) {}
}