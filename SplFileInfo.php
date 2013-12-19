<?php

namespace Pitpit\Component\MongoFilesystem;

/**
 * SplFileInfo.
 *
 * @author    Damien Pitard <damien.pitard@gmail.com>
 * @copyright 2013 Damien Pitard
 */
class SplFileInfo extends \SplFileInfo
{
    const FOLDER_MIMETYPE = 'directory';

    /**
     * @var MongoId
     */
    protected $fs;

    /**
     * @var string
     */
    protected $infoClass;

    /**
     * @var string
     */
    protected $fileClass;

    /**
     * Memory cache
     *
     * @var array
     */
    protected $cache = array();

    /**
     * Constructor
     *
     * @param string      $pathname The file to read.
     * @param MongoGridFS $fs       A MongoGridFS instance
     */
    public function __construct($pathname, \MongoGridFS $fs = null)
    {
        parent::__construct($pathname);
        $this->setInfoClass();
        $this->setFileClass();
        $this->setGridFS($fs);
    }

    /**
     * Set the related MongoGrid
     * @param [type] $fs [description]
     */
    protected function setGridFS(\MongoGridFS $fs = null)
    {
        $this->fs = $fs;
    }

    /**
     * {@inheritdoc}
     */
    public function setInfoClass($infoClass = null)
    {
        $parent = 'Pitpit\Component\MongoFilesystem\SplFileInfo';
        if (null === $infoClass || $parent === $infoClass) {
            $infoClass = $parent;
        } else {
            $this->checkClass($infoClass, $parent, __METHOD__);
        }
        $this->infoClass = $infoClass;
    }

    /**
     * {@inheritdoc}
     */
    public function setFileClass($fileClass = null)
    {
        $parent = 'Pitpit\Component\MongoFilesystem\SplFileObject';
        if (null === $fileClass || $parent === $fileClass) {
            $fileClass = $parent;
        } else {
            $this->checkClass($fileClass, $parent, __METHOD__);
        }
        $this->fileClass = $fileClass;
    }

    /**
     * {@inheritdoc}
     *
     * It does not resolve symbolic links, because there are not supported for now.
     */
    public function getRealPath()
    {
        if (!$this->exists()) {
            return false;
        }

        $pathname = $this->getResolvedPath();

        return $pathname;
    }

    /**
     * {@inheritdoc}
     *
     * Permissions not supported for now. Fake perms for file are 0666 and directory 01777.
     */
    public function getPerms()
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File "%s" does not exist.', $this->getPathname()));
        }

        if ($this->isDir()) {

            $mode = 01777;
        } else {

            $mode = 0666;
        }

        return $mode;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return $this->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return $this->exists();
    }

    /**
     * {@inheritdoc}
     * File is not executable if it is not a dir.
     */
    public function isExecutable()
    {
        return ($this->isDir())?true:false;
    }

    /**
     * {@inheritdoc}
     */
    public function isDir()
    {
        if (!$this->exists()) {

            return false;
        }

        return (self::FOLDER_MIMETYPE === $this->getDocument()->file['mimeType']);
    }

    /**
     * {@inheritdoc}
     */
    public function isFile()
    {
        if (!$this->exists()) {

            return false;
        }

        return (self::FOLDER_MIMETYPE !== $this->getDocument()->file['mimeType']);
    }

    /**
     * {@inheritdoc}
     * Symlink not supported for now
     */
    public function isLink()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * This function returns the time when the content of the file was changed.
     */
    public function getMTime()
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File "%s" does not exist.', $this->getPathname()));
        }

        return $this->getDocument()->file['uploadDate']->sec;
    }

    /**
     * {@inheritdoc}
     *
     * A file is considered changed when the permissions, owner, group, or other metadata from the file is updated.
     */
    public function getCTime()
    {
        return $this->getMTime();
    }

    /**
     * {@inheritdoc}
     *
     * This feature is disabled because it is costly performance-wise when an application regularly accesses a very large number of files or directories.
     * Will return the mtime instead.
     */
    public function getATime()
    {
        return $this->getMTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkTarget()
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File "%s" does not exist.', $this->getPathname()));
        }

        if (!$this->isLink()) {
            throw new \RuntimeException(sprintf('File "%s" is not a link.', $this->getPathname()));
        }

        throw new \LogicException('Method not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File "%s" does not exist.', $this->getPathname()));
        }

        return $this->isDir()?'dir':'file';
    }

    /**
     * {@inheritdoc}
     */
    public function getMimeType()
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File "%s" does not exist.', $this->getPathname()));
        }

        return $this->getDocument()->file['mimeType'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File "%s" does not exist.', $this->getPathname()));
        }

        return $this->getDocument()->file['length'];
    }

    /**
     * {@inheritdoc}
     *
     * User & group not supported.
     */
    public function getOwner()
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File "%s" does not exist.', $this->getPathname()));
        }

        return 42;
    }

    /**
     * {@inheritdoc}
     *
     * User & group not supported.
     */
    public function getGroup()
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File "%s" does not exist.', $this->getPathname()));
        }

        return 42;
    }

    /**
     * {@inheritdoc}
     *
     * inode not supported.
     */
    public function getInode()
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File "%s" does not exist.', $this->getPathname()));
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getPathInfo($class = null)
    {
        return $this->getInfo($this->getPath(), $class);
    }

    /**
     * {@inheritdoc}
     */
    public function getFileInfo($class = null)
    {
        return $this->getInfo($this->getPathname(), $class);
    }

    /**
     * Check that $className is a subclass of the current class name.
     * If classname is not defined return the current class name..
     *
     * @param string $path  The path
     * @param string $class The class to implement
     *
     * @return string The class to use
     */
    protected function getInfo($path, $class = null)
    {
        if ($class !== $this->infoClass) {
            if (null === $class) {
                $class = $this->infoClass;
            } else {
                $this->checkClass($class, get_class($this), __METHOD__);
            }
        }

        return new $class($path, $this->fs);
    }

    protected function checkClass($class, $parent, $method)
    {
        $reflection = new \ReflectionClass($class);
        if (!$reflection->isSubclassOf($parent)) {
            throw new \UnexpectedValueException(sprintf("%s expects parameter 1 to be a class name derived from %s", $method, $parent));
        }
    }

    /**
     * {@inheritdoc}
     *
     * failed to open stream: No such file or directory
     */
    public function openFile($mode = 'r', $useIncludePath = false, $context = null)
    {
        return new $this->fileClass($this->getPathname(), $mode, $useIncludePath, $context, $this->fs);
    }

    /**
     * Get a file in MongoGridFS
     * The result is cached to save resource.
     *
     * @return \MongoGridFS
     */
    protected function getDocument()
    {
        if (!isset($this->cache['document'])) {
            $this->cache['document'] = $this->fs->findOne(array('filename' => $this->getResolvedPath()));
        }

        return $this->cache['document'];
    }

    /**
     * Does a file exist in MongoGridFS
     * The result is cached to save resource.
     *
     * @return \MongoGridFS
     */
    protected function exists()
    {
        return (null !== $this->getDocument());
    }

    /**
     * Resolve pathname removing .. and . and cache it in memory
     *
     * @return string
     */
    public function getResolvedPath()
    {
        if (!isset($this->cache['resolved_path'])) {
            $pathname = $this->getPathname();
            $parts = explode('/', $pathname);
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

            $this->cache['resolved_path'] = $pathname;
        }

        return $this->cache['resolved_path'];
    }
}