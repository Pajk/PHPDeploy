<?php

namespace Deploy\Phinx;

include dirname(__FILE__) . '/../../bootstrap.php';

use Deploy\Core\Event\DeployEvent;
use Deploy\Core\Event\RollbackEvent;
use Deploy\Core\Utils;
use Monolog\Logger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\Filesystem\Filesystem;

class DeployTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  vfsStreamDirectory
     */
    private $root;

    /**
     * @var Deploy
     */
    private $phinxDeploy;

    /**
     * @var Utils
     */
    private $utils;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $deploy_path;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->deploy_path = 'deploy_dir';
        $this->root = vfsStream::setup($this->deploy_path, null, array(
            'releases' => array(
                'previous_release' => array(
                    'bin' => array(
                        'phinx' => "phinx binary"
                    ),
                    'phinx.yml' => "phinx config"
                ),
                'current_release' => array(
                    'bin' => array(
                        'phinx' => "phinx binary"
                    ),
                    'phinx.yml' => "phinx config"
                ),
                'new_release' => array(
                    'bin' => array(
                        'phinx' => "phinx binary"
                    ),
                    'phinx.yml' => "phinx config"
                )
            )
        ));

        $this->phinxDeploy = new Deploy('bin/phinx', array('phinx.yml'));
        $this->phinxDeploy->setDeployPath(vfsStream::url($this->deploy_path));

        $this->logger = $this->getMock('Monolog\Logger', array(), array("deploy"));
        $this->phinxDeploy->setLogger($this->logger);

        $this->utils = $this->getMock('Deploy\Core\Utils', array('exec'), array($this->logger));
        $this->phinxDeploy->setUtils($this->utils);

        $filesystem = new TestFileSystem();
        $this->utils->setFilesystem($filesystem);
    }

    private function getPath($resource)
    {
        return vfsStream::url($this->deploy_path . $resource);
    }

    public function testOnDeployEvent()
    {
        $migration_id = 123456;
        $phinx_status_output = "     up  {$migration_id}  test_migration";
        $phinx_migrate_output = "phinx migrate output";

        $process = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $process
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->onConsecutiveCalls($phinx_migrate_output, $phinx_status_output));

        // it needs to call only 'phinx migrate' and then 'phinx status'
        $this->utils
            ->expects($this->exactly(2))
            ->method('exec')
            ->will($this->returnValue($process));

        $event = new DeployEvent();
        $event->setTargetDir($this->getPath('/releases/new_release'));
        $event->setCurrentDir($this->getPath('/releases/current_release'));
        $event->setTimestamp('new_release');
        $event->setSourceDir($this->getPath('/source'));

        $this->phinxDeploy->onDeployEvent($event);

        // it should create PHINX_CURRENT, PHINX_MIGRATE and PHINX_STATUS
        $this->assertTrue($this->root->hasChild('releases/new_release/PHINX_CURRENT'));
        $content = file_get_contents($this->getPath('/releases/new_release/PHINX_CURRENT'));
        $this->assertEquals($migration_id, $content);

        $this->assertTrue($this->root->hasChild('releases/new_release/PHINX_MIGRATE'));
        $content = file_get_contents($this->getPath('/releases/new_release/PHINX_MIGRATE'));
        $this->assertEquals($phinx_migrate_output, $content);

        $this->assertTrue($this->root->hasChild('releases/new_release/PHINX_STATUS'));
        $content = file_get_contents($this->getPath('/releases/new_release/PHINX_STATUS'));
        $this->assertEquals($phinx_status_output, $content);
    }

    public function testOnDeployEventUndo()
    {
        $current_migration_id = 44444;
        $new_migration_id = 3333;

        $this->utils->getFs()->dumpFile(
            $this->getPath("/releases/current_release/PHINX_CURRENT"),
            $current_migration_id
        );

        $this->utils->getFs()->dumpFile(
            $this->getPath("/releases/new_release/PHINX_CURRENT"),
            $new_migration_id
        );

        $phinx_undo_output = "phinx undo output";
        $phinx_status_output = "     up  {$current_migration_id}  test_migration";

        $process = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $process
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->onConsecutiveCalls($phinx_undo_output, $phinx_status_output));

        // it needs to call only 'phinx rollback' and then 'phinx status'
        $this->utils
            ->expects($this->exactly(2))
            ->method('exec')
            ->will($this->returnValue($process));

        $event = new DeployEvent();
        $event->setTargetDir($this->getPath('/releases/new_release'));
        $event->setCurrentDir($this->getPath('/releases/current_release'));
        $event->setTimestamp('new_release');
        $event->setSourceDir($this->getPath('/source'));

        $this->phinxDeploy->onDeployEventUndo($event);

        // it shouldn't change versions stored in PHINX_CURRENT files
        $this->assertTrue($this->root->hasChild('releases/current_release/PHINX_CURRENT'));
        $content = file_get_contents($this->getPath('/releases/current_release/PHINX_CURRENT'));
        $this->assertEquals($current_migration_id, $content);

        $this->assertTrue($this->root->hasChild('releases/new_release/PHINX_CURRENT'));
        $content = file_get_contents($this->getPath('/releases/new_release/PHINX_CURRENT'));
        $this->assertEquals($new_migration_id, $content);

        // it should create PHINX_UNDO, PHINX_UNDO_TARGET and PHINX_AFTER_UNDO_STATUS
        $this->assertTrue($this->root->hasChild('releases/new_release/PHINX_UNDO'));
        $content = file_get_contents($this->getPath('/releases/new_release/PHINX_UNDO'));
        $this->assertEquals($phinx_undo_output, $content);

        $this->assertTrue($this->root->hasChild('releases/new_release/PHINX_UNDO_TARGET'));
        $content = file_get_contents($this->getPath('/releases/new_release/PHINX_UNDO_TARGET'));
        $this->assertEquals($current_migration_id, $content);

        $this->assertTrue($this->root->hasChild('releases/new_release/PHINX_AFTER_UNDO_STATUS'));
        $content = file_get_contents($this->getPath('/releases/new_release/PHINX_AFTER_UNDO_STATUS'));
        $this->assertEquals($phinx_status_output, $content);
    }

    public function testOnDeployEventUndoWithoutCurrentRelease()
    {
        $event = new DeployEvent();
        $event->setTargetDir($this->getPath('/releases/new_release'));
        $event->setTimestamp('new_release');
        $event->setSourceDir($this->getPath('/source'));

        try {
            $this->phinxDeploy->onDeployEventUndo($event);
        } catch (\Exception $e) {
            $this->fail('An exception has been raised. ' . $e->getMessage());
        }
    }

    public function testOnRollbackEvent()
    {
        $previous_migration_id = 12345;
        $current_migration_id = 99999;

        $this->utils->getFs()->dumpFile(
            $this->getPath("/releases/previous_release/PHINX_CURRENT"),
            $previous_migration_id
        );

        $this->utils->getFs()->dumpFile(
            $this->getPath("/releases/current_release/PHINX_CURRENT"),
            $current_migration_id
        );

        $phinx_rollback_output = "phinx rollback output";
        $phinx_status_output = "     up  {$previous_migration_id}  test_migration";

        $process = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $process
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->onConsecutiveCalls($phinx_rollback_output, $phinx_status_output));

        // it needs to call only 'phinx rollback' and then 'phinx status'
        $this->utils
            ->expects($this->exactly(2))
            ->method('exec')
            ->will($this->returnValue($process));

        $event = new RollbackEvent();
        $event->setCurrentDir($this->getPath('/releases/current_release'));
        $event->setTargetDir($this->getPath('/releases/previous_release'));

        $this->phinxDeploy->onRollbackEvent($event);

        $this->assertTrue($this->root->hasChild('releases/current_release/PHINX_CURRENT'));
        $content = file_get_contents($this->getPath('/releases/current_release/PHINX_CURRENT'));
        $this->assertEquals($current_migration_id, $content);

        $this->assertTrue($this->root->hasChild('releases/previous_release/PHINX_CURRENT'));
        $content = file_get_contents($this->getPath('/releases/previous_release/PHINX_CURRENT'));
        $this->assertEquals($previous_migration_id, $content);

        // it should create PHINX_ROLLBACK, PHINX_ROLLBACK_TARGET and PHINX_AFTER_ROLLBACK_STATUS
        $this->assertTrue($this->root->hasChild('releases/current_release/PHINX_ROLLBACK'));
        $content = file_get_contents($this->getPath('/releases/current_release/PHINX_ROLLBACK'));
        $this->assertEquals($phinx_rollback_output, $content);

        $this->assertTrue($this->root->hasChild('releases/current_release/PHINX_ROLLBACK_TARGET'));
        $content = file_get_contents($this->getPath('/releases/current_release/PHINX_ROLLBACK_TARGET'));
        $this->assertEquals($previous_migration_id, $content);

        $this->assertTrue($this->root->hasChild('releases/current_release/PHINX_AFTER_ROLLBACK_STATUS'));
        $content = file_get_contents($this->getPath('/releases/current_release/PHINX_AFTER_ROLLBACK_STATUS'));
        $this->assertEquals($phinx_status_output, $content);
    }

    public function testOnRollbackEventUndoWithoutCurrentRelease()
    {
        $event = new RollbackEvent();
        $event->setCurrentDir($this->getPath('/releases/current_release'));

        try {
            $this->phinxDeploy->onRollbackEvent($event);
        } catch (\Exception $e) {
            $this->fail('An exception has been raised. ' . $e->getMessage());
        }
    }
}

class TestFileSystem extends Filesystem
{
    public function dumpFile($filename, $content, $mode = 0666)
    {
        file_put_contents($filename, $content);
    }
}