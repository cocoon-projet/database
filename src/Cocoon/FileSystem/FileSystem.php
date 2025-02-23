<?php

namespace Cocoon\FileSystem;

use League\Flysystem\Filesystem as BaseFileSystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class FileSystem extends BaseFileSystem
{
    public function __construct($path)
    {
        parent::__construct(new LocalFilesystemAdapter($path));
    }

    /**
     * @param string $path Chemin du fichier
     * @param int $ttl Temps d'expiration en seconde
     * @return bool
     * @throws League\Flysystem\FilesystemException
     */
    public function hasAndIsExpired($path, $ttl) :bool
    {
        $expire = time() - $ttl;
        if ($this->has($path) && $this->getTimestamp($path) > $expire) {
            return true;
        }
        return false;
    }
}
