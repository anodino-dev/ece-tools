<?php
/**
 * Origin filesystem driver
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Driver;

use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Class File
 *
 * @package Magento\Framework\Filesystem\Driver
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class File
{
    /**
     * Returns last warning message string
     *
     * @return string
     */
    protected function getWarningMessage()
    {
        $warning = error_get_last();
        if ($warning && $warning['type'] == E_WARNING) {
            return 'Warning!' . $warning['message'];
        }

        return null;
    }

    /**
     * Is file or directory exist in file system
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function isExists($path): bool
    {
        clearstatcache();
        $result = @file_exists($path);
        if ($result === null) {
            $this->fileSystemException('Error occurred during execution %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Gathers the statistics of the given path
     *
     * @param string $path
     * @return array
     * @throws FileSystemException
     */
    public function stat($path)
    {
        clearstatcache();
        $result = @stat($path);
        if (!$result) {
            $this->fileSystemException('Cannot gather stats! %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Check permissions for reading file or directory
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function isReadable($path)
    {
        clearstatcache();
        $result = @is_readable($path);
        if ($result === null) {
            $this->fileSystemException('Error occurred during execution %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Tells whether the filename is a regular file
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function isFile($path): bool
    {
        clearstatcache();
        $result = @is_file($path);
        if ($result === null) {
            $this->fileSystemException('Error occurred during execution %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Tells whether the filename is a regular directory
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function isDirectory($path): bool
    {
        clearstatcache();
        $result = @is_dir($path);
        if ($result === null) {
            $this->fileSystemException('Error occurred during execution %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Retrieve file contents from given path
     *
     * @param string $path
     * @param string|null $flag
     * @param resource|null $context
     * @return string
     * @throws FileSystemException
     */
    public function fileGetContents($path, $flag = null, $context = null)
    {
        clearstatcache();
        $result = @file_get_contents($path, $flag, $context);
        if (false === $result) {
            $this->fileSystemException('Cannot read contents from file "%1" %2', [$path, $this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Parse a configuration file.
     *
     * @param string $path
     * @param bool $processSections
     * @param int $scannerMode
     * @return array|bool
     */
    public function parseIni($path, $processSections = false, $scannerMode = INI_SCANNER_NORMAL)
    {
        clearstatcache();
        $result = @parse_ini_file($path, $processSections, $scannerMode);
        if (false === $result) {
            $this->fileSystemException('Cannot read contents from file "%1" %2', [$path, $this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Check if given path is writable
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function isWritable($path)
    {
        clearstatcache();
        $result = @is_writable($path);
        if ($result === null) {
            $this->fileSystemException('Error occurred during execution %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Returns parent directory's path
     *
     * @param string $path
     * @return string
     */
    public function getParentDirectory($path)
    {
        return dirname($path);
    }

    /**
     * Create directory
     *
     * @param string $path
     * @param int $permissions
     * @return bool
     * @throws FileSystemException
     */
    public function createDirectory($path, $permissions = 0777)
    {
        return $this->mkdirRecursive($path, $permissions);
    }

    /**
     * Create a directory recursively taking into account race conditions
     *
     * @param string $path
     * @param int $permissions
     * @return bool
     * @throws FileSystemException
     */
    private function mkdirRecursive($path, $permissions = 0777)
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        while (!is_dir($parentDir)) {
            $this->mkdirRecursive($parentDir, $permissions);
        }
        $result = @mkdir($path, $permissions);
        if (!$result) {
            if (is_dir($path)) {
                $result = true;
            } else {
                $this->fileSystemException('Directory "%1" cannot be created %2', [$path, $this->getWarningMessage()]);
            }
        }

        return $result;
    }

    /**
     * Read directory
     *
     * @param string $path
     * @return string[]
     * @throws FileSystemException
     */
    public function readDirectory($path)
    {
        try {
            $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
            $iterator = new \FilesystemIterator($path, $flags);
            $result = [];
            /** @var \FilesystemIterator $file */
            foreach ($iterator as $file) {
                $result[] = $file->getPathname();
            }
            sort($result);

            return $result;
        } catch (\Exception $e) {
            throw new FileSystemException($e->getMessage(), $e);
        }
    }

    /**
     * Search paths by given regex
     *
     * @param string $pattern
     * @param string $path
     * @return string[]
     * @throws FileSystemException
     */
    public function search($pattern, $path)
    {
        clearstatcache();
        $globPattern = rtrim($path, '/') . '/' . ltrim($pattern, '/');
        $result = glob($globPattern, GLOB_BRACE);

        return is_array($result) ? $result : [];
    }

    /**
     * Renames a file or directory
     *
     * @param string $oldPath
     * @param string $newPath
     * @return bool
     * @throws FileSystemException
     */
    public function rename($oldPath, $newPath)
    {
        $result = @rename($oldPath, $newPath);
        if (!$result) {
            $this->fileSystemException(
                'The path "%1" cannot be renamed into "%2" %3',
                [$oldPath, $newPath, $this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Copy source into destination
     *
     * @param string $source
     * @param string $destination
     * @return bool
     * @throws FileSystemException
     */
    public function copy($source, $destination)
    {
        $result = @copy($source, $destination);
        if (!$result) {
            $this->fileSystemException(
                'The file or directory "%1" cannot be copied to "%2" %3',
                [
                    $source,
                    $destination,
                    $this->getWarningMessage(),
                ]
            );
        }

        return $result;
    }

    /**
     * Copy directory recursively.
     *
     * @param string $source The path of source folder
     * @param string $destination The path of destination folder
     */
    public function copyDirectory($source, $destination)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var \RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $item) {
            $destinationPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                $this->createDirectory($destinationPath);
            } else {
                $this->copy($item, $destinationPath);
            }
        }
    }

    /**
     * Create symlink on source and place it into destination
     *
     * @param string $source
     * @param string $destination
     * @return bool
     * @throws FileSystemException
     */
    public function symlink($source, $destination)
    {
        $result = @symlink($source, $destination);
        if (!$result) {
            $this->fileSystemException(
                'Cannot create a symlink for "%1" and place it to "%2" %3',
                [
                    $source,
                    $destination,
                    $this->getWarningMessage(),
                ]
            );
        }

        return $result;
    }

    /**
     * Delete file
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function deleteFile($path)
    {
        $result = @unlink($path);
        if (!$result) {
            $this->fileSystemException('The file "%1" cannot be deleted %2', [$path, $this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Recursive delete directory
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function deleteDirectory($path)
    {
        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
        $iterator = new \FilesystemIterator($path, $flags);
        /** @var \FilesystemIterator $entity */
        foreach ($iterator as $entity) {
            if ($entity->isDir()) {
                $this->deleteDirectory($entity->getPathname());
            } else {
                $this->deleteFile($entity->getPathname());
            }
        }
        $result = @rmdir($path);
        if (!$result) {
            $this->fileSystemException(
                'The directory "%1" cannot be deleted %2',
                [$path, $this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Recursive clear directory
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function clearDirectory($path)
    {
        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
        $iterator = new \FilesystemIterator($path, $flags);
        /** @var \FilesystemIterator $entity */
        foreach ($iterator as $entity) {
            if ($entity->isDir()) {
                $this->deleteDirectory($entity->getPathname());
            } else {
                $this->deleteFile($entity->getPathname());
            }
        }

        return true;
    }

    /**
     * Change permissions of given path
     *
     * @param string $path
     * @param int $permissions
     * @return bool
     * @throws FileSystemException
     */
    public function changePermissions($path, $permissions)
    {
        $result = @chmod($path, $permissions);
        if (!$result) {
            $this->fileSystemException(
                'Cannot change permissions for path "%1" %2',
                [$path, $this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Recursively change permissions of given path
     *
     * @param string $path
     * @param int $dirPermissions
     * @param int $filePermissions
     * @return bool
     * @throws FileSystemException
     */
    public function changePermissionsRecursively($path, $dirPermissions, $filePermissions)
    {
        if ($this->isFile($path)) {
            $result = @chmod($path, $filePermissions);
        } else {
            $result = @chmod($path, $dirPermissions);
        }

        if (!$result) {
            $this->fileSystemException(
                'Cannot change permissions for path "%1" %2',
                [$path, $this->getWarningMessage()]
            );
        }

        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, $flags),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        /** @var \FilesystemIterator $entity */
        foreach ($iterator as $entity) {
            if ($entity->isDir()) {
                $result = @chmod($entity->getPathname(), $dirPermissions);
            } else {
                $result = @chmod($entity->getPathname(), $filePermissions);
            }
            if (!$result) {
                $this->fileSystemException(
                    'Cannot change permissions for path "%1" %2',
                    [$path, $this->getWarningMessage()]
                );
            }
        }

        return $result;
    }

    /**
     * Sets access and modification time of file.
     *
     * @param string $path
     * @param int|null $modificationTime
     * @return bool
     * @throws FileSystemException
     */
    public function touch($path, $modificationTime = null)
    {
        if (!$modificationTime) {
            $result = @touch($path);
        } else {
            $result = @touch($path, $modificationTime);
        }
        if (!$result) {
            $this->fileSystemException(
                'The file or directory "%1" cannot be touched %2',
                [$path, $this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Write contents to file in given path
     *
     * @param string $path
     * @param string $content
     * @param string|null $mode
     * @return int The number of bytes that were written.
     * @throws FileSystemException
     */
    public function filePutContents($path, $content, $mode = null)
    {
        $result = @file_put_contents($path, $content, $mode);
        if (!$result) {
            $this->fileSystemException(
                'The specified "%1" file could not be written %2',
                [$path, $this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Open file
     *
     * @param string $path
     * @param string $mode
     * @return resource file
     * @throws FileSystemException
     */
    public function fileOpen($path, $mode)
    {
        $result = @fopen($path, $mode);
        if (!$result) {
            $this->fileSystemException('File "%1" cannot be opened %2', [$path, $this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Reads the line content from file pointer (with specified number of bytes from the current position).
     *
     * @param resource $resource
     * @param int $length
     * @param string $ending [optional]
     * @return string
     * @throws FileSystemException
     */
    public function fileReadLine($resource, $length, $ending = null)
    {
        $result = @stream_get_line($resource, $length, $ending);
        if (false === $result) {
            $this->fileSystemException('File cannot be read %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Reads the specified number of bytes from the current position.
     *
     * @param resource $resource
     * @param int $length
     * @return string
     * @throws FileSystemException
     */
    public function fileRead($resource, $length)
    {
        $result = @fread($resource, $length);
        if ($result === false) {
            $this->fileSystemException('File cannot be read %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Reads one CSV row from the file
     *
     * @param resource $resource
     * @param int $length [optional]
     * @param string $delimiter [optional]
     * @param string $enclosure [optional]
     * @param string $escape [optional]
     * @return array|bool|null
     * @throws FileSystemException
     */
    public function fileGetCsv($resource, $length = 0, $delimiter = ',', $enclosure = '"', $escape = '\\')
    {
        $result = @fgetcsv($resource, $length, $delimiter, $enclosure, $escape);
        if ($result === null) {
            $this->fileSystemException('Wrong CSV handle %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Returns position of read/write pointer
     *
     * @param resource $resource
     * @return int
     * @throws FileSystemException
     */
    public function fileTell($resource)
    {
        $result = @ftell($resource);
        if ($result === null) {
            $this->fileSystemException('Error occurred during execution %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Seeks to the specified offset
     *
     * @param resource $resource
     * @param int $offset
     * @param int $whence
     * @return int
     * @throws FileSystemException
     */
    public function fileSeek($resource, $offset, $whence = SEEK_SET)
    {
        $result = @fseek($resource, $offset, $whence);
        if ($result === -1) {
            $this->fileSystemException('Error occurred during execution of fileSeek %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Returns true if pointer at the end of file or in case of exception
     *
     * @param resource $resource
     * @return boolean
     */
    public function endOfFile($resource)
    {
        return feof($resource);
    }

    /**
     * Close file
     *
     * @param resource $resource
     * @return boolean
     * @throws FileSystemException
     */
    public function fileClose($resource)
    {
        $result = @fclose($resource);
        if (!$result) {
            $this->fileSystemException(
                'Error occurred during execution of fileClose %1',
                [$this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Writes data to file
     *
     * @param resource $resource
     * @param string $data
     * @return int
     * @throws FileSystemException
     */
    public function fileWrite($resource, $data)
    {
        $lenData = strlen($data);
        for ($result = 0; $result < $lenData; $result += $fwrite) {
            $fwrite = @fwrite($resource, substr($data, $result));
            if (0 === $fwrite) {
                $this->fileSystemException('Unable to write');
            }
            if (false === $fwrite) {
                $this->fileSystemException(
                    'Error occurred during execution of fileWrite %1',
                    [$this->getWarningMessage()]
                );
            }
        }

        return $result;
    }

    /**
     * Throw a FileSystemException with a message and optional arguments
     *
     * @param string $message
     * @param array $arguments
     * @return void
     * @throws FileSystemException
     */
    private function fileSystemException($message, $arguments = [])
    {
        if ($arguments) {
            $placeholders = array_map(
                function ($key) {
                    return '%' . (is_int($key) ? strval($key + 1) : $key);
                },
                array_keys($arguments)
            );
            $pairs = array_combine($placeholders, $arguments);
            $message = strtr($message, $pairs);
        }

        throw new FileSystemException($message);
    }

    /**
     * Writes one CSV row to the file.
     *
     * @param resource $resource
     * @param array $data
     * @param string $delimiter
     * @param string $enclosure
     * @return int
     * @throws FileSystemException
     */
    public function filePutCsv($resource, array $data, $delimiter = ',', $enclosure = '"')
    {
        /**
         * Security enhancement for CSV data processing by Excel-like applications.
         *
         * @see https://bugzilla.mozilla.org/show_bug.cgi?id=1054702
         *
         * @var $value string
         */
        foreach ($data as $key => $value) {
            if (!is_string($value)) {
                $value = (string)$value;
            }
            if (isset($value[0]) && in_array($value[0], ['=', '+', '-'])) {
                $data[$key] = ' ' . $value;
            }
        }

        $result = @fputcsv($resource, $data, $delimiter, $enclosure);
        if (!$result) {
            $this->fileSystemException(
                'Error occurred during execution of filePutCsv %1',
                [$this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Flushes the output
     *
     * @param resource $resource
     * @return bool
     * @throws FileSystemException
     */
    public function fileFlush($resource)
    {
        $result = @fflush($resource);
        if (!$result) {
            $this->fileSystemException(
                'Error occurred during execution of fileFlush %1',
                [$this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Lock file in selected mode
     *
     * @param resource $resource
     * @param int $lockMode
     * @return bool
     * @throws FileSystemException
     */
    public function fileLock($resource, $lockMode = LOCK_EX)
    {
        $result = @flock($resource, $lockMode);
        if (!$result) {
            $this->fileSystemException(
                'Error occurred during execution of fileLock %1',
                [$this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Unlock file
     *
     * @param resource $resource
     * @return bool
     * @throws FileSystemException
     */
    public function fileUnlock($resource)
    {
        $result = @flock($resource, LOCK_UN);
        if (!$result) {
            $this->fileSystemException(
                'Error occurred during execution of fileUnlock %1',
                [$this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * @param string $basePath
     * @param string $path
     * @param string|null $scheme
     * @return string
     */
    public function getAbsolutePath($basePath, $path, $scheme = null)
    {
        // check if the path given is already an absolute path containing the
        // basepath. so if the basepath starts at position 0 in the path, we
        // must not concatinate them again because path is already absolute.
        if (0 === strpos($path, $basePath)) {
            return $scheme . $path;
        }

        return $scheme . $basePath . ltrim($this->fixSeparator($path), '/');
    }

    /**
     * Retrieves relative path
     *
     * @param string $basePath
     * @param string $path
     * @return string
     */
    public function getRelativePath($basePath, $path = null)
    {
        $path = $this->fixSeparator($path);
        if (strpos($path, $basePath) === 0 || $basePath == $path . '/') {
            $result = substr($path, strlen($basePath));
        } else {
            $result = $path;
        }

        return $result;
    }

    /**
     * Fixes path separator
     * Utility method.
     *
     * @param string $path
     * @return string
     */
    protected function fixSeparator($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Read directory recursively
     *
     * @param string $path
     * @return string[]
     * @throws FileSystemException
     */
    public function readDirectoryRecursively($path = null)
    {
        $result = [];
        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, $flags),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            /** @var \FilesystemIterator $file */
            foreach ($iterator as $file) {
                $result[] = $file->getPathname();
            }
        } catch (\Exception $e) {
            throw new FileSystemException($e->getMessage(), $e);
        }

        return $result;
    }

    /**
     * Get real path
     *
     * @param string $path
     *
     * @return string|bool
     */
    public function getRealPath($path)
    {
        return realpath($path);
    }

    /**
     * Return correct path for link
     *
     * @param string $path
     * @return mixed
     */
    public function getRealPathSafety($path)
    {
        if (strpos($path, DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) === false) {
            return $path;
        }
        $pathParts = explode(DIRECTORY_SEPARATOR, $path);
        $realPath = [];
        foreach ($pathParts as $pathPart) {
            if ($pathPart == '.') {
                continue;
            }
            if ($pathPart == '..') {
                array_pop($realPath);
                continue;
            }
            $realPath[] = $pathPart;
        }

        return implode(DIRECTORY_SEPARATOR, $realPath);
    }

    public function scanDir(string $path)
    {
        clearstatcache();
        $result = @scandir($path);
        if (false === $result) {
            $this->fileSystemException('Cannot read contents from path "%1" %2', [$path, $this->getWarningMessage()]);
        }

        return $result;
    }
}
