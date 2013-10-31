<?php

namespace Deploy\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class RollbackEvent extends Event
{
    private $targetDir;
    private $currentDir;

    /**
     * @param mixed $currentDir
     */
    public function setCurrentDir($currentDir)
    {
        $this->currentDir = $currentDir;
    }

    /**
     * @return mixed
     */
    public function getCurrentDir()
    {
        return $this->currentDir;
    }

    /**
     * @param $targetDir
     */
    public function setTargetDir($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    /**
     * @return mixed
     */
    public function getTargetDir()
    {
        return $this->targetDir;
    }

}
