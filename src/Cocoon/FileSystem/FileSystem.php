<?php
declare(strict_types=1);

namespace Cocoon\FileSystem;

use League\Flysystem\FilesystemException;
use League\Flysystem\Filesystem as BaseFileSystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class FileSystem extends BaseFileSystem
{
    public function __construct(string $path)
    {
        $normalizedPath = $this->normalizePath($path);
        parent::__construct(new LocalFilesystemAdapter($normalizedPath));
    }

    /**
     * Normalise le chemin pour éviter les problèmes de séparateurs
     */
    private function normalizePath(string $path): string
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param string $path Chemin du fichier
     * @param int $ttl Temps d'expiration en seconde
     * @return bool
     * @throws FilesystemException
     */
    public function hasAndIsExpired(string $path, int $ttl): bool
    {
        $expire = time() - $ttl;
        if ($this->has($path) && $this->lastModified($path) > $expire) {
            return true;
        }
        return false;
    }

    /**
     * Écrit du contenu dans un fichier
     *
     * @param string $path Chemin relatif du fichier (ex: cache/file.php)
     * @param string $contents Contenu à écrire
     * @return void
     * @throws FilesystemException
     */
    public function put(string $path, string $contents): void
    {
        $this->write($path, $contents);
    }

    /**
     * Lit le contenu d'un fichier
     *
     * @param string $path Chemin relatif du fichier (ex: cache/file.php)
     * @return string Contenu du fichier
     * @throws FilesystemException
     */
    public function read(string $path): string
    {
        return parent::read($path);
    }
}
