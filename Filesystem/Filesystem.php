<?php

namespace Pitpit\Component\MongoFilesystem\Filesystem;

use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Pitpit\Component\MongoFilesystem\SplFileInfo;

/**
 * Queries files from MongoDB
 *
 * @author    Damien Pitard <damien.pitard@gmail.com>
 * @copyright 2013 Damien Pitard
 */
class Filesystem extends BaseFilesystem
{
    /**
     * @var MongoId
     */
    protected $fs;

    /**
     * Memory cache
     *
     * @var array
     */
    protected $cache = array();

    /**
     * Constructor
     *
     * @param MongoGridFS $fs A MongoGridFS instance
     */
    public function __construct(\MongoGridFS $fs)
    {
        $this->fs = $fs;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($files)
    {
        foreach ($this->toIterator($files) as $file) {
            $file = new SplFileInfo($file, $this->fs);
            if (!$file->isFile() && !$file->isDir() && !$file->isLink()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sets access and modification time of file.
     *
     * @param string|array|\Traversable $files A filename, an array of files, or a \Traversable instance to create
     * @param integer                   $time  The touch time as a unix timestamp
     * @param integer                   $atime The access time as a unix timestamp
     *
     * @throws IOException When touch fails
     */
    public function touch($files, $time = null, $atime = null)
    {
        try {
            foreach ($this->toIterator($files) as $filepath) {
                $parent = new SplFileInfo(dirname($filepath), $this->fs);
                if (!$parent->isDir()) {
                    throw new IOException(sprintf('Failed to touch %s. Parent directory does not exist.', $filepath));
                }

                $file = new SplFileInfo($filepath, $this->fs);
                $metadata = array('filename' => $file->getResolvedPath(), 'type' => 'file');
                if ($time) {
                    $metadata['uploadDate'] = new \MongoDate($time);
                }
                $this->fs->storeBytes('', $metadata);
            }
        } catch (\MongoCursorException $e) {
            throw new IOException(sprintf('Failed to touch %s', $file));
        }
    }

    /**
     * Creates a directory recursively.
     *
     * @param string|array|\Traversable $dirs The directory path
     * @param integer                   $mode The directory mode
     *
     * @throws IOException On any directory creation failure
     */
    public function mkdir($dirs, $mode = 0777)
    {
        try {
            foreach ($this->toIterator($dirs) as $dir) {
                $file = new SplFileInfo($dir, $this->fs);
                if ($file->isDir()) {
                    continue;
                } else if ($file->isFile() || $file->isLink()) {
                    throw new IOException(sprintf('Failed to create %s', $dir));
                }

                $parent = new SplFileInfo(dirname($dir), $this->fs);
                if (!$parent->isDir()) {
                    $this->mkdir(dirname($dir), $mode);
                }

                $this->fs->storeBytes('', array('filename' => $file->getResolvedPath(), 'type' => 'dir'));
            }
        } catch (\MongoCursorException $e) {
            throw new IOException(sprintf('Failed to create %s', $dir));
        }
    }

    /**
     * Copies a file.
     *
     * This method only copies the file if the origin file is newer than the target file.
     *
     * By default, if the target already exists, it is not overridden.
     *
     * @param string  $originFile The original filename
     * @param string  $targetFile The target filename
     * @param boolean $override   Whether to override an existing file or not
     *
     * @throws IOException When copy fails
     */
    public function copy($originFile, $targetFile, $override = false)
    {
        $originFileInfo = new SplFileInfo($originFile, $this->fs);
        $targetFileInfo = new SplFileInfo($targetFile, $this->fs);

        if (stream_is_local($originFile) && !$originFileInfo->isFile()) {
            throw new IOException(sprintf('Failed to copy %s because file not exists', $originFile));
        }

        $this->mkdir(dirname($targetFile));
        if (!$override && $targetFileInfo->isFile()) {
            $doCopy = $originFileInfo->getMTime() > $targetFileInfo->getMTime();
        } else {
            $doCopy = true;
        }

        if ($doCopy) {

            $originFileInfoObject = $originFileInfo->openFile('r');
            $targetFileInfoObject = $targetFileInfo->openFile('w+');
            while (!$originFileInfoObject->eof()) {
                $targetFileInfoObject->fwrite($originFileInfoObject->fgets());
            }

            // https://bugs.php.net/bug.php?id=64634
            // $source = fopen($originFile, 'r');
            // $target = fopen($targetFile, 'w+');
            // stream_copy_to_stream($originFileInfoObject, $targetFileInfoObject);
            // fclose($source);
            // fclose($target);
            // unset($source, $target);

            if ($targetFileInfo->isFile()) {
                throw new IOException(sprintf('Failed to copy %s to %s', $originFile, $targetFile));
            }
        }
    }

    /**
     * @param mixed $files
     *
     * @return \Traversable
     */
    protected function toIterator($files)
    {
        if (!$files instanceof \Traversable) {
            $files = new \ArrayObject(is_array($files) ? $files : array($files));
        }

        foreach ($files as $key => $file) {
            $files[$key] =  $file;//$this->getResolvedPath($file);
        }

        return $files;
    }

    // /**
    //  * Get a file in MongoGridFS
    //  * The result is cached in memory.
    //  *
    //  * @param string $filepath The full path filename
    //  *
    //  * @return \MongoGridFS
    //  */
    // protected function getDocument($filepath)
    // {
    //     $filepath = $this->getResolvedPath($filepath);
    //     if (!isset($this->cache['document'][$filepath])) {
    //         $this->cache['document'][$filepath] = $this->fs->findOne(array('filename' => $filepath));
    //     }

    //     return $this->cache['document'][$filepath];
    // }

    // /**
    //  * Resolve pathname removing .. and . and cache it in memory
    //  *
    //  * @param string $filepath The full path filename
    //  *
    //  * @return string
    //  */
    // protected function getResolvedPath($filepath)
    // {
    //     if (!isset($this->cache['resolved_path'][$filepath])) {
    //         $parts = explode('/', $filepath);
    //         $parents = array();
    //         foreach ($parts as $dir) {
    //             switch($dir) {
    //                 case '.':
    //                     break;
    //                 case '..':
    //                     array_pop($parents);
    //                     break;
    //                 default:
    //                     $parents[] = $dir;
    //                     break;
    //             }
    //         }

    //         $pathname = implode('/', $parents);
    //         if ('' === $pathname) {
    //             $pathname = getcwd();
    //         }

    //         $this->cache['resolved_path'][$filepath] = $pathname;
    //     }

    //     return $this->cache['resolved_path'][$filepath];
    // }
}