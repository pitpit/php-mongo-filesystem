<?php

namespace Pitpit\Component\MongoFilesystem\Tests;

use Pitpit\Component\MongoFilesystem\Tests\MongoGridTestHelper;
use Pitpit\Component\MongoFilesystem\SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;

class SplFileInfoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $workspace = null;

    /**
     * @var integer
     */
    protected $time;

    /**
     * @var boolean
     */
    protected $legacy;

    /**
     * Get a SplFilInfo
     *
     * @param string $filename The full filepath
     *
     * @return SplFileInfo
     */
    protected function getFile($filename)
    {
        if ($this->legacy) {
            return new \SplFileInfo($filename);
        } else {
            return new SplFileInfo($filename, MongoGridTestHelper::getGridFS());
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
        file_put_contents($this->workspace.'/bar/dummy.txt', 'bar');
        umask($old);

        if (!$this->legacy) {
            MongoGridTestHelper::getGridFS()->storeBytes('', array('uploadDate' => new \MongoDate($this->time), 'filename' => '/', 'mimeType' => SplFileInfo::FOLDER_MIMETYPE));
            MongoGridTestHelper::getGridFS()->storeBytes('', array('uploadDate' => new \MongoDate($this->time), 'filename' => getcwd(), 'mimeType' => SplFileInfo::FOLDER_MIMETYPE));
            MongoGridTestHelper::getGridFS()->storeBytes('', array('uploadDate' => new \MongoDate($this->time), 'filename' => $this->workspace, 'mimeType' => mime_content_type($this->workspace)));
            MongoGridTestHelper::getGridFS()->storeBytes('', array('uploadDate' => new \MongoDate($this->time), 'filename' => $this->workspace.'/bar', 'mimeType' => mime_content_type($this->workspace.'/bar')));
            MongoGridTestHelper::getGridFS()->storeFile($this->workspace.'/foo.txt', array('uploadDate' => new \MongoDate($this->time), 'mimeType' => mime_content_type($this->workspace.'/foo.txt')));
            MongoGridTestHelper::getGridFS()->storeFile($this->workspace.'/bar/foo.txt', array('uploadDate' => new \MongoDate($this->time), 'mimeType' => mime_content_type($this->workspace.'/bar/foo.txt')));
            MongoGridTestHelper::getGridFS()->storeFile($this->workspace.'/bar/dummy.txt', array('uploadDate' => new \MongoDate($this->time), 'mimeType' => mime_content_type($this->workspace.'/bar/dummy.txt')));
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

    public function testGetPathname()
    {
        //file does not need to exists
        $file = $this->getFile('foo.txt');

        $this->assertEquals('foo.txt', $file->getPathname());

        $file = $this->getFile('/foo.txt');

        $this->assertEquals('/foo.txt', $file->getPathname());

        $file = $this->getFile('/bar/');

        $this->assertEquals('/bar', $file->getPathname());
    }

    public function testGetFilename()
    {
        //file does not need to exists

        $file = $this->getFile('foo.txt');

        $this->assertEquals('foo.txt', $file->getFilename());

        $file = $this->getFile('/bar/foo.txt');

        $this->assertEquals('foo.txt', $file->getFilename());

        $file = $this->getFile($this->workspace.'/bar/.');

        $this->assertEquals('.', $file->getFilename());
    }

    public function testGetBasename()
    {
        //file does not need to exists

        $file = $this->getFile('foo.txt');

        $this->assertEquals('txt', $file->getExtension());

        $file = $this->getFile('/bar/foo.txt');

        $this->assertEquals('txt', $file->getExtension());

        $file = $this->getFile('something.tar.gz');

        $this->assertEquals('gz', $file->getExtension());

        $file = $this->getFile('/bar');

        $this->assertEquals('', $file->getExtension());
    }

    public function testGetExtension()
    {
        //file does not need to exists

        $file = $this->getFile('foo.txt');

        $this->assertEquals('foo.txt', $file->getBasename());

        $file = $this->getFile('/bar/foo.txt');

        $this->assertEquals('foo.txt', $file->getBasename());

        $file = $this->getFile('/bar/foo.txt');

        $this->assertEquals('foo', $file->getBasename('.txt'));

        $file = $this->getFile('/bar');

        $this->assertEquals('bar', $file->getBasename());
    }

    public function testGetPath()
    {
        //file does not need to exists

        $file = $this->getFile('foo.txt');

        $this->assertEquals('', $file->getPath());

        $file = $this->getFile('/bar/foo.txt');

        $this->assertEquals('/bar', $file->getPath());

        $file = $this->getFile('/bar/');

        $this->assertEquals('', $file->getPath());

        $file = $this->getFile('/bar');

        $this->assertEquals('', $file->getPath());
    }

    public function testToString()
    {
        //file does not need to exists
        $file = $this->getFile('foo.txt');

        $this->assertEquals('foo.txt',(string)$file);

        $file = $this->getFile('/foo.txt');

        $this->assertEquals('/foo.txt',(string)$file);

        $file = $this->getFile('/bar/foo.txt');

        $this->assertEquals('/bar/foo.txt',(string)$file);
    }


    public function testGetType()
    {
        $file = $this->getFile($this->workspace.'/foo.txt');

        $this->assertEquals('file', $file->getType());

        $file = $this->getFile($this->workspace.'/');

        $this->assertEquals('dir', $file->getType());

        $file = $this->getFile($this->workspace);

        $this->assertEquals('dir', $file->getType());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetTypeFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');
        $file->getType();
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetTypeDirNotFound()
    {
        $file = $this->getFile('/unknown/');
        $file->getType();
    }

    public function testIsDir()
    {
        $file = $this->getFile($this->workspace.'/foo.txt');

        $this->assertFalse($file->isDir());

        $file = $this->getFile($this->workspace);

        $this->assertTrue($file->isDir());
    }

    public function testIsDirNotFound()
    {
        $file = $this->getFile('/unknown.txt');

        $this->assertFalse($file->isDir(), 'false if dir does not exist');

        $file = $this->getFile('/unknown');

        $this->assertFalse($file->isDir(), 'false if dir does not exist');
    }

    public function testIsFile()
    {
        $file = $this->getFile($this->workspace.'/foo.txt');

        $this->assertTrue($file->isFile(), 'false if file does not exist');

        $file = $this->getFile($this->workspace);

        $this->assertFalse($file->isFile(), 'false if file does not exist');
    }

    public function testIsFileFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');

        $this->assertFalse($file->isFile());

        $file = $this->getFile('/unknown');

        $this->assertFalse($file->isFile());
    }

    public function testGetRealPath()
    {
        $file = $this->getFile('/');

        $this->assertEquals('/', $file->getRealPath());

        $file = $this->getFile('.');

        $this->assertEquals(getcwd(), $file->getRealPath());

        $file = $this->getFile('./');

        $this->assertEquals(getcwd(), $file->getRealPath());

        $file = $this->getFile('');

        $this->assertEquals(getcwd(), $file->getRealPath());

        $file = $this->getFile($this->workspace.'/foo.txt');

        $this->assertRegExp('@'.preg_quote($this->workspace, '/').'\/foo.txt$@', $file->getRealPath());

        $file = $this->getFile($this->workspace.'/bar/../foo.txt');

        $this->assertRegExp('@'.preg_quote($this->workspace, '/').'\/foo.txt$@', $file->getRealPath());

        $file = $this->getFile($this->workspace.'/bar/./foo.txt');

        $this->assertRegExp('@'.preg_quote($this->workspace, '/').'\/bar\/foo.txt$@', $file->getRealPath());

        $file = $this->getFile($this->workspace.'/bar/.././foo.txt');

        $this->assertRegExp('@'.preg_quote($this->workspace, '/').'\/foo.txt$@', $file->getRealPath());

        $file = $this->getFile($this->workspace.'/bar/..');

        $this->assertRegExp('@'.preg_quote($this->workspace, '/').'$@', $file->getRealPath());

        $file = $this->getFile($this->workspace.'/bar/.');

        $this->assertRegExp('@'.preg_quote($this->workspace, '/').'\/bar$@', $file->getRealPath());


        $file = $this->getFile($this->workspace.'/bar/');

        $this->assertRegExp('@'.preg_quote($this->workspace, '/').'\/bar$@', $file->getRealPath());
    }

    public function testGetRealPathFileNotFound()
    {
        $file = $this->getFile('unknown.txt');
        $this->assertFalse($file->getRealPath());
    }

    public function testGetSize()
    {
        $file = $this->getFile($this->workspace.'/bar/dummy.txt');

        $this->assertEquals(3, $file->getSize());


        $file = $this->getFile($this->workspace.'/bar/foo.txt');

        $this->assertEquals(0, $file->getSize());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetSizeFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');


        $this->assertEquals(3, $file->getSize());
    }

    public function testGetMimeType()
    {
        if ($this->legacy) {
            $this->markTestIncomplete('Not supported by \SplInfoFile.');
        }

        $file = $this->getFile($this->workspace.'/bar/dummy.txt');

        $this->assertEquals('text/plain', $file->getMimeType());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetMimeTypeFileNotFound()
    {
        if ($this->legacy) {
            $this->markTestIncomplete('Not supported by \SplInfoFile.');
        }

        $file = $this->getFile('/unknown.txt');
        $file->getMimeType();
    }

    public function testGetIsReadable()
    {
        $file = $this->getFile($this->workspace.'/bar/dummy.txt');

        $this->assertTrue($file->isReadable(), 'always readable (no perms support)');
    }

    public function testGetIsReadableFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');

        $this->assertFalse($file->isReadable());
    }

    public function testGetIsWritable()
    {
        $file = $this->getFile($this->workspace.'/bar/dummy.txt');

        $this->assertTrue($file->isWritable(), 'always writable (no perms support)');
    }

    public function testGetIsWritableFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');

        $this->assertFalse($file->isWritable());
    }

    public function testIsExecutable()
    {
        $file = $this->getFile($this->workspace.'/bar/dummy.txt');

        $this->assertFalse($file->isExecutable(), 'file never executable (no perms support)');

        $file = $this->getFile($this->workspace.'/bar');

        $this->assertTrue($file->isExecutable());
    }

    public function testIsLink()
    {
        $file = $this->getFile($this->workspace.'/bar/dummy.txt');

        $this->assertFalse($file->isLink(), 'always false (no symlink support)');
    }

    public function testGetPerms()
    {
        $file = $this->getFile($this->workspace.'/bar/dummy.txt');

        $this->assertEquals('0666', substr(sprintf('%o', $file->getPerms()), -4));

        $file = $this->getFile($this->workspace.'/bar');

        $this->assertEquals('1777', substr(sprintf('%o', $file->getPerms()), -4));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetPermsFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');
        $file->getPerms();
    }

    public function testGetMTime()
    {
        $file = $this->getFile($this->workspace.'/foo.txt');
        $this->assertEquals($this->time, $file->getMtime());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetMTimeFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');
        $file->getMtime();
    }

    public function testGetCTime()
    {
        $file = $this->getFile($this->workspace.'/foo.txt');
        $this->assertEquals($this->time, $file->getCtime());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetCTimeFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');
        $file->getCtime();
    }

    public function testGetATime()
    {
        $file = $this->getFile($this->workspace.'/foo.txt');
        $this->assertEquals($this->time, $file->getAtime());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetATimeFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');
        $file->getAtime();
    }

    public function testGetLinkTarget()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetLinkTargetNotASymlink()
    {
        $file = $this->getFile($this->workspace.'/foo.txt');
        $file->getLinkTarget();
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetLinkTargetFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');
        $file->getLinkTarget();
    }

    public function testGetGroup()
    {
        $file = $this->getFile($this->workspace.'/bar/foo.txt');
        $this->assertInternalType('integer', $file->getGroup());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetGroupFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');
        $file->getGroup();
    }

    public function testGetOwner()
    {
        $file = $this->getFile($this->workspace.'/bar/foo.txt');
        $this->assertInternalType('integer', $file->getOwner());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetOwnerFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');
        $file->getOwner();
    }

    public function testGetInode()
    {
        $file = $this->getFile($this->workspace.'/bar/foo.txt');
        $this->assertInternalType('integer', $file->getInode());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetInodeFileNotFound()
    {
        $file = $this->getFile('/unknown.txt');
        $file->getInode();
    }

    public function testGetFileInfo()
    {
        $file = $this->getFile($this->workspace.'/bar/foo.txt');
        $file2 = $file->getFileInfo();
        $this->assertInstanceOf(get_class($file), $file2);
        $this->assertEquals($this->workspace.'/bar/foo.txt', $file2->getPathname());
    }

    /**
     * @expectedException UnexpectedValueException
     *
     * SplFileInfo::getFileInfo() expects parameter 1 to be a class name derived from SplFileInfo,
     */
    public function testGetFileInfoClassNotDerived()
    {
        $file = $this->getFile($this->workspace.'/bar/foo.txt');
        $file->getFileInfo('StdClass');
    }

    public function testGetPathInfo()
    {
        $file = $this->getFile($this->workspace.'/bar/foo.txt');
        $dir = $file->getPathInfo();

        $this->assertInstanceOf(get_class($file), $dir);
        $this->assertEquals($this->workspace.'/bar', $dir->getPathname());
    }

    /**
     * @expectedException UnexpectedValueException
     *
     * SplFileInfo::getFileInfo() expects parameter 1 to be a class name derived from SplFileInfo,
     * SplFileInfo::getPathInfo() expects parameter 1 to be a class name derived from \SplFileInfo,
     */
    public function testGetPathInfoClassNotDerived()
    {
        $file = $this->getFile($this->workspace.'/bar/foo.txt');
        $file->getPathInfo('StdClass');
    }

    public function testGetPathInfoClassNotDerived2()
    {
        $file = $this->getFile($this->workspace.'/bar/foo.txt');
        $file->getPathInfo(get_class($file));
    }

    public function testSetFileClass()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetInfoClass()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
