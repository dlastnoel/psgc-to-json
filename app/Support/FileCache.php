<?php

namespace App\Support;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class FileCache implements CacheInterface
{
    protected string $cacheDir;

    protected int $defaultTtl;

    public function __construct(string $cacheDir, int $defaultTtl = 3600)
    {
        $this->cacheDir = $cacheDir;
        $this->defaultTtl = $defaultTtl;

        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }

    public function get($key, $default = null): mixed
    {
        $this->validateKey($key);

        $file = $this->getFilePath($key);

        if (! file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data === false) {
            return $default;
        }

        if (isset($data['expires']) && $data['expires'] !== null && time() > $data['expires']) {
            @unlink($file);

            return $default;
        }

        return $data['value'] ?? $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->validateKey($key);

        if ($ttl === null) {
            $ttl = $this->defaultTtl;
        }

        // If ttl is null or 0, cache indefinitely
        if ($ttl <= 0) {
            $expires = null;
        } else {
            $expires = time() + $ttl;
        }

        $data = [
            'value' => $value,
            'expires' => $expires,
        ];

        $file = $this->getFilePath($key);

        return file_put_contents($file, serialize($data)) !== false;
    }

    public function delete($key): bool
    {
        $this->validateKey($key);

        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir.'/*.cache');

        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (! $this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function deleteMultiple($keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (! $this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    public function has($key): bool
    {
        $this->validateKey($key);

        $file = $this->getFilePath($key);

        if (! file_exists($file)) {
            return false;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data === false) {
            return false;
        }

        if (isset($data['expires']) && $data['expires'] !== null && time() > $data['expires']) {
            return false;
        }

        return true;
    }

    protected function getFilePath(string $key): string
    {
        $safeKey = md5($key);

        return $this->cacheDir.'/'.$safeKey.'.cache';
    }

    protected function validateKey($key): void
    {
        if (! is_string($key)) {
            throw new InvalidArgumentException('Cache key must be a string');
        }

        if (preg_match('/\{|\}|\(|\)|\/|\\\\|@|:/', $key)) {
            throw new InvalidArgumentException('Cache key contains invalid characters');
        }
    }
}
