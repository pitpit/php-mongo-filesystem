<?php

namespace Pitpit\Component\MongoFilesystem\Filesystem\Tests;

use Pitpit\Component\MongoFilesystem\Filesystem\Filesystem;
use Pitpit\Component\MongoFilesystem\Tests\MongoGridTestHelper;

/**
 * Test class for Filesystem.
 *
 * Developed from Symfony\Component\Filesystem\Filesystem part of the Symfony package
 * and copyrighted to: (c) Fabien Potencier <fabien@symfony.com>
 * Released under the following license: https://github.com/symfony/symfony/blob/master/LICENSE
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
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var MongoGridFS
     */
    protected $gridfs;

    protected function setUp()
    {
        $this->gridfs = MongoGridTestHelper::getGridFS();
        $this->filesystem = new Filesystem($this->gridfs);
        $this->workspace = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid();
        $this->time = time();

        $this->gridfs->storeBytes('', array('filename' => $this->workspace, 'type' => 'dir'));
    }

    protected function tearDown()
    {
        $this->gridfs->drop();
    }

    /**
     * Reimp of PHPUnit_Framework_Assert::assertFileExists to use MongoDB instead of physical drive
     *
     * {@inheritdoc}
     */
    public static function assertFileExists($filepath, $message = null)
    {
        if (!is_string($filepath)) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }
        $found = MongoGridTestHelper::getGridFS()->findOne(array('filename' => $filepath));

        self::assertNotNull($found, $message);
    }

    /**
     * Reimp of PHPUnit_Framework_Assert::assertFileExists to use MongoDB instead of physical drive
     *
     * {@inheritdoc}
     */
    public static function assertFileNotExists($filepath, $message = null)
    {
        if (!is_string($filepath)) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }
        $found = MongoGridTestHelper::getGridFS()->findOne(array('filename' => $filepath));

        self::assertNull($found, $message);
    }

    /**
     * Test  over MongoDB content of $filepath
     */
    public static function assertFileContent($content, $filepath, $message = null)
    {
        if (!is_string($filepath)) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }
        $found = MongoGridTestHelper::getGridFS()->findOne(array('filename' => $filepath));

        self::assertNotNull($found);
        self::assertEquals($content, $found->getBytes(), $message);
    }

    /**
     * Test  over MongoDB if $filepath is a directory
     */
    public static function assertIsDir($filepath, $message = null)
    {
        if (!is_string($filepath)) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }
        $found = MongoGridTestHelper::getGridFS()->findOne(array('filename' => $filepath));

        self::assertNotNull($found);
        self::assertEquals('dir', $found->file['type'], $message);
    }

    public function testCopyCreatesNewFile()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

        $this->gridfs->storeBytes('SOURCE FILE', array('filename' => $sourceFilePath, 'type' => 'file'));

        $this->filesystem->copy($sourceFilePath, $targetFilePath);

        $this->assertFileExists($targetFilePath);
        $this->assertFileContent('SOURCE FILE', $targetFilePath);
    }

    // public function testCopyFromDisk()
    // {
    //     $sourceFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'copy_source_file'.rand(0, 9999);
    //     $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

    //     @mkdir(dirname($sourceFilePath), 0777, true);
    //     file_put_contents($sourceFilePath, 'SOURCE FILE');

    //     $this->filesystem->copy($sourceFilePath, $targetFilePath);

    //     $this->assertFileExists($targetFilePath);
    //     $this->assertEquals('SOURCE FILE', $this->gridfs->findOne(array('filename' => $targetFilePath))->getBytes());
    // }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\IOException
     */
    public function testCopyFails()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

        $this->filesystem->copy($sourceFilePath, $targetFilePath);
    }

    public function testCopyOverridesExistingFileIfModified()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

        $this->gridfs->storeBytes('SOURCE FILE', array('filename' => $sourceFilePath, 'type' => 'file'));
        $this->gridfs->storeBytes('TARGET FILE', array('filename' => $targetFilePath, 'type' => 'file', 'uploadDate' => new \MongoDate(time() - 1000)));

        $this->filesystem->copy($sourceFilePath, $targetFilePath);

        $this->assertFileExists($targetFilePath);
        $this->assertFileContent('SOURCE FILE', $targetFilePath);
    }

    public function testCopyDoesNotOverrideExistingFileByDefault()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

        $time = time() - 1000;
        $this->gridfs->storeBytes('SOURCE FILE', array('filename' => $sourceFilePath, 'type' => 'file', 'uploadDate' => new \MongoDate($time)));
        $this->gridfs->storeBytes('TARGET FILE', array('filename' => $targetFilePath, 'type' => 'file', 'uploadDate' => new \MongoDate($time)));

        $this->filesystem->copy($sourceFilePath, $targetFilePath);

        $this->assertFileExists($targetFilePath);
        $this->assertFileContent('TARGET FILE', $targetFilePath);
    }

    public function testCopyOverridesExistingFileIfForced()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

        $time = time() - 1000;
        $this->gridfs->storeBytes('SOURCE FILE', array('filename' => $sourceFilePath, 'type' => 'file', 'uploadDate' => new \MongoDate($time)));
        $this->gridfs->storeBytes('TARGET FILE', array('filename' => $targetFilePath, 'type' => 'file', 'uploadDate' => new \MongoDate($time)));

        $this->filesystem->copy($sourceFilePath, $targetFilePath, true);

        $this->assertFileExists($targetFilePath);
        $this->assertFileContent('SOURCE FILE', $targetFilePath);
    }

    public function testCopyCreatesTargetDirectoryIfItDoesNotExist()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFileDirectory = $this->workspace.DIRECTORY_SEPARATOR.'directory';
        $targetFilePath = $targetFileDirectory.DIRECTORY_SEPARATOR.'copy_target_file';

        $this->gridfs->storeBytes('SOURCE FILE', array('filename' => $sourceFilePath, 'type' => 'file'));

        $this->filesystem->copy($sourceFilePath, $targetFilePath);

        $this->assertIsDir($targetFileDirectory);
        $this->assertFileExists($targetFilePath);
        $this->assertFileContent('SOURCE FILE', $targetFilePath);
    }

    public function testMkdirCreatesDirectoriesRecursively()
    {
        $this->filesystem->mkdir($this->workspace.DIRECTORY_SEPARATOR.'directory'.DIRECTORY_SEPARATOR.'sub_directory');

        $this->assertIsDir($this->workspace.DIRECTORY_SEPARATOR.'directory');
        $this->assertIsDir($this->workspace.DIRECTORY_SEPARATOR.'directory'.DIRECTORY_SEPARATOR.'sub_directory');
    }

    public function testMkdirCreatesDirectoriesFromArray()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;
        $directories = array(
            $basePath.'1', $basePath.'2', $basePath.'3'
        );

        $this->filesystem->mkdir($directories);

        $this->assertIsDir($basePath.'1');
        $this->assertIsDir($basePath.'2');
        $this->assertIsDir($basePath.'3');
    }

    public function testMkdirCreatesDirectoriesFromTraversableObject()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;
        $directories = new \ArrayObject(array(
            $basePath.'1', $basePath.'2', $basePath.'3'
        ));

        $this->filesystem->mkdir($directories);

        $this->assertIsDir($basePath.'1');
        $this->assertIsDir($basePath.'2');
        $this->assertIsDir($basePath.'3');
    }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\IOException
     */
    public function testMkdirCreatesDirectoriesFails()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;
        $dir = $basePath.'2';

        $this->filesystem->touch($dir);

        $this->filesystem->mkdir($dir);
    }

    public function testTouchCreatesEmptyFile()
    {
        $file = $this->workspace.DIRECTORY_SEPARATOR.'1';

        $this->filesystem->touch($file);

        $this->assertFileExists($file);
    }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\IOException
     */
    public function testTouchFails()
    {
        $file = $this->workspace.DIRECTORY_SEPARATOR.'1'.DIRECTORY_SEPARATOR.'2';

        $this->filesystem->touch($file);
    }

    public function testTouchCreatesEmptyFilesFromArray()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;
        $files = array(
            $basePath.'1', $basePath.'2', $basePath.'3'
        );

        $this->filesystem->touch($files);

        $this->assertFileExists($basePath.'1');
        $this->assertFileExists($basePath.'2');
        $this->assertFileExists($basePath.'3');
    }

    public function testTouchCreatesEmptyFilesFromTraversableObject()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;
        $files = new \ArrayObject(array(
            $basePath.'1', $basePath.'2', $basePath.'3'
        ));

        $this->filesystem->touch($files);

        $this->assertFileExists($basePath.'1');
        $this->assertFileExists($basePath.'2');
        $this->assertFileExists($basePath.'3');
    }

    public function testRemoveCleansFilesAndDirectoriesIteratively()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR.'directory';

        $this->gridfs->storeBytes('', array('filename' => $basePath, 'type' => 'dir'));
        $this->gridfs->storeBytes('', array('filename' => $basePath.DIRECTORY_SEPARATOR.'dir', 'type' => 'dir'));
        $this->gridfs->storeBytes('', array('filename' => $basePath.DIRECTORY_SEPARATOR.'file', 'type' => 'file'));

        $this->filesystem->remove($basePath);

        $this->assertFileNotExists($basePath.DIRECTORY_SEPARATOR.'dir');
        $this->assertFileNotExists($basePath.DIRECTORY_SEPARATOR.'file');
        $this->assertFileNotExists($basePath);
    }

    public function testRemoveCleansArrayOfFilesAndDirectories()
    {
        $basePath = $this->workspace;


        $this->gridfs->storeBytes('', array('filename' => $basePath, 'type' => 'dir'));
        $this->gridfs->storeBytes('', array('filename' => $basePath.DIRECTORY_SEPARATOR.'dir', 'type' => 'dir'));
        $this->gridfs->storeBytes('', array('filename' => $basePath.DIRECTORY_SEPARATOR.'file', 'type' => 'file'));

        $files = array(
            $basePath.DIRECTORY_SEPARATOR.'dir', $basePath.DIRECTORY_SEPARATOR.'file'
        );

        $this->filesystem->remove($files);

        $this->assertFileNotExists($basePath.DIRECTORY_SEPARATOR.'dir');
        $this->assertFileNotExists($basePath.DIRECTORY_SEPARATOR.'file');
    }

    public function testRemoveCleansTraversableObjectOfFilesAndDirectories()
    {
        $basePath = $this->workspace;

        $this->gridfs->storeBytes('', array('filename' => $basePath, 'type' => 'dir'));
        $this->gridfs->storeBytes('', array('filename' => $basePath.DIRECTORY_SEPARATOR.'dir', 'type' => 'dir'));
        $this->gridfs->storeBytes('', array('filename' => $basePath.DIRECTORY_SEPARATOR.'file', 'type' => 'file'));

        $files = new \ArrayObject(array(
            $basePath.DIRECTORY_SEPARATOR.'dir', $basePath.DIRECTORY_SEPARATOR.'file'
        ));

        $this->filesystem->remove($files);

        $this->assertFileNotExists($basePath.DIRECTORY_SEPARATOR.'dir');
        $this->assertFileNotExists($basePath.DIRECTORY_SEPARATOR.'file');
    }

    public function testRemoveIgnoresNonExistingFiles()
    {
        $basePath = $this->workspace;

        $this->gridfs->storeBytes('', array('filename' => $basePath.DIRECTORY_SEPARATOR.'dir', 'type' => 'dir'));

        $files = array(
            $basePath.DIRECTORY_SEPARATOR.'dir', $basePath.DIRECTORY_SEPARATOR.'file'
        );

        $this->filesystem->remove($files);

       $this->assertFileNotExists($basePath.DIRECTORY_SEPARATOR.'dir');
    }

    // public function testRemoveCleansInvalidLinks()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $basePath = $this->workspace.DIRECTORY_SEPARATOR.'directory'.DIRECTORY_SEPARATOR;

    //     mkdir($basePath);
    //     mkdir($basePath.'dir');
    //     // create symlink to unexisting file
    //     @symlink($basePath.'file', $basePath.'link');

    //     $this->filesystem->remove($basePath);

    //     $this->assertTrue(!is_dir($basePath));
    // }

    public function testFilesExists()
    {
        $this->gridfs->storeBytes('bar', array('filename' => $this->workspace.'/file1', 'type' => 'file'));
        $this->gridfs->storeBytes('', array('filename' => $this->workspace.'/folder', 'type' => 'dir'));

        $this->assertTrue($this->filesystem->exists($this->workspace.'/file1'));
        $this->assertTrue($this->filesystem->exists($this->workspace.'/folder'));
    }

    public function testFilesExistsTraversableObjectOfFilesAndDirectories()
    {
        $this->gridfs->storeBytes('bar', array('filename' => $this->workspace.'/file1', 'type' => 'file'));
        $this->gridfs->storeBytes('', array('filename' => $this->workspace.'/folder', 'type' => 'dir'));

        $files = new \ArrayObject(array(
            $this->workspace.'/folder', $this->workspace.'/file1'
        ));

        $this->assertTrue($this->filesystem->exists($files));
    }

    public function testFilesNotExistsTraversableObjectOfFilesAndDirectories()
    {
        $this->gridfs->storeBytes('bar', array('filename' => $this->workspace.'/file1', 'type' => 'file'));
        $this->gridfs->storeBytes('', array('filename' => $this->workspace.'/folder', 'type' => 'dir'));

        $files = new \ArrayObject(array(
            $this->workspace.'/folder', $this->workspace.'/file1', $this->workspace.'/unknown'
        ));

        $this->assertFalse($this->filesystem->exists($files));
    }

    public function testInvalidFileNotExists()
    {
        $this->assertFalse($this->filesystem->exists($this->workspace.'/unknown'));
    }

    // public function testChmodChangesFileMode()
    // {
    //     $this->markAsSkippedIfChmodIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);
    //     $file = $dir.DIRECTORY_SEPARATOR.'file';
    //     touch($file);

    //     $this->filesystem->chmod($file, 0400);
    //     $this->filesystem->chmod($dir, 0753);

    //     $this->assertEquals(753, $this->getFilePermissions($dir));
    //     $this->assertEquals(400, $this->getFilePermissions($file));
    // }

    // public function testChmodWrongMod()
    // {
    //     $this->markAsSkippedIfChmodIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     touch($dir);

    //     $this->filesystem->chmod($dir, 'Wrongmode');
    // }

    // public function testChmodRecursive()
    // {
    //     $this->markAsSkippedIfChmodIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);
    //     $file = $dir.DIRECTORY_SEPARATOR.'file';
    //     touch($file);

    //     $this->filesystem->chmod($file, 0400, 0000, true);
    //     $this->filesystem->chmod($dir, 0753, 0000, true);

    //     $this->assertEquals(753, $this->getFilePermissions($dir));
    //     $this->assertEquals(753, $this->getFilePermissions($file));
    // }

    // public function testChmodAppliesUmask()
    // {
    //     $this->markAsSkippedIfChmodIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     touch($file);

    //     $this->filesystem->chmod($file, 0770, 0022);
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

    //     $this->filesystem->chmod($files, 0753);

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

    //     $this->filesystem->chmod($files, 0753);

    //     $this->assertEquals(753, $this->getFilePermissions($file));
    //     $this->assertEquals(753, $this->getFilePermissions($directory));
    // }

    // public function testChown()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);

    //     $this->filesystem->chown($dir, $this->getFileOwner($dir));
    // }

    // public function testChownRecursive()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);
    //     $file = $dir.DIRECTORY_SEPARATOR.'file';
    //     touch($file);

    //     $this->filesystem->chown($dir, $this->getFileOwner($dir), true);
    // }

    // public function testChownSymlink()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);

    //     $this->filesystem->symlink($file, $link);

    //     $this->filesystem->chown($link, $this->getFileOwner($link));
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

    //     $this->filesystem->symlink($file, $link);

    //     $this->filesystem->chown($link, 'user'.time().mt_rand(1000, 9999));
    // }

    // /**
    //  * @expectedException \Symfony\Component\Filesystem\Exception\IOException
    //  */
    // public function testChownFail()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);

    //     $this->filesystem->chown($dir, 'user'.time().mt_rand(1000, 9999));
    // }

    // public function testChgrp()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);

    //     $this->filesystem->chgrp($dir, $this->getFileGroup($dir));
    // }

    // public function testChgrpRecursive()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);
    //     $file = $dir.DIRECTORY_SEPARATOR.'file';
    //     touch($file);

    //     $this->filesystem->chgrp($dir, $this->getFileGroup($dir), true);
    // }

    // public function testChgrpSymlink()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);

    //     $this->filesystem->symlink($file, $link);

    //     $this->filesystem->chgrp($link, $this->getFileGroup($link));
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

    //     $this->filesystem->symlink($file, $link);

    //     $this->filesystem->chgrp($link, 'user'.time().mt_rand(1000, 9999));
    // }

    // /**
    //  * @expectedException \Symfony\Component\Filesystem\Exception\IOException
    //  */
    // public function testChgrpFail()
    // {
    //     $this->markAsSkippedIfPosixIsMissing();

    //     $dir = $this->workspace.DIRECTORY_SEPARATOR.'dir';
    //     mkdir($dir);

    //     $this->filesystem->chgrp($dir, 'user'.time().mt_rand(1000, 9999));
    // }

    public function testRename()
    {
        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';
        // touch($file);

        $this->gridfs->storeBytes('', array('filename' => $file, 'type' => 'file'));

        $this->filesystem->rename($file, $newPath);

        $this->assertFileNotExists($file);
        $this->assertFileExists($newPath);
    }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\IOException
     */
    public function testRenameThrowsExceptionIfTargetAlreadyExists()
    {
        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';

        $this->gridfs->storeBytes('', array('filename' => $file, 'type' => 'file'));
        $this->gridfs->storeBytes('', array('filename' => $newPath, 'type' => 'file'));

        $this->filesystem->rename($file, $newPath);
    }

    public function testRenameOverwritesTheTargetIfItAlreadyExists()
    {
        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';

        $this->gridfs->storeBytes('foo', array('filename' => $file, 'type' => 'file'));
        $this->gridfs->storeBytes('bar', array('filename' => $newPath, 'type' => 'file'));

        $this->filesystem->rename($file, $newPath, true);

        $this->assertFileNotExists($file);
        $this->assertFileExists($newPath);
        $this->assertFileContent('foo', $newPath);
    }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\IOException
     */
    public function testRenameThrowsExceptionOnError()
    {
        $file = $this->workspace.DIRECTORY_SEPARATOR.uniqid();
        $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';

        $this->filesystem->rename($file, $newPath);
    }

    // public function testSymlink()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);

    //     $this->filesystem->symlink($file, $link);

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

    //     $this->filesystem->remove($link);

    //     $this->assertTrue(!is_link($link));
    // }

    // public function testSymlinkIsOverwrittenIfPointsToDifferentTarget()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
    //     $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

    //     touch($file);
    //     symlink($this->workspace, $link);

    //     $this->filesystem->symlink($file, $link);

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

    //     $this->filesystem->symlink($file, $link);

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

    //     $this->filesystem->symlink($file, $link1);
    //     $this->filesystem->symlink($file, $link2);

    //     $this->assertTrue(is_link($link1));
    //     $this->assertEquals($file, readlink($link1));
    //     $this->assertTrue(is_link($link2));
    //     $this->assertEquals($file, readlink($link2));
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

    //     $this->filesystem->mirror($sourcePath, $targetPath);

    //     $this->assertTrue(is_dir($targetPath));
    //     $this->assertTrue(is_dir($targetPath.'directory'));
    //     $this->assertFileEquals($file1, $targetPath.'directory'.DIRECTORY_SEPARATOR.'file1');
    //     $this->assertFileEquals($file2, $targetPath.'file2');

    //     $this->filesystem->remove($file1);

    //     $this->filesystem->mirror($sourcePath, $targetPath, null, array('delete' => false));
    //     $this->assertTrue($this->filesystem->exists($targetPath.'directory'.DIRECTORY_SEPARATOR.'file1'));

    //     $this->filesystem->mirror($sourcePath, $targetPath, null, array('delete' => true));
    //     $this->assertFalse($this->filesystem->exists($targetPath.'directory'.DIRECTORY_SEPARATOR.'file1'));

    //     file_put_contents($file1, 'FILE1');

    //     $this->filesystem->mirror($sourcePath, $targetPath, null, array('delete' => true));
    //     $this->assertTrue($this->filesystem->exists($targetPath.'directory'.DIRECTORY_SEPARATOR.'file1'));

    //     $this->filesystem->remove($directory);
    //     $this->filesystem->mirror($sourcePath, $targetPath, null, array('delete' => true));
    //     $this->assertFalse($this->filesystem->exists($targetPath.'directory'));
    //     $this->assertFalse($this->filesystem->exists($targetPath.'directory'.DIRECTORY_SEPARATOR.'file1'));
    // }

    // public function testMirrorCopiesLinks()
    // {
    //     $this->markAsSkippedIfSymlinkIsMissing();

    //     $sourcePath = $this->workspace.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR;

    //     mkdir($sourcePath);
    //     file_put_contents($sourcePath.'file1', 'FILE1');
    //     symlink($sourcePath.'file1', $sourcePath.'link1');

    //     $targetPath = $this->workspace.DIRECTORY_SEPARATOR.'target'.DIRECTORY_SEPARATOR;

    //     $this->filesystem->mirror($sourcePath, $targetPath);

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

    //     $this->filesystem->mirror($sourcePath, $targetPath);

    //     $this->assertTrue(is_dir($targetPath));
    //     $this->assertFileEquals($sourcePath.'/nested/file1.txt', $targetPath.DIRECTORY_SEPARATOR.'link1/file1.txt');
    //     $this->assertTrue(is_link($targetPath.DIRECTORY_SEPARATOR.'link1'));
    // }

    public function testDumpFile()
    {
        $filename = $this->workspace.DIRECTORY_SEPARATOR.'foo'.DIRECTORY_SEPARATOR.'baz.txt';

        $this->filesystem->dumpFile($filename, 'bar', 0753);

        $this->assertFileExists($filename);
        $this->assertFileContent('bar', $filename);
        //$this->assertEquals(753, $this->getFilePermissions($filename));
    }

    public function testDumpFileOverwritesAnExistingFile()
    {
        $filename = $this->workspace.DIRECTORY_SEPARATOR.'foo.txt';

        $this->gridfs->storeBytes('FOO BAR', array('filename' => $filename, 'type' => 'file'));

        $this->filesystem->dumpFile($filename, 'bar');

        $this->assertFileExists($filename);
        $this->assertFileContent('bar', $filename);
    }

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
