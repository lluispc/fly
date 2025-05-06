<?php

namespace App\Adapter\Perpetual;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\DirectoryAttributes;

/**
 * Custom filesystem adapter for Perpetual storage
 */
class PerpetualAdapter implements FilesystemAdapter
{
    /**
     * @var string
     */
    private $basePath;
    
    /**
     * @var AutonomiBridge|null
     */
    private $autonomiBridge;
    
    /**
     * @var bool
     */
    private $useAutonomiForDirectories;

    /**
     * Constructor
     *
     * @param string $basePath Base storage path
     * @param bool $useAutonomiForDirectories Whether to use Autonomi for directory operations
     * @param string|null $autonomiApiUrl URL for the Autonomi API (default: http://localhost:8000)
     */
    public function __construct(
        string $basePath, 
        bool $useAutonomiForDirectories = false,
        ?string $autonomiApiUrl = null
    ) {
        $this->basePath = rtrim($basePath, '/') . '/';
        $this->useAutonomiForDirectories = $useAutonomiForDirectories;
        
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
        
        if ($useAutonomiForDirectories) {
            $this->autonomiBridge = new AutonomiBridge($autonomiApiUrl ?? 'http://localhost:8000');
        }
    }

    /**
     * Get the full path for a file
     *
     * @param string $path
     * @return string
     */
    private function getFullPath(string $path): string
    {
        return $this->basePath . ltrim($path, '/');
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        $location = $this->getFullPath($path);
        
        try {
            return is_file($location);
        } catch (\Exception $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function directoryExists(string $path): bool
    {
        $location = $this->getFullPath($path);
        
        try {
            return is_dir($location);
        } catch (\Exception $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $location = $this->getFullPath($path);
        $directory = dirname($location);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        try {
            if (file_put_contents($location, $contents) === false) {
                throw new \Exception("Unable to write file at {$path}");
            }
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $location = $this->getFullPath($path);
        $directory = dirname($location);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        try {
            $stream = fopen($location, 'w+b');
            
            if (!$stream) {
                throw new \Exception("Unable to open file for writing at {$path}");
            }
            
            stream_copy_to_stream($contents, $stream);
            fclose($stream);
            
            if (is_resource($contents)) {
                fclose($contents);
            }
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function read(string $path): string
    {
        $location = $this->getFullPath($path);
        
        try {
            if (!is_file($location)) {
                throw new \Exception("File does not exist at {$path}");
            }
            
            $contents = file_get_contents($location);
            
            if ($contents === false) {
                throw new \Exception("Unable to read file at {$path}");
            }
            
            return $contents;
        } catch (\Exception $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function readStream(string $path)
    {
        $location = $this->getFullPath($path);
        
        try {
            if (!is_file($location)) {
                throw new \Exception("File does not exist at {$path}");
            }
            
            $stream = fopen($location, 'rb');
            
            if (!$stream) {
                throw new \Exception("Unable to open file for reading at {$path}");
            }
            
            return $stream;
        } catch (\Exception $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(string $path): void
    {
        $location = $this->getFullPath($path);
        
        try {
            if (!is_file($location)) {
                return;
            }
            
            if (!unlink($location)) {
                throw new \Exception("Unable to delete file at {$path}");
            }
        } catch (\Exception $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteDirectory(string $path): void
    {
        $location = $this->getFullPath($path);
        
        if (!is_dir($location)) {
            return;
        }
        
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($location, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            
            if (!rmdir($location)) {
                throw new \Exception("Unable to delete directory at {$path}");
            }
        } catch (\Exception $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function createDirectory(string $path, Config $config): void
    {
        $location = $this->getFullPath($path);
        
        try {
            if (is_dir($location)) {
                return;
            }
            
            if (!mkdir($location, 0755, true)) {
                throw new \Exception("Unable to create directory at {$path}");
            }
        } catch (\Exception $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function setVisibility(string $path, string $visibility): void
    {
        $location = $this->getFullPath($path);
        
        try {
            if (!is_file($location)) {
                throw new \Exception("File does not exist at {$path}");
            }
            
            $permissions = $visibility === 'public' ? 0644 : 0600;
            
            if (!chmod($location, $permissions)) {
                throw new \Exception("Unable to set visibility for file at {$path}");
            }
        } catch (\Exception $e) {
            throw UnableToSetVisibility::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function visibility(string $path): FileAttributes
    {
        $location = $this->getFullPath($path);
        
        try {
            if (!is_file($location)) {
                throw new \Exception("File does not exist at {$path}");
            }
            
            $permissions = fileperms($location) & 0777;
            $visibility = $permissions & 0044 ? 'public' : 'private';
            
            return new FileAttributes($path, null, $visibility);
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::visibility($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function mimeType(string $path): FileAttributes
    {
        $location = $this->getFullPath($path);
        
        try {
            if (!is_file($location)) {
                throw new \Exception("File does not exist at {$path}");
            }
            
            $mimeType = mime_content_type($location);
            
            if ($mimeType === false) {
                throw new \Exception("Unable to determine MIME type for file at {$path}");
            }
            
            return new FileAttributes($path, null, null, null, $mimeType);
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function lastModified(string $path): FileAttributes
    {
        $location = $this->getFullPath($path);
        
        try {
            if (!is_file($location)) {
                throw new \Exception("File does not exist at {$path}");
            }
            
            $timestamp = filemtime($location);
            
            if ($timestamp === false) {
                throw new \Exception("Unable to determine last modified time for file at {$path}");
            }
            
            return new FileAttributes($path, null, null, $timestamp);
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function fileSize(string $path): FileAttributes
    {
        $location = $this->getFullPath($path);
        
        try {
            if (!is_file($location)) {
                throw new \Exception("File does not exist at {$path}");
            }
            
            $size = filesize($location);
            
            if ($size === false) {
                throw new \Exception("Unable to determine size for file at {$path}");
            }
            
            return new FileAttributes($path, $size);
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $location = $this->getFullPath($path);
        
        if (!is_dir($location)) {
            return;
        }
        
        try {
            if ($deep) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($location, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
            } else {
                $iterator = new \DirectoryIterator($location);
            }
            
            foreach ($iterator as $item) {
                // Skip dots for DirectoryIterator (RecursiveDirectoryIterator already has SKIP_DOTS flag)
                if (!$deep && ($item->getFilename() === '.' || $item->getFilename() === '..')) {
                    continue;
                }
                
                $itemPath = str_replace($this->basePath, '', $item->getPathname());
                
                if ($item->isDir()) {
                    yield new DirectoryAttributes($itemPath);
                } else {
                    yield new FileAttributes(
                        $itemPath,
                        $item->getSize(),
                        null,
                        $item->getMTime()
                    );
                }
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Unable to list contents at {$path}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function move(string $source, string $destination, Config $config): void
    {
        $sourcePath = $this->getFullPath($source);
        $destinationPath = $this->getFullPath($destination);
        
        $this->ensureDirectoryExists(dirname($destinationPath));
        
        try {
            if (!is_file($sourcePath)) {
                throw new \Exception("File does not exist at {$source}");
            }
            
            if (!rename($sourcePath, $destinationPath)) {
                throw new \Exception("Unable to move file from {$source} to {$destination}");
            }
        } catch (\Exception $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        $sourcePath = $this->getFullPath($source);
        $destinationPath = $this->getFullPath($destination);
        
        $this->ensureDirectoryExists(dirname($destinationPath));
        
        try {
            if (!is_file($sourcePath)) {
                throw new \Exception("File does not exist at {$source}");
            }
            
            if (!copy($sourcePath, $destinationPath)) {
                throw new \Exception("Unable to copy file from {$source} to {$destination}");
            }
        } catch (\Exception $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }
    
    /**
     * Ensure directory exists
     *
     * @param string $dirname
     * @return void
     */
    private function ensureDirectoryExists(string $dirname): void
    {
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }
    }
    
    /**
     * Upload a directory to Autonomi network
     * 
     * @param string $path Local directory path relative to basePath
     * @param bool $isPublic Whether to make it public
     * 
     * @return array Result with operation status, cost and access details
     * @throws \Exception If Autonomi integration is not enabled or API error occurs
     */
    public function uploadDirectoryToAutonomi(string $path, bool $isPublic = false): array
    {
        if (!$this->useAutonomiForDirectories || !$this->autonomiBridge) {
            throw new \Exception('Autonomi integration for directories is not enabled');
        }
        
        $fullPath = $this->getFullPath($path);
        
        if (!is_dir($fullPath)) {
            throw new \Exception("Directory not found: {$fullPath}");
        }
        
        return $this->autonomiBridge->uploadDirectory($fullPath, $isPublic);
    }
    
    /**
     * Download a directory from Autonomi network
     * 
     * @param string $path Local directory path relative to basePath where to save
     * @param string|null $dataMap For private directories, the data map string
     * @param string|null $publicAddress For public directories, the public address
     * 
     * @return array Result with operation status
     * @throws \Exception If Autonomi integration is not enabled or API error occurs
     */
    public function downloadDirectoryFromAutonomi(string $path, ?string $dataMap = null, ?string $publicAddress = null): array
    {
        if (!$this->useAutonomiForDirectories || !$this->autonomiBridge) {
            throw new \Exception('Autonomi integration for directories is not enabled');
        }
        
        $fullPath = $this->getFullPath($path);
        
        return $this->autonomiBridge->downloadDirectory($fullPath, $dataMap, $publicAddress);
    }
    
    /**
     * Get transactions history for directory operations
     * 
     * @param string|null $date Optional date filter in YYYY-MM-DD format
     * @param string|null $operationType Filter by operation type: 'upload' or 'download'
     * 
     * @return array List of directory operations
     * @throws \Exception If Autonomi integration is not enabled or API error occurs
     */
    public function getDirectoryTransactions(?string $date = null, ?string $operationType = null): array
    {
        if (!$this->useAutonomiForDirectories || !$this->autonomiBridge) {
            throw new \Exception('Autonomi integration for directories is not enabled');
        }
        
        return $this->autonomiBridge->getDirectoryTransactions($date, $operationType);
    }
    
    /**
     * Get statistics for directory operations
     * 
     * @param int $days Number of days to include in statistics
     * 
     * @return array Statistics data
     * @throws \Exception If Autonomi integration is not enabled or API error occurs
     */
    public function getDirectoryStats(int $days = 30): array
    {
        if (!$this->useAutonomiForDirectories || !$this->autonomiBridge) {
            throw new \Exception('Autonomi integration for directories is not enabled');
        }
        
        return $this->autonomiBridge->getDirectoryStats($days);
    }
} 