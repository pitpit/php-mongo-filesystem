<?php

namespace Pitpit\Component\MongoFilesystem;

/**
 * SplFileObject.
 *
 * @author    Damien Pitard <damien.pitard@gmail.com>
 * @copyright 2013 Damien Pitard
 */
class SplFileObject extends SplFileInfo
{
    protected $stream;

    /**
     * Constructor
     *
     * @param string      $pathname       The file to read.
     * @param string      $mode           The mode in which to open the file. See fopen() for a list of allowed modes.
     * @param bool        $useIncludePath Whether to search in the include_path for $pathname.
     * @param resource    $context        A valid context resource created with stream_context_create().
     * @param MongoGridFS $fs             A MongoGridFS instance
     */
    public function __construct($pathname, $mode = "r", $useIncludePath = false, $context = null, \MongoGridFS $fs = null)
    {
        parent::__construct($pathname, $fs);


        if ('r' === $mode || 'r+' === $mode) {
            if (!$this->exists()) {
                throw new \RuntimeException(sprintf('%s(%s): failed to open stream: No such file or directory', __METHOD__, $this->getPathname()));
            }
        } else if ('w' === $mode || 'w+' === $mode) {
            if (!$this->exists()) {
                // If the file does not exist, attempt to create it.
                $this->fs->storeBytes('', array('filename' => $this->getResolvedPath()));
            } else {
                //truncate the file to zero length
                $file = $this->getDocument();
                $this->fs->storeBytes('', array('_id' => $this->file->file['_id']));

                // $this->fs->put($filename);
                //$this->fs->storeBytes('', array('_id' => $this->file->file['_id']));
                // $this->fs->chunks->update(array('files_id' => $this->file->file['_id']), array('data'));
            }
        } else if ('a' === $mode || 'a+' === $mode) {
            if (!$this->exists()) {
                // If the file does not exist, attempt to create it.
                $this->fs->storeBytes('', array('filename' => $this->getResolvedPath()));
            }
        }

        $this->stream = $this->getDocument()->getResource();
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return feof($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function fgets()
    {
        return fgets($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function fwrite()
    {

    }
}