<?php

namespace Deploy\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class DeployEvent extends Event
{
    protected $source_dir;
    protected $target_dir;
    protected $current_dir;
    protected $timestamp;

    public function setTargetDir($target_dir)
    {
        $this->target_dir = $target_dir;
    }
    public function getTargetDir()
    {
        return $this->target_dir;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setSourceDir($source_dir)
    {
        $this->source_dir = $source_dir;
    }

    public function getSourceDir()
    {
        return $this->source_dir;
    }

    public function setCurrentDir($current_dir)
    {
        $this->current_dir = $current_dir;
    }

    public function getCurrentDir()
    {
        return $this->current_dir;
    }

}
