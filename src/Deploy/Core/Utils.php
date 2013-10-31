<?php

namespace Deploy\Core;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class Utils
{
    private $logger;

    private $fs;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->fs     = new Filesystem();
    }

    public function setFilesystem($filesystem)
    {
        $this->fs = $filesystem;
    }

    public function getTimestamp()
    {
        return date("YmdHis");
    }

    public function exec($command, $error, $success = null, $working_directory = '.', $timeout = 60)
    {
        if (is_array($command)) {
            $builder = new ProcessBuilder($command);
            $process = $builder->getProcess();
        } else {
            $process = new Process($command);
        }

        $this->logger->debug("Executing: " . $process->getCommandLine());

        $process->setTimeout($timeout);
        $process->setWorkingDirectory($working_directory);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->logger->alert($error);
            throw new \RuntimeException($process->getErrorOutput());
        } elseif ($success) {
            $this->logger->info($success);
        }

        return $process;
    }

    public function execPhp($script, $error, $success = null, $working_directory = '.', $timeout = 60)
    {
        $process = new PhpProcess($script);
        $this->logger->debug("Executing php script");

        $process->setTimeout($timeout);
        $process->setWorkingDirectory($working_directory);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->logger->alert($error);
            throw new \RuntimeException($process->getErrorOutput());
        } elseif ($success) {
            $this->logger->info($success);
        }

        return $process;
    }

    public function mkdir($dir, $error, $success = null, $exception_code = 0)
    {
        try {
            $this->fs->mkdir($dir);
        } catch (IOException $e) {
            throw new \RuntimeException($e->getMessage(), $exception_code);
        }

        if ($success) {
            $this->logger->info($success);
        }
    }

    public function createFile($path, $content, $error, $success = null, $mode = 0666)
    {
        try {
            $this->fs->dumpFile($path, $content, $mode);
        } catch (IOException $e) {
            $this->logger->alert($error);
            throw new \RuntimeException($e->getMessage());
        }

        if ($success) {
            $this->logger->info($success);
        }
    }

    public function createFolder($dir, $error, $success = null)
    {
        try {
            $this->fs->mkdir($dir);
        } catch (IOException $e) {
            $this->logger->alert($error);
            throw new \RuntimeException($e->getMessage());
        }

        if ($success) {
            $this->logger->info($success);
        }
    }

    public function symlink($origin, $target, $error, $success = null)
    {
        try {
            $this->fs->symlink($origin, $target);
        } catch (IOException $e) {
            $this->logger->alert($error);
            throw new \RuntimeException($e->getMessage());
        }

        if ($success) {
            $this->logger->info($success);
        }
    }

    public function remove($files, $error, $success = null)
    {
        try {
            $this->fs->remove($files);
        } catch (IOException $e) {
            $this->logger->alert($error);
            throw new \RuntimeException($e->getMessage());
        }

        if ($success) {
            $this->logger->info($success);
        }
    }

    public function copy($origin, $target, $error, $success = null)
    {
        try {
            $this->fs->copy($origin, $target);
        } catch (IOException $e) {
            $this->logger->alert($error);
            throw new \RuntimeException($e->getMessage());
        }

        if ($success) {
            $this->logger->info($success);
        }
    }

    public function shouldExists($resource, $error, $success = null, $exception_code = 0)
    {
        if ($this->fs->exists($resource)) {
            if ($success) {
                $this->logger->info($success);
            }
        } else {
            throw new \RuntimeException($error, $exception_code);
        }
    }

    public function chmod($resource, $chmod, $error, $success = null)
    {
        try {
            $this->fs->chmod($resource, $chmod, 0000, true);
        } catch (IOException $e) {
            $this->logger->alert($error);
            throw new \RuntimeException($e->getMessage());
        }

        if ($success) {
            $this->logger->info($success);
        }
    }

    public function exists($file)
    {
        return $this->fs->exists($file);
    }

    public function getFs()
    {
        return $this->fs;
    }
}
