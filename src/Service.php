<?php
/**
 * Opine\Build\Service
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine\Build;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use Exception;
use Opine\Interfaces\Cache as CacheInterface;

class Service
{
    private $root;
    private $cache;
    private $config;
    private $bundles;
    private $container;
    private $route;
    private $topics;

    private static function environment()
    {
        return empty(getenv('OPINE_ENV')) ? 'dev' : getenv('OPINE_ENV');
    }

    private static function projectName()
    {
        return empty(getenv('OPINE_PROJECT')) ? 'project' : getenv('OPINE_PROJECT');
    }

    public function __construct($root, CacheInterface $cache, $config, $bundles, $container, $route, $topics)
    {
        $this->root = $root;
        $this->cache = $cache;
        $this->config = $config;
        $this->bundles = $bundles;
        $this->container = $container;
        $this->route = $route;
        $this->topics = $topics;
    }

    public function project()
    {
        //clear memcache and filesystem
        $this->clearCache();
        $this->clearFileCache();

        //build each type of thing
        $this->config->build();
        $this->bundles->build();
        $this->container->build();
        $this->bundles->build();
        $this->container->build();
        $this->route->build();
        $this->topics->build();

        //put cache data into memcache
        $cache = [
            'bundles'     => $this->getFile($this->root.'/../var/cache/bundles.json'),
            'topics'      => $this->getFile($this->root.'/../var/cache/topics.json'),
            'routes'      => $this->getFile($this->root.'/../var/cache/routes.json'),
            'container'   => $this->getFile($this->root.'/../var/cache/container.json'),
            'config'      => $this->getFile($this->root.'/../var/cache/config.json')
        ];

        $cachePrefix = self::projectName() . self::environment();
        $this->cache->set($cachePrefix . '-opine', json_encode($cache));
        return $cache;
    }

    private function getFile ($path)
    {
        if (!file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true);
    }

    private function clearCache()
    {
        $cachePrefix = self::projectName() . self::environment();
        $this->cache->delete($cachePrefix . '-opine');
    }

    private function clearFileCache()
    {
        $dirPath = $this->root.'/../var/cache';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }
        rmdir($dirPath);
    }
}
