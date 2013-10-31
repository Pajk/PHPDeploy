<?php

namespace Deploy\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class InitEvent extends Event
{
    protected $source_dir;

    public function setSourceDir($source_dir)
    {
        $this->source_dir = $source_dir;
    }

    public function getSourceDir()
    {
        return $this->source_dir;
    }

}
