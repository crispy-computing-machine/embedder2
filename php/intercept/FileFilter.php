<?php

namespace Embeder;

class FileFilter {

    /**
     * @var string[]
     */
    private $whiteList = [];

    /**
     * @var string[]
     */
    private $blackList = [];

    /**
     * @var string[]|null Null indicates that all extensions are allowed.
     */
    private $allowedExtensions;

    /**
     * @var string[]|null Null indicates that all extensions are disallowed.
     */
    private $disallowedExtensions;

    /**
     * @return FileFilter
     */
    public static function createAllWhitelisted(): FileFilter {
        $filter = new self();
        $filter->addWhiteList('');
        return $filter;
    }

    /**
     * @return FileFilter
     */
    public static function createAllBlacklisted(): FileFilter {
        return new self();
    }

    /**
     * @param string $path
     * @return void
     */
    public function addWhiteList(string $path): void {
        $this->whiteList[] = rtrim($this->normalizePath($path), '/');
    }

    /**
     * @param string $path
     * @return void
     */
    public function addBlackList(string $path): void {
        $this->blackList[] = rtrim($this->normalizePath($path), '/');
    }

    /**
     * @param string $extension
     * @return void
     */
    public function whitelistExtension(string $extension): void {
        $this->allowedExtensions[] = ltrim($extension, '.');
    }

    /**
     * @param string $extension
     * @return void
     */
    public function blacklistExtension(string $extension): void {
        $this->disallowedExtensions[] = ltrim($extension, '.');
    }

    /**
     * Test if path is white listed or blacklisted
     * @param string $path
     * @return bool
     */
    public function testPath(string $path): bool {
        $path = $this->normalizePath($path);
        return $this->isWhiteListed($path) > $this->isBlackListed($path);
    }

    /**
     * Test if extension is white listed or blacklisted
     * @param string $path
     * @return bool
     */
    public function testExtension(string $path): bool {
        $path = $this->normalizePath($path);
        return $this->isExtensionWhiteListed($path) > $this->isExtensionBlackListed($path);
    }

    /**
     * Test if file is white listed or blacklisted based on path and ext
     * @param string $path
     * @return bool
     */
    public function test(string $path): bool {
        return $this->testPath($path) && $this->testExtension($path);
    }

    /**
     * Check if a file has a whitelisted extension.
     */
    private function isValidExtension(string $path): bool {
        if ($this->allowedExtensions === null) {
            return true;
        }
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($extension, $this->allowedExtensions);
    }

    /**
     * Check if a file is whitelisted.
     *
     * @return int the length of the longest white list match
     */
    public function isExtensionWhiteListed(string $path): int {
        return in_array(pathinfo($path, PATHINFO_EXTENSION), $this->allowedExtensions, true);
    }

    /**
     * Check if a file is blacklisted.
     *
     * @return int the length of the longest black list match
     */
    private function isExtensionBlackListed(string $path): int {
        return in_array(pathinfo($path, PATHINFO_EXTENSION), $this->disallowedExtensions, true);
    }

    /**
     * Check if a file is whitelisted.
     *
     * @return int the length of the longest white list match
     */
    private function isWhiteListed(string $path): int {
        return $this->isListed($path, $this->whiteList);
    }

    /**
     * Check if a file is blacklisted.
     *
     * @return int the length of the longest black list match
     */
    private function isBlackListed(string $path): int {
        return $this->isListed($path, $this->blackList);
    }

    /**
     * @param string $path
     * @param array $list
     * @return int
     */
    private function isListed(string $path, array $list): int {
        $length = 0;
        foreach ($list as $item) {
            $itemLen = \strlen($item);
            // Check for exact file match.
            if ($item === $path) {
                return $itemLen;
            }
            // Check for directory match.
            if ($itemLen >= $length && $this->inDirectory($item, $path)) {
                $length = $itemLen + 1; // +1 for trailing /
            }
        }
        return $length;
    }

    /**
     * Check if a file is within a folder.
     */
    private function inDirectory(string $directory, string $path): bool {
        return ($directory === '') || (substr($path, 0, strlen($directory) + 1) === $directory . '/');
    }

    /*
     * Normalize to Unix-style path.
     */
    private function normalizePath(string $path): string {
        return str_replace('\\', '/', $path);
    }
}
