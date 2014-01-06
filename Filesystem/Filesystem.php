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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
            $originDocument = $originFileInfo->getDocument();
            $targetDocument = $targetFileInfo->getDocument();
            $this->fs->storeBytes($originDocument->getBytes(), array('filename' => $targetFileInfo->getResolvedPath(), 'type' => 'file'));
            if ($targetDocument) {
                $this->fs->remove(array('_id' => $targetDocument->file['_id']));
            }
        }
    }

    /**
     * Removes files or directories.
     *
     * @param string|array|\Traversable $files A filename, an array of files, or a \Traversable instance to remove
     *
     * @throws IOException When removal fails
     */
    public function remove($files)
    {
        $files = iterator_to_array($this->toIterator($files));
        $files = array_reverse($files);
        $n = 0;
        foreach ($files as $file) {
            $result = $this->fs->remove(array('filename' => new \MongoRegex('/^'.preg_quote(rtrim($file, '/'), '/').'/')));
            $n += $result['n'];
        }

        if ($n < 1) {
            throw new IOException(sprintf('Failed to remove directory %s', $file));
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
}