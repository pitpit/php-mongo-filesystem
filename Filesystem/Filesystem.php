<?php

namespace Pitpit\Component\MongoFilesystem\Filesystem;

use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;
use Symfony\Component\Filesystem\Exception\IOException;


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
            $document = $this->getDocument($file);
            if (!$document) {
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
            foreach ($this->toIterator($files) as $file) {
                $metadata = array('filename' => $file, 'mimeType' => 'application/octet-stream');
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
            $files[$key] = $this->getResolvedPath($file);
        }

        return $files;
    }

    /**
     * Get a file in MongoGridFS
     * The result is cached in memory.
     *
     * @param string $pathname The full path filename
     *
     * @return \MongoGridFS
     */
    protected function getDocument($filename)
    {
        $filename = $this->getResolvedPath($filename);
        if (!isset($this->cache['document'][$filename])) {
            $this->cache['document'][$filename] = $this->fs->findOne(array('filename' => $filename));
        }

        return $this->cache['document'][$filename];
    }

    /**
     * Resolve pathname removing .. and . and cache it in memory
     *
     * @param string $pathname The full path filename
     *
     * @return string
     */
    protected function getResolvedPath($filename)
    {
        if (!isset($this->cache['resolved_path'][$filename])) {
            $parts = explode('/', $filename);
            $parents = array();
            foreach ($parts as $dir) {
                switch($dir) {
                    case '.':
                        break;
                    case '..':
                        array_pop($parents);
                        break;
                    default:
                        $parents[] = $dir;
                        break;
                }
            }

            $pathname = implode('/', $parents);
            if ('' === $pathname) {
                $pathname = getcwd();
            }

            $this->cache['resolved_path'][$filename] = $pathname;
        }

        return $this->cache['resolved_path'][$filename];
    }
}