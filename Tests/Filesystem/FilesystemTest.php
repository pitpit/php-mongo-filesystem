<?php

namespace Pitpit\Component\MongoFilesystem\Filesystem\Tests;

use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;
use Pitpit\Component\MongoFilesystem\Filesystem\Filesystem;
use Pitpit\Component\MongoFilesystem\SplFileInfo;
use Pitpit\Component\MongoFilesystem\Tests\MongoGridTestHelper;

/**
 * Test class for Filesystem.
 */
class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string $workspace
     */
    private $workspace = null;

    /**
     * @var integer $time
     */
    protected $time;

    /**
     * @var boolean
     */
    protected $legacy;

    // private static $symlinkOnWindows = null;

    // public static function setUpBeforeClass()
    // {
    //     if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
    //         self::$symlinkOnWindows = true;
    //         $originDir = tempnam(sys_get_temp_dir(), 'sl');
    //         $targetDir = tempnam(sys_get_temp_dir(), 'sl');
    //         if (true !== @symlink($originDir, $targetDir)) {
    //             $report = error_get_last();
    //             if (is_array($report) && false !== strpos($report['message'], 'error code(1314)')) {
    //                 self::$symlinkOnWindows = false;
    //             }
    //         }
    //     }
    // }


    /**
     * @return Filesystem
     */
    protected function getFilesystem()
    {
        if ($this->legacy) {
            return new BaseFilesystem();
        } else {
            return new Filesystem(MongoGridTestHelper::getGridFS());
        }
    }

    protected function setUp()
    {
        $this->legacy = isset($_SERVER['LEGACY_TESTS']) ? (bool) $_SERVER['LEGACY_TESTS'] : false;
        $this->workspace = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid();
        $this->time = time();

        $old = umask(0);
        mkdir($this->workspace.'/bar', 0777, true);
        chmod($this->workspace.'/bar', 01777);
        touch($this->workspace.'/foo.txt', $this->time, $this->time);
        touch($this->workspace.'/bar/foo.txt');
        // file_put_contents($this->workspace.'/bar/dummy.txt', 'bar');
        umask($old);

        if (!$this->legacy) {
            // MongoGridTestHelper::getGridFS()->storeBytes('', array('uploadDate' => new \MongoDate($this->time), 'filename' => '/', 'mimeType' => SplFileInfo::FOLDER_MIMETYPE));
            // MongoGridTestHelper::getGridFS()->storeBytes('', array('uploadDate' => new \MongoDate($this->time), 'filename' => getcwd(), 'mimeType' => SplFileInfo::FOLDER_MIMETYPE));
            MongoGridTestHelper::getGridFS()->storeBytes('', array('uploadDate' => new \MongoDate($this->time), 'filename' => $this->workspace, 'mimeType' => mime_content_type($this->workspace)));
            MongoGridTestHelper::getGridFS()->storeBytes('', array('uploadDate' => new \MongoDate($this->time), 'filename' => $this->workspace.'/bar', 'mimeType' => mime_content_type($this->workspace.'/bar')));
            MongoGridTestHelper::getGridFS()->storeFile($this->workspace.'/foo.txt', array('uploadDate' => new \MongoDate($this->time), 'mimeType' => mime_content_type($this->workspace.'/foo.txt')));
            MongoGridTestHelper::getGridFS()->storeFile($this->workspace.'/bar/foo.txt', array('uploadDate' => new \MongoDate($this->time), 'mimeType' => mime_content_type($this->workspace.'/bar/foo.txt')));
            // MongoGridTestHelper::getGridFS()->storeFile($this->workspace.'/bar/dummy.txt', array('uploadDate' => new \MongoDate($this->time), 'mimeType' => mime_content_type($this->workspace.'/bar/dummy.txt')));
            $this->clean($this->workspace);
        }
    }

    protected function tearDown()
    {
        if ($this->legacy) {
            $this->clean($this->workspace);
        }
    }

    /**
     * @param string $file
     */
    private function clean($file)
    {
        if (is_dir($file) && !is_link($file)) {
            $dir = new \FilesystemIterator($file);
            foreach ($dir as $childFile) {
                $this->clean($childFile);
            }

            rmdir($file);
        } else {
            unlink($file);
        }
    }

    // public function testCopyCreatesNewFile()
    // {
    //     $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
    //     $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

    //     file_put_contents($sourceFilePath, 'SOURCE FILE');

    //     $filesystem->copy($sourceFilePath, $targetFilePath);

    //     $this->assertFileExists($targetFilePath);
    //     $this->assertEquals('SOURCE FILE', file_get_contents($targetFilePath));
    // }

    // /**
    //  * @expectedException \Symfony\Component\Filesystem\Exception\IOException
    //  */
    // public function testCopyFails()
    // {
    //     $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
    //     $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

    //     $filesystem->copy($sourceFilePath, $targetFilePath);
    // }

    // public function testCopyOverridesExistingFileIfModified()
    // {
    //     $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
    //     $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

    //     file_put_contents($sourceFilePath, 'SOURCE FILE');
    //     file_put_contents($targetFilePath, 'TARGET FILE');
    //     touch($targetFilePath, time() - 1000);

    //     $filesystem->copy($sourceFilePath, $targetFilePath);

    //     $this->assertFileExists($targetFilePath);
    //     $this->assertEquals('SOURCE FILE', file_get_contents($targetFilePath));
    // }

    // public function testCopyDoesNotOverrideExistingFileByDefault()
    // {
    //     $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
    //     $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

    //     file_put_contents($sourceFilePath, 'SOURCE FILE');
    //     file_put_contents($targetFilePath, 'TARGET FILE');

    //     // make sure both files have the same modification time
    //     $modificationTime = time() - 1000;
    //     touch($sourceFilePath, $modificationTime);
    //     touch($targetFilePath, $modificationTime);

    //     $filesystem->copy($sourceFilePath, $targetFilePath);

    //     $this->assertFileExists($targetFilePath);
    //     $this->assertEquals('TARGET FILE', file_get_contents($targetFilePath));
    // }

    // public function testCopyOverridesExistingFileIfForced()
    // {
    //     $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
    //     $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

    //     file_put_contents($sourceFilePath, 'SOURCE FILE');
    //     file_put_contents($targetFilePath, 'TARGET FILE');

    //     // make sure both files have the same modification time
    //     $modificationTime = time() - 1000;
    //     touch($sourceFilePath, $modificationTime);
    //     touch($targetFilePath, $modificationTime);

    //     $filesystem->copy($sourceFilePath, $targetFilePath, true);

    //     $this->assertFileExists($targetFilePath);
    //     $this->assertEquals('SOURCE FILE', file_get_contents($targetFilePath));
    // }

    // public function testCopyCreatesTargetDirectoryIfItDoesNotExist()
    // {
    //     $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
    //     $targetFileDirectory = $this->workspace.DIRECTORY_SEPARATOR.'directory';
    //     $targetFilePath = $targetFileDirectory.DIRECTORY_SEPARATOR.'copy_target_file';

    //     file_put_contents($sourceFilePath, 'SOURCE FILE');

    //     $filesystem->copy($sourceFilePath, $targetFilePath);

    //     $this->assertTrue(is_dir($targetFileDirectory));
    //     $this->assertFileExists($targetFilePath);
    //     $this->assertEquals('SOURCE FILE', file_get_contents($targetFilePath));
    // }

    // public function testMkdirCreatesDirectoriesRecursively()
    // {
    //     $directory = $this->workspace
    //         .DIRECTORY_SEPARATOR.'directory'
    //         .DIRECTORY_SEPARATOR.'sub_directory';

    //     $filesystem->mkdir($directory);

    //     $this->assertTrue(is_dir($directory));
    // }

    // public function testMkdirCreatesDirectoriesFromArray()
    // {
    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR;
    //     $directories = array(
    //         $basePath.'1', $basePath.'2', $basePath.'3'
    //     );

    //     $filesystem->mkdir($directories);

    //     $this->assertTrue(is_dir($basePath.'1'));
    //     $this->assertTrue(is_dir($basePath.'2'));
    //     $this->assertTrue(is_dir($basePath.'3'));
    // }

    // public function testMkdirCreatesDirectoriesFromTraversableObject()
    // {
    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR;
    //     $directories = new \ArrayObject(array(
    //         $basePath.'1', $basePath.'2', $basePath.'3'
    //     ));

    //     $filesystem->mkdir($directories);

    //     $this->assertTrue(is_dir($basePath.'1'));
    //     $this->assertTrue(is_dir($basePath.'2'));
    //     $this->assertTrue(is_dir($basePath.'3'));
    // }

    // /**
    //  * @expectedException \Symfony\Component\Filesystem\Exception\IOException
    //  */
    // public function testMkdirCreatesDirectoriesFails()
    // {
    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR;
    //     $dir = $basePath.'2';

    //     file_put_contents($dir, '');

    //     $filesystem->mkdir($dir);
    // }

    public function testTouchCreatesEmptyFile()
    {
        $filesystem = $this->getFilesystem();
        $file = $this->workspace.DIRECTORY_SEPARATOR.'1';

        $filesystem->touch($file);

        $found = MongoGridTestHelper::getGridFS()->findOne(array('filename' => $file));

        $this->assertNotNull($found);
    }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\IOException
     */
    public function testTouchFails()
    {
        $filesystem = $this->getFilesystem();
        $file = $this->workspace.DIRECTORY_SEPARATOR.'1'.DIRECTORY_SEPARATOR.'2';

        $filesystem->touch($file);
    }

    // public function testTouchCreatesEmptyFilesFromArray()
    // {
    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR;
    //     $files = array(
    //         $basePath.'1', $basePath.'2', $basePath.'3'
    //     );

    //     $filesystem->touch($files);

    //     $this->assertFileExists($basePath.'1');
    //     $this->assertFileExists($basePath.'2');
    //     $this->assertFileExists($basePath.'3');
    // }

    // public function testTouchCreatesEmptyFilesFromTraversableObject()
    // {
    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR;
    //     $files = new \ArrayObject(array(
    //         $basePath.'1', $basePath.'2', $basePath.'3'
    //     ));

    //     $filesystem->touch($files);

    //     $this->assertFileExists($basePath.'1');
    //     $this->assertFileExists($basePath.'2');
    //     $this->assertFileExists($basePath.'3');
    // }

    // public function testRemoveCleansFilesAndDirectoriesIteratively()
    // {
    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR.'directory'.DIRECTORY_SEPARATOR;

    //     mkdir($basePath);
    //     mkdir($basePath.'dir');
    //     touch($basePath.'file');

    //     $filesystem->remove($basePath);

    //     $this->assertTrue(!is_dir($basePath));
    // }

    // public function testRemoveCleansArrayOfFilesAndDirectories()
    // {
    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR;

    //     mkdir($basePath.'dir');
    //     touch($basePath.'file');

    //     $files = array(
    //         $basePath.'dir', $basePath.'file'
    //     );

    //     $filesystem->remove($files);

    //     $this->assertTrue(!is_dir($basePath.'dir'));
    //     $this->assertTrue(!is_file($basePath.'file'));
    // }

    // public function testRemoveCleansTraversableObjectOfFilesAndDirectories()
    // {
    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR;

    //     mkdir($basePath.'dir');
    //     touch($basePath.'file');

    //     $files = new \ArrayObject(array(
    //         $basePath.'dir', $basePath.'file'
    //     ));

    //     $filesystem->remove($files);

    //     $this->assertTrue(!is_dir($basePath.'dir'));
    //     $this->assertTrue(!is_file($basePath.'file'));
    // }

    // public function testRemoveIgnoresNonExistingFiles()
    // {
    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR;

    //     mkdir($basePath.'dir');

    //     $files = array(
    //         $basePath.'dir', $basePath.'file'
    //     );

    //     $filesystem->remove($files);

    //     $this->assertTrue(!is_dir($basePath.'dir'));
    // }

    // public function testRemoveCleansInvalidLinks()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR.'directory'.DIRECTORY_SEPARATOR;

    //     mkdir($basePath);
    //     mkdir($basePath.'dir');
    //     // create symlink to unexisting file
    //     @symlink($basePath.'file', $basePath.'link');

    //     $filesystem->remove($basePath);

    //     $this->assertTrue(!is_dir($basePath));
    // }

    public function testFilesExists()
    {
        $filesystem = $this->getFilesystem();
        $this->assertTrue($filesystem->exists($this->workspace.'/foo.txt'));
        $this->assertTrue($filesystem->exists($this->workspace.'/bar'));
    }

    public function testFilesExistsTraversableObjectOfFilesAndDirectories()
    {
        $filesystem = $this->getFilesystem();
        $files = new \ArrayObject(array(
            $this->workspace.'/bar', $this->workspace.'/foo.txt'
        ));

        $this->assertTrue($filesystem->exists($files));
    }

    public function testFilesNotExistsTraversableObjectOfFilesAndDirectories()
    {
        $filesystem = $this->getFilesystem();
        $files = new \ArrayObject(array(
            $this->workspace.'/bar', $this->workspace.'/foo.txt', $this->workspace.'/unknown.txt'
        ));

        $this->assertFalse($filesystem->exists($files));
    }

    public function testInvalidFileNotExists()
    {
        $filesystem = $this->getFilesystem();
        $this->assertFalse($filesystem->exists($this->workspace.'/unknown.txt'));
    }

    // public function testChmodChangesFileMode()
    // {
    //     $this->markAsSkippedIfChmodIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);
    //     $file = $dir.DIRECTORY_SEPARATOR.'file';
    //     touch($file);

    //     $filesystem->chmod($file, 0400);
    //     $filesystem->chmod($dir, 0753);

    //     $this->assertEquals(753, $this->getFilePermissions($dir));
    //     $this->assertEquals(400, $this->getFilePermissions($file));
    // }

    // public function testChmodWrongMod()
    // {
    //     $this->markAsSkippedIfChmodIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     touch($dir);

    //     $filesystem->chmod($dir, 'Wrongmode');
    // }

    // public function testChmodRecursive()
    // {
    //     $this->markAsSkippedIfChmodIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);
    //     $file = $dir.DIRECTORY_SEPARATOR.'file';
    //     touch($file);

    //     $filesystem->chmod($file, 0400, 0000, true);
    //     $filesystem->chmod($dir, 0753, 0000, true);

    //     $this->assertEquals(753, $this->getFilePermissions($dir));
    //     $this->assertEquals(753, $this->getFilePermissions($file));
    // }

    // public function testChmodAppliesUmask()
    // {
    //     $this->markAsSkippedIfChmodIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     touch($file);

    //     $filesystem->chmod($file, 0770, 0022);
    //     $this->assertEquals(750, $this->getFilePermissions($file));
    // }

    // public function testChmodChangesModeOfArrayOfFiles()
    // {
    //     $this->markAsSkippedIfChmodIsMissing();

    //     $directory = $this->workspace.DIRECTORY_SEPARATOR.'directory';
    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $files = array($directory, $file);

    //     mkdir($directory);
    //     touch($file);

    //     $filesystem->chmod($files, 0753);

    //     $this->assertEquals(753, $this->getFilePermissions($file));
    //     $this->assertEquals(753, $this->getFilePermissions($directory));
    // }

    // public function testChmodChangesModeOfTraversableFileObject()
    // {
    //     $this->markAsSkippedIfChmodIsMissing();

    //     $directory = $this->workspace.DIRECTORY_SEPARATOR.'directory';
    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $files = new \ArrayObject(array($directory, $file));

    //     mkdir($directory);
    //     touch($file);

    //     $filesystem->chmod($files, 0753);

    //     $this->assertEquals(753, $this->getFilePermissions($file));
    //     $this->assertEquals(753, $this->getFilePermissions($directory));
    // }

    // public function testChown()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);

    //     $filesystem->chown($dir, $this->getFileOwner($dir));
    // }

    // public function testChownRecursive()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);
    //     $file = $dir.DIRECTORY_SEPARATOR.'file';
    //     touch($file);

    //     $filesystem->chown($dir, $this->getFileOwner($dir), true);
    // }

    // public function testChownSymlink()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);

    //     $filesystem->symlink($file, $link);

    //     $filesystem->chown($link, $this->getFileOwner($link));
    // }

    // /**
    //  * @expectedException \Symfony\Component\Filesystem\Exception\IOException
    //  */
    // public function testChownSymlinkFails()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);

    //     $filesystem->symlink($file, $link);

    //     $filesystem->chown($link, 'user'.time().mt_rand(1000, 9999));
    // }

    // /**
    //  * @expectedException \Symfony\Component\Filesystem\Exception\IOException
    //  */
    // public function testChownFail()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);

    //     $filesystem->chown($dir, 'user'.time().mt_rand(1000, 9999));
    // }

    // public function testChgrp()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);

    //     $filesystem->chgrp($dir, $this->getFileGroup($dir));
    // }

    // public function testChgrpRecursive()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);
    //     $file = $dir.DIRECTORY_SEPARATOR.'file';
    //     touch($file);

    //     $filesystem->chgrp($dir, $this->getFileGroup($dir), true);
    // }

    // public function testChgrpSymlink()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);

    //     $filesystem->symlink($file, $link);

    //     $filesystem->chgrp($link, $this->getFileGroup($link));
    // }

    // /**
    //  * @expectedException \Symfony\Component\Filesystem\Exception\IOException
    //  */
    // public function testChgrpSymlinkFails()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);

    //     $filesystem->symlink($file, $link);

    //     $filesystem->chgrp($link, 'user'.time().mt_rand(1000, 9999));
    // }

    // /**
    //  * @expectedException \Symfony\Component\Filesystem\Exception\IOException
    //  */
    // public function testChgrpFail()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);

    //     $filesystem->chgrp($dir, 'user'.time().mt_rand(1000, 9999));
    // }

    // public function testRename()
    // {
    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';
    //     touch($file);

    //     $filesystem->rename($file, $newPath);

    //     $this->assertFileNotExists($file);
    //     $this->assertFileExists($newPath);
    // }

    // /**
    //  * @expectedException \Symfony\Component\Filesystem\Exception\IOException
    //  */
    // public function testRenameThrowsExceptionIfTargetAlreadyExists()
    // {
    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';

    //     touch($file);
    //     touch($newPath);

    //     $filesystem->rename($file, $newPath);
    // }

    // public function testRenameOverwritesTheTargetIfItAlreadyExists()
    // {
    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';

    //     touch($file);
    //     touch($newPath);

    //     $filesystem->rename($file, $newPath, true);

    //     $this->assertFileNotExists($file);
    //     $this->assertFileExists($newPath);
    // }

    // /**
    //  * @expectedException \Symfony\Component\Filesystem\Exception\IOException
    //  */
    // public function testRenameThrowsExceptionOnError()
    // {
    //     $file = $this->workspace.DIRECTORY_SEPARATOR.uniqid();
    //     $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';

    //     $filesystem->rename($file, $newPath);
    // }

    // public function testSymlink()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);

    //     $filesystem->symlink($file, $link);

    //     $this->assertTrue(is_link($link));
    //     $this->assertEquals($file, readlink($link));
    // }

    // /**
    //  * @depends testSymlink
    //  */
    // public function testRemoveSymlink()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     $filesystem->remove($link);

    //     $this->assertTrue(!is_link($link));
    // }

    // public function testSymlinkIsOverwrittenIfPointsToDifferentTarget()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);
    //     symlink($this->workspace, $link);

    //     $filesystem->symlink($file, $link);

    //     $this->assertTrue(is_link($link));
    //     $this->assertEquals($file, readlink($link));
    // }

    // public function testSymlinkIsNotOverwrittenIfAlreadyCreated()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);
    //     symlink($file, $link);

    //     $filesystem->symlink($file, $link);

    //     $this->assertTrue(is_link($link));
    //     $this->assertEquals($file, readlink($link));
    // }

    // public function testSymlinkCreatesTargetDirectoryIfItDoesNotExist()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link1 = $this->workspace.DIRECTORY_SEPARATOR.'dir'.DIRECTORY_SEPARATOR.'link';
    //     $link2 = $this->workspace.DIRECTORY_SEPARATOR.'dir'.DIRECTORY_SEPARATOR.'subdir'.DIRECTORY_SEPARATOR.'link';

    //     touch($file);

    //     $filesystem->symlink($file, $link1);
    //     $filesystem->symlink($file, $link2);

    //     $this->assertTrue(is_link($link1));
    //     $this->assertEquals($file, readlink($link1));
    //     $this->assertTrue(is_link($link2));
    //     $this->assertEquals($file, readlink($link2));
    // }

    // /**
    //  * @dataProvider providePathsForMakePathRelative
    //  */
    // public function testMakePathRelative($endPath, $startPath, $expectedPath)
    // {
    //     $path = $filesystem->makePathRelative($endPath, $startPath);

    //     $this->assertEquals($expectedPath, $path);
    // }

    // /**
    //  * @return array
    //  */
    // public function providePathsForMakePathRelative()
    // {
    //     $paths = array(
    //         array('/var/lib/symfony/src/Symfony/', '/var/lib/symfony/src/Symfony/Component', '../'),
    //         array('/var/lib/symfony/src/Symfony/', '/var/lib/symfony/src/Symfony/Component/', '../'),
    //         array('/var/lib/symfony/src/Symfony', '/var/lib/symfony/src/Symfony/Component', '../'),
    //         array('/var/lib/symfony/src/Symfony', '/var/lib/symfony/src/Symfony/Component/', '../'),
    //         array('var/lib/symfony/', 'var/lib/symfony/src/Symfony/Component', '../../../'),
    //         array('/usr/lib/symfony/', '/var/lib/symfony/src/Symfony/Component', '../../../../../../usr/lib/symfony/'),
    //         array('/var/lib/symfony/src/Symfony/', '/var/lib/symfony/', 'src/Symfony/'),
    //         array('/aa/bb', '/aa/bb', './'),
    //         array('/aa/bb', '/aa/bb/', './'),
    //         array('/aa/bb/', '/aa/bb', './'),
    //         array('/aa/bb/', '/aa/bb/', './'),
    //         array('/aa/bb/cc', '/aa/bb/cc/dd', '../'),
    //         array('/aa/bb/cc', '/aa/bb/cc/dd/', '../'),
    //         array('/aa/bb/cc/', '/aa/bb/cc/dd', '../'),
    //         array('/aa/bb/cc/', '/aa/bb/cc/dd/', '../'),
    //         array('/aa/bb/cc', '/aa', 'bb/cc/'),
    //         array('/aa/bb/cc', '/aa/', 'bb/cc/'),
    //         array('/aa/bb/cc/', '/aa', 'bb/cc/'),
    //         array('/aa/bb/cc/', '/aa/', 'bb/cc/'),
    //         array('/a/aab/bb', '/a/aa', '../aab/bb/'),
    //         array('/a/aab/bb', '/a/aa/', '../aab/bb/'),
    //         array('/a/aab/bb/', '/a/aa', '../aab/bb/'),
    //         array('/a/aab/bb/', '/a/aa/', '../aab/bb/'),
    //     );

    //     if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
    //         $paths[] = array('c:\var\lib/symfony/src/Symfony/', 'c:/var/lib/symfony/', 'src/Symfony/');
    //     }

    //     return $paths;
    // }

    // public function testMirrorCopiesFilesAndDirectoriesRecursively()
    // {
    //     $sourcePath = $this->workspace.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR;
    //     $directory = $sourcePath.'directory'.DIRECTORY_SEPARATOR;
    //     $file1 = $directory.'file1';
    //     $file2 = $sourcePath.'file2';

    //     mkdir($sourcePath);
    //     mkdir($directory);
    //     file_put_contents($file1, 'FILE1');
    //     file_put_contents($file2, 'FILE2');

    //     $targetPath = $this->workspace.DIRECTORY_SEPARATOR.'target'.DIRECTORY_SEPARATOR;

    //     $filesystem->mirror($sourcePath, $targetPath);

    //     $this->assertTrue(is_dir($targetPath));
    //     $this->assertTrue(is_dir($targetPath.'directory'));
    //     $this->assertFileEquals($file1, $targetPath.'directory'.DIRECTORY_SEPARATOR.'file1');
    //     $this->assertFileEquals($file2, $targetPath.'file2');

    //     $filesystem->remove($file1);

    //     $filesystem->mirror($sourcePath, $targetPath, null, array('delete' => false));
    //     $this->assertTrue($filesystem->exists($targetPath.'directory'.DIRECTORY_SEPARATOR.'file1'));

    //     $filesystem->mirror($sourcePath, $targetPath, null, array('delete' => true));
    //     $this->assertFalse($filesystem->exists($targetPath.'directory'.DIRECTORY_SEPARATOR.'file1'));

    //     file_put_contents($file1, 'FILE1');

    //     $filesystem->mirror($sourcePath, $targetPath, null, array('delete' => true));
    //     $this->assertTrue($filesystem->exists($targetPath.'directory'.DIRECTORY_SEPARATOR.'file1'));

    //     $filesystem->remove($directory);
    //     $filesystem->mirror($sourcePath, $targetPath, null, array('delete' => true));
    //     $this->assertFalse($filesystem->exists($targetPath.'directory'));
    //     $this->assertFalse($filesystem->exists($targetPath.'directory'.DIRECTORY_SEPARATOR.'file1'));
    // }

    // public function testMirrorCopiesLinks()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $sourcePath = $this->workspace.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR;

    //     mkdir($sourcePath);
    //     file_put_contents($sourcePath.'file1', 'FILE1');
    //     symlink($sourcePath.'file1', $sourcePath.'link1');

    //     $targetPath = $this->workspace.DIRECTORY_SEPARATOR.'target'.DIRECTORY_SEPARATOR;

    //     $filesystem->mirror($sourcePath, $targetPath);

    //     $this->assertTrue(is_dir($targetPath));
    //     $this->assertFileEquals($sourcePath.'file1', $targetPath.DIRECTORY_SEPARATOR.'link1');
    //     $this->assertTrue(is_link($targetPath.DIRECTORY_SEPARATOR.'link1'));
    // }

    // public function testMirrorCopiesLinkedDirectoryContents()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $sourcePath = $this->workspace.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR;

    //     mkdir($sourcePath.'nested/', 0777, true);
    //     file_put_contents($sourcePath.'/nested/file1.txt', 'FILE1');
    //     // Note: We symlink directory, not file
    //     symlink($sourcePath.'nested', $sourcePath.'link1');

    //     $targetPath = $this->workspace.DIRECTORY_SEPARATOR.'target'.DIRECTORY_SEPARATOR;

    //     $filesystem->mirror($sourcePath, $targetPath);

    //     $this->assertTrue(is_dir($targetPath));
    //     $this->assertFileEquals($sourcePath.'/nested/file1.txt', $targetPath.DIRECTORY_SEPARATOR.'link1/file1.txt');
    //     $this->assertTrue(is_link($targetPath.DIRECTORY_SEPARATOR.'link1'));
    // }

    // /**
    //  * @dataProvider providePathsForIsAbsolutePath
    //  */
    // public function testIsAbsolutePath($path, $expectedResult)
    // {
    //     $result = $filesystem->isAbsolutePath($path);

    //     $this->assertEquals($expectedResult, $result);
    // }

    // /**
    //  * @return array
    //  */
    // public function providePathsForIsAbsolutePath()
    // {
    //     return array(
    //         array('/var/lib', true),
    //         array('c:\\\\var\\lib', true),
    //         array('\\var\\lib', true),
    //         array('var/lib', false),
    //         array('../var/lib', false),
    //         array('', false),
    //         array(null, false)
    //     );
    // }

    // public function testDumpFile()
    // {
    //     $filename = $this->workspace.DIRECTORY_SEPARATOR.'foo'.DIRECTORY_SEPARATOR.'baz.txt';

    //     $filesystem->dumpFile($filename, 'bar', 0753);

    //     $this->assertFileExists($filename);
    //     $this->assertSame('bar', file_get_contents($filename));

    //     // skip mode check on windows
    //     if (!defined('PHP_WINDOWS_VERSION_MAJOR')) {
    //         $this->assertEquals(753, $this->getFilePermissions($filename));
    //     }
    // }

    // public function testDumpFileOverwritesAnExistingFile()
    // {
    //     $filename = $this->workspace.DIRECTORY_SEPARATOR.'foo.txt';
    //     file_put_contents($filename, 'FOO BAR');

    //     $filesystem->dumpFile($filename, 'bar');

    //     $this->assertFileExists($filename);
    //     $this->assertSame('bar', file_get_contents($filename));
    // }

    // /**
    //  * Returns file permissions as three digits (i.e. 755)
    //  *
    //  * @param string $filePath
    //  *
    //  * @return integer
    //  */
    // private function getFilePermissions($filePath)
    // {
    //     return (int) substr(sprintf('%o', fileperms($filePath)), -3);
    // }

    // private function getFileOwner($filepath)
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $infos = stat($filepath);
    //     if ($datas = posix_getpwuid($infos['uid'])) {
    //         return $datas['name'];
    //     }
    // }

    // private function getFileGroup($filepath)
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $infos = stat($filepath);
    //     if ($datas = posix_getgrgid($infos['gid'])) {
    //         return $datas['name'];
    //     }
    // }

    // private function markAsSkippedIfSymlinkIsMissing()
    // {
    //     if (!function_exists('symlink')) {
    //         $this->markTestSkipped('symlink is not supported');
    //     }

    //     if (defined('PHP_WINDOWS_VERSION_MAJOR') && false === self::$symlinkOnWindows) {
    //         $this->markTestSkipped('symlink requires "Create symbolic links" privilege on windows');
    //     }
    // }

    // private function markAsSkippedIfChmodIsMissing()
    // {
    //     if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
    //         $this->markTestSkipped('chmod is not supported on windows');
    //     }
    // }

    // private function markAsSkippedIfPosixIsMissing()
    // {
    //     if (defined('PHP_WINDOWS_VERSION_MAJOR') || !function_exists('posix_isatty')) {
    //         $this->markTestSkipped('Posix is not supported');
    //     }
    // }
}
