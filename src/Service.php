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
use MongoClient;
use MongoDB;
use MongoCollection;
use MongoId;
use MongoDate;
use Exception;
use Memcache;
use Pheanstalk_Pheanstalk;
use Opine\Interfaces\Cache as CacheInterface;
use Opine\Interfaces\Route as RouteInterface;

class Service {
    private $root = false;
    private $pubSubModel;
    private $fieldModel;
    private $collectionModel;
    private $helperModel;
    private $configModel;
    private $formModel;
    private $cache;
    private $bundleModel;
    private $search;
    private $route;
    private $containerCache;
    private $handlebarService;
    private $languageModel;
    private $config;
    private $db;

    public function __construct (
        $root,
        $fieldModel,
        $pubSubModel,
        $collectionModel,
        $helperModel,
        $configModel,
        $formModel,
        $bundleModel,
        CacheInterface $cache,
        $search,
        RouteInterface $route,
        $containerCache,
        $handlebarService,
        $languageModel,
        $config,
        $db)
    {
        $this->root = $root;
        $this->fieldModel = $fieldModel;
        $this->pubSubModel = $pubSubModel;
        $this->collectionModel = $collectionModel;
        $this->helperModel = $helperModel;
        $this->configModel = $configModel;
        $this->formModel = $formModel;
        $this->bundleModel = $bundleModel;
        $this->cache = $cache;
        $this->search = $search;
        $this->route = $route;
        $this->containerCache = $containerCache;
        $this->handlebarService = $handlebarService;
        $this->languageModel = $languageModel;
        $this->config = $config;
        $this->db = $db;
    }

    public function project () {
        try {
            $this->search->indexCreateDefault();
        } catch (Exception $e) {
            //echo 'Search Index Error: ', $e->getMessage(), "\n";
        }
        $this->clearCache();
        $this->clearFileCache();
        $this->salt();
        $this->config();
        $this->directories();
        $this->db();
        $this->bundle();
        $this->bundles();
        $this->container();
        $this->route();
        $this->collections();
        $this->forms();
        $this->field();
        $this->helpers();
        $this->templatesCompile();
        $this->topics();
        $this->moveStatic();
        $this->languages();
        try {
            $this->adminUserFirst();
        } catch (Exception $e) {}
        echo 'Built', "\n";
    }

    public function languages () {
        $this->cache->set($this->root . '-languages', $this->languageModel->build(), 0);
    }

    public function templatesCompile () {
        $this->handlebarService->build();
    }

    public function field () {
        $this->fieldModel->build();
    }

    public function environmentCheck () {
        $authConfigFile = $this->root . '/../config/auth.php';
        if (file_exists($authConfigFile)) {
            echo 'Good: Authentication salt file already exists.', "\n";
        }

        //mongo
        if (class_exists('\MongoClient', false)) {
            echo 'Good: MongoDB client driver is installed.', "\n";
        } else {
            echo 'Problem: MongoDB client driver not installed.', "\n";
        }
        $dbConfig = $this->config->get('db');
        if (is_array($dbConfig) && count($dbConfig) > 0) {
            echo 'Good: Database config file exists.', "\n";
            try {
                $client = new MongoClient($dbConfig['conn']);
                $collections = $client->{$dbConfig['name']}->getCollectionNames();
                echo 'Good: can connect to database.', "\n";
            } catch (Exception $e) {
                echo 'Problem: can not connect to database: ', $e->getMessage(), "\n";
            }
        } else {
            echo 'Problem: Database config file does not exists.', "\n";
        }

        //memcache
        if (class_exists('\Memcache', false)) {
            echo 'Good: Memcache client driver is installed.', "\n";
            $memcache = new Memcache();
            try {
                $result = @$memcache->pconnect('localhost', 11211);
                if ($result !== false) {
                    echo 'Good: Memcache connection made.', "\n";
                } else {
                    echo 'Problem: Memcache connection failed.', "\n";
                }
            } catch (Exception $e) {
                echo 'Problem: Memcache: ', $e->getMessage(), "\n";
            }
        } else {
            echo 'Problem: Memcache client driver is not installed.', "\n";
        }

        //beanstalkd
        if (class_exists('\Pheanstalk_Pheanstalk')) {
            echo 'Good: Queue client driver is installed.', "\n";
            $queue = new Pheanstalk_Pheanstalk('127.0.0.1');
            try {
                if ($queue->getConnection()->isServiceListening() != true) {
                    echo 'Problem: Queue connetion not made.', "\n";
                } else {
                    echo 'Good: Queue connetion made.', "\n";
                }
            } catch (\Exception $e) {
                echo 'Problem: Queue: ', $e->getMessage(), "\n";
            }
        } else {
            echo 'Problem: Pheanstalkd client driver is not installed.', "\n";
        }
    }

    private function config () {
        $this->configModel->build($this->root);
    }

    private function clearCache () {
        $this->cache->deleteBatch([
            $this->root . '-collections',
            $this->root . '-forms',
            $this->root . '-bundles',
            $this->root . '-topics',
            $this->root . '-routes',
            $this->root . '-container',
            $this->root . '-languages',
            $this->root . '-config'
        ]);
    }

    private function clearFileCache () {
        shell_exec('rm -rf ' . $this->root . '/../var/cache');
        mkdir($this->root . '/../var/cache');
    }

    private function salt () {
        $authConfigFile = $this->root . '/../config/auth.php';
        if (file_exists($authConfigFile)) {
            return;
        }
        file_put_contents($authConfigFile, '<?php
return [
    "salt" => "' . uniqid() . uniqid() . uniqid() . '"
];');
    }

    private function adminUserFirst () {
        if (!class_exists('MongoClient', false)) {
            echo 'Note: MongoDB client driver not installed.', "\n";
            return;
        }
        try {
            $auth = require $this->root . '/../config/auth.php';
            if (!isset($auth['salt'])) {
                echo 'Problem: No Salt set in auth config file';
            }
            $found = $this->db->collection('users')->findOne(['groups' => 'manager'], ['_id', 'groups']);
            if (isset($found['_id'])) {
                echo 'Good: Superadmin already exists.', "\n";
                return;
            }
            $id = $this->db->id();
            $dbURI = 'users:' . (string)$id;
            $this->db->document($dbURI)->upsert([
                '_id'          => $id,
                'first_name'   => 'Admin',
                'last_name'    => 'Admin',
                'email'        => 'admin@website.com',
                'groups'       => ['manager'],
                'password'     => sha1($auth['salt'] . 'password'),
                'created_date' => new MongoDate(strtotime('now')),
                'dbURI'        => 'users:' . (string)$id,
                'acl'          => ['manager']
            ]);
            echo 'Good: Superuser created. admin@website.com : password', "\n";
        } catch (Exception $e) {
            echo 'Note: Can not create manager superuser because database credentials not yet set, or:', $e->getMessage(), "\n";
        }
    }

    private function bundle () {
        $defaultBundle = $this->root . '/../config/bundles.yml';
        if (file_exists($defaultBundle)) {
            return;
        }
        file_put_contents($defaultBundle, file_get_contents(__DIR__ . '/../static/bundles.yml'));
    }

    private function bundles () {
        $this->cache->set($this->root . '-bundles', $this->bundleModel->build(), 0);
    }

    private function collections () {
        $this->cache->set($this->root . '-collections', $this->collectionModel->build());
    }

    private function forms () {
        $this->cache->set($this->root . '-forms', $this->formModel->build());
    }

    private function helpers () {
        $this->helperModel->buildAll();
        $this->handlebarService->helpersLoad();
    }

    private function topics () {
        $this->cache->set($this->root . '-topics', json_encode($this->pubSubModel->build()));
    }

    private function db () {
        $dbPath = $this->root . '/../config/db.php';
        if (!file_exists($dbPath)) {
            file_put_contents($dbPath, file_get_contents(__DIR__ . '/../static/db.php'));
        }
    }

    private function moveStatic () {
        @copy($this->root . '/../vendor/opine/layout/dependencies/jquery.min.js', $this->root . '/js/jquery.min.js');
        @copy($this->root . '/../vendor/opine/layout/dependencies/jquery.form.js', $this->root . '/js/jquery.form.js');
        @copy($this->root . '/../vendor/opine/form/js/formXHR.js', $this->root . '/js/formXHR.js');
        @copy($this->root . '/../vendor/opine/form/js/formHelperSemantic.js', $this->root . '/js/formHelperSemantic.js');
    }

    private function route () {
        $routePath = $this->root . '/../Route.php';
        if (!file_exists($routePath)) {
            file_put_contents($routePath, file_get_contents(__DIR__ . '/../static/Route.php'));
        }
        $routes = json_encode($this->route->cacheGenerate());
        $this->cache->set($this->root . '-routes', $routes);
    }

    private function directories () {
        foreach (['css', 'js', 'layouts', 'partials', 'images', 'fonts', 'helpers'] as $dir) {
            $dirPath = $this->root . '/' . $dir;
            if (!file_exists($dirPath)) {
                mkdir($dirPath);
            }
        }
        foreach (['config', 'config/collections', 'config/forms', 'config/managers', 'config/layouts', 'app', 'app/models', 'app/views', 'app/controllers', 'app/helpers', 'var', 'var/cache', 'var/log'] as $dir) {
            $dirPath = $this->root . '/../' . $dir;
            if (!file_exists($dirPath)) {
                mkdir($dirPath);
            }
        }
        foreach (['collections', 'documents', 'forms'] as $dir) {
            $dirPath = $this->root . '/layouts/' . $dir;
            if (!file_exists($dirPath)) {
                mkdir($dirPath);
            }
            $dirPath = $this->root . '/partials/' . $dir;
            if (!file_exists($dirPath)) {
                mkdir($dirPath);
            }
            $dirPath = $this->root . '/../config/layouts/' . $dir;
            if (!file_exists($dirPath)) {
                mkdir($dirPath);
            }
        }
    }

    public function container () {
        $this->containerCache->clear();
        $this->containerCache->read($this->root . '/../config/container.yml');
        $this->cache->set($this->root . '-container', $this->containerCache->write());
    }

    public function collectionInstall ($collection) {
        $path = $this->root . '/../collections/' . $collection . '.php';
        if (file_exists($path)) {
            echo $path, ': already exists.', "\n";
            return;
        }
        $remote = 'https://raw.githubusercontent.com/Opine-Org/Collection/master/available/' . $collection . '.php';
        file_put_contents($path, file_get_contents($remote));
        echo $path, ': saved.', "\n";
    }

    public function managerInstall ($manager) {
        $path = $this->root . '/../managers/' . $manager . '.php';
        if (file_exists($path)) {
            echo $path, ': already exists.', "\n";
            return;
        }
        $remote = 'https://raw.githubusercontent.com/Opine-Org/Semantic-CM/master/available/' . $manager . '.php';
        file_put_contents($path, file_get_contents($remote));
        echo $path, ': saved.', "\n";
    }
}