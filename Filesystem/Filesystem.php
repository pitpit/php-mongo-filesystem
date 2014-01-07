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

            try {
                $this->fs->storeBytes('', $metadata);
            } catch (\MongoCursorException $e) {
                throw new IOException(sprintf('Failed to touch %s', $file));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir($dirs, $mode = 0777)
    {
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


            try {
                $this->fs->storeBytes('', array('filename' => $file->getResolvedPath(), 'type' => 'dir'));
            } catch (\MongoCursorException $e) {
                throw new IOException(sprintf('Failed to create %s', $dir));
            }
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
            try {
                $this->fs->storeBytes($originDocument->getBytes(), array('filename' => $targetFileInfo->getResolvedPath(), 'type' => 'file'));
            } catch (\MongoCursorException $e) {
                throw new IOException(sprintf('Failed to copy %s to %s', $originFile, $targetFile));
            }
            if ($targetDocument) {
                $this->fs->remove(array('_id' => $targetDocument->file['_id']));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($files)
    {
        $files = iterator_to_array($this->toIterator($files));
        $files = array_reverse($files);
        $n = 0;
        foreach ($files as $file) {
            $fileInfo = new SplFileInfo($file, $this->fs);
            if (!$fileInfo->isWritable() && !$fileInfo->isLink()) {
                continue;
            }

            $pattern = '/^'.preg_quote(rtrim($file, '/'), '/').'/';
            $result = $this->fs->remove(array('filename' => new \MongoRegex($pattern)));
            // $n += $result['n'];

            if ($result['n'] < 1) {
                throw new IOException(sprintf('Failed to remove file or directory %s', $file));
            }
        }

        // if ($n < 1) {
        //     throw new IOException(sprintf('Failed to remove directory %s', $file));
        // }
    }

    /**
     * {@inheritdoc}
     */
    public function chmod($files, $mode, $umask = 0000, $recursive = false)
    {
        throw new \Exception('Not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function chown($files, $user, $recursive = false)
    {
        throw new \Exception('Not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function chgrp($files, $group, $recursive = false)
    {
        throw new \Exception('Not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function rename($origin, $target, $overwrite = false)
    {
        //we check that target does not exist
        $targetFileInfo = new SplFileInfo($target, $this->fs);
        if ($targetFileInfo->isReadable()) {
            if ($overwrite) {
                $this->remove($target);
            } else {
                throw new IOException(sprintf('Cannot rename because the target "%s" already exist.', $target));
            }
        }

        $pattern = '/^'.preg_quote(rtrim($origin, '/'), '/').'/';
        $files = $this->fs->find(array('filename' => new \MongoRegex($pattern)));

        $n = 0;
        foreach ($files as $file) {
            $result = $this->fs->update(array(
                '_id' => $file->file['_id']
            ), array(
                '$set' => array('filename' => preg_replace($pattern, $target, $file->file['filename']))
            ));
            $n += $result['n'];
        }

        if ($n < 1) {
            throw new IOException(sprintf('Cannot rename "%s" to "%s".', $origin, $target));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function symlink($originDir, $targetDir, $copyOnWindows = false)
    {
        throw new \Exception('Not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function mirror($originDir, $targetDir, \Traversable $iterator = null, $options = array())
    {
        throw new \Exception('Not supported.');
    }

    /**
     * Atomically dumps content into a file.
     *
     * @param  string  $filename The file to be written to.
     * @param  string  $content  The data to write into the file.
     * @param  integer $mode     The file mode (octal).
     * @throws IOException       If the file cannot be written to.
     */
    public function dumpFile($filename, $content, $mode = 0666)
    {
        $dir = dirname($filename);
        $dirFileInfo = new SplFileInfo($dir, $this->fs);

        if (!$dirFileInfo->isDir()) {
            $this->mkdir($dir);
        } elseif (!$dirFileInfo->isWritable()) {
            throw new IOException(sprintf('Unable to write in the %s directory\n', $dir));
        }

        $this->remove($filename);

        try {
            $this->fs->storeBytes($content, array('filename' => $filename, 'type' => 'file'));
        } catch (\MongoCursorException $e) {
            throw new IOException(sprintf('Failed to write file "%s".', $filename));
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

        return $files;
    }
}