<?php

namespace Pitpit\Component\MongoFilesystem\Tests;

use Symfony\Component\Filesystem\Filesystem;
use Pitpit\Component\MongoFilesystem\Finder\Finder;
use Symfony\Component\Finder\Finder as BaseFinder;
use Pitpit\Component\MongoFilesystem\Tests\MongoGridTestHelper;
use Pitpit\Component\MongoFilesystem\SplFileInfo;

class FinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $workspace = null;

    /**
     * @var boolean
     */
    protected $legacy;

    protected function getFinder()
    {
        if ($this->legacy) {
            return new BaseFinder();
        } else {
            return new Finder();
        }
    }

    protected function setUp()
    {
        $this->legacy = isset($_SERVER['LEGACY_TESTS']) ? (bool) $_SERVER['LEGACY_TESTS'] : false;
        $this->workspace = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid();
        $fs = new Filesystem();
        $fs->mkdir($this->workspace.'/bar');
        $fs->touch($this->workspace.'/foo.txt');
        $fs->touch($this->workspace.'/bar/foo.txt');
        $fs->touch($this->workspace.'/bar/.hidden');

        if (!$this->legacy) {
            MongoGridTestHelper::getGridFS()->storeBytes('', array('filename' => $this->workspace, 'mimeType' => SplFileInfo::FOLDER_MIMETYPE));
            MongoGridTestHelper::getGridFS()->storeBytes('', array('filename' => $this->workspace.'/bar', 'mimeType' => SplFileInfo::FOLDER_MIMETYPE));
            MongoGridTestHelper::getGridFS()->storeFile($this->workspace.'/foo.txt', array('mimeType' => 'text/plain'));
            MongoGridTestHelper::getGridFS()->storeFile($this->workspace.'/bar/foo.txt', array('mimeType' => 'text/plain'));
            MongoGridTestHelper::getGridFS()->storeFile($this->workspace.'/bar/.hidden', array('mimeType' => 'text/plain'));

            $fs->remove($this->workspace);
        }
    }

    protected function tearDown()
    {
        if ($this->legacy) {
            $fs = new Filesystem();
            $fs->remove($this->workspace);
        }
    }

    protected function assertFileIterator($filenames, $iterator)
    {
        $files = array();
        foreach ($iterator as $file) {
            $files[] = $file->getPathname();
        }

        $this->assertEquals($filenames, $files);
    }

    public function testIn()
    {
        $finder = $this->getFinder();
        $finder->in($this->workspace);

        $this->assertFileIterator(array(
            $this->workspace.'/bar',
            $this->workspace.'/bar/foo.txt',
            $this->workspace.'/foo.txt'
        ), $finder->getIterator());
    }

    public function testDepth()
    {
        $finder = $this->getFinder();
        $finder->in($this->workspace)->depth(0);

        $this->assertFileIterator(array(
            $this->workspace.'/bar',
            $this->workspace.'/foo.txt',
        ), $finder->getIterator());

        $finder = $this->getFinder();
        $finder->in($this->workspace)->depth(1);

        $this->assertFileIterator(array(
            $this->workspace.'/bar/foo.txt'
        ), $finder->getIterator());
    }

    public function testignoreDotFiles()
    {
        $finder = $this->getFinder();
        $finder->in($this->workspace)->ignoreDotFiles(true);

        $this->assertFileIterator(array(
            $this->workspace.'/bar',
            $this->workspace.'/bar/foo.txt',
            $this->workspace.'/foo.txt',
        ), $finder->getIterator());
    }
}
