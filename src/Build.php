<?php
/**
 * Opine\Build
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
namespace Opine;
use MongoClient;
use MongoDB;
use MongoCollection;
use MongoId;
use MongoDate;
use Exception;
use Memcache;
use Pheanstalk_Pheanstalk;

class Build {
    private $root = false;
    private $url = false;
    private $pubSubBuild;
    private $collectionModel;
    private $helperRoute;
    private $configRoute;
    private $formModel;
    private $cache;
    private $bundleRoute;
    private $search;
    private $route;
    private $containerCache;
    private $handlebarService;

    public function __construct (
        $root,
        $fieldRoute,
        $pubSubBuild,
        $collectionModel,
        $helperRoute,
        $configRoute,
        $formModel,
        $bundleRoute,
        $cache,
        $search,
        $route,
        $containerCache,
        $handlebarService)
    {
        $this->root = $root;
        $this->fieldRoute = $fieldRoute;
        $this->pubSubBuild = $pubSubBuild;
        $this->collectionModel = $collectionModel;
        $this->helperRoute = $helperRoute;
        $this->configRoute = $configRoute;
        $this->formModel = $formModel;
        $this->bundleRoute = $bundleRoute;
        $this->cache = $cache;
        $this->search = $search;
        $this->route = $route;
        $this->containerCache = $containerCache;
        $this->handlebarService = $handlebarService;
    }

    public function upgrade () {
        $this->collectionModel->upgrade();
        $this->formModel->upgrade($this->root);
        $this->bundleRoute->upgrade($this->root);
    }

    public function project () {
        try {
            $this->search->indexCreateDefault();
        } catch (Exception $e) {
            //echo 'Search Index Error: ', $e->getMessage(), "\n";
        }
        $this->clearCache();
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
        try {
            $this->adminUserFirst();
        } catch (Exception $e) {}
        echo 'Built', "\n";
    }

    public function templatesCompile () {
        $this->handlebarService->build();
    }

    public function field () {
        $this->fieldRoute->build();
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
        if (file_exists($this->root . '/../config/db.php')) {
            echo 'Good: Database config file exists.', "\n";
            $db = require $this->root . '/../config/db.php';
            try {
                $client = new MongoClient($db['conn']);
                $collections = $client->{$db['name']}->getCollectionNames();
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
        $this->configRoute->build($this->root);
    }

    private function clearCache () {
        $this->cache->deleteBatch([
            $this->root . '-collections',
            $this->root . '-forms',
            $this->root . '-bundles',
            $this->root . '-topics',
            $this->root . '-routes',
            $this->root . '-container'
        ]);
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
            $config = require $this->root . '/../config/db.php';
            $client = new MongoClient($config['conn']);
            $db = new MongoDB($client, $config['name']);
            $users = new MongoCollection($db, 'users');
            $found = $users->findOne(['groups' => 'manager'], ['_id', 'groups']);
            if (isset($found['_id'])) {
                echo 'Good: Superadmin already exists.', "\n";
                return;
            }
            $id = new MongoId();
            $users->save([
                '_id' => $id,
                'first_name' => 'Admin',
                'last_name' => 'Admin',
                'email' => 'admin@website.com',
                'groups' => ['manager'],
                'password' => sha1($auth['salt'] . 'password'),
                'created_date' => new MongoDate(strtotime('now')),
                'dbURI' => 'users:' . (string)$id
            ]);
            echo 'Good: Superuser created. admin@website.com : password', "\n";
        } catch (Exception $e) {
            echo 'Note: Can not create manager superuser because database credentials not yet set, or:', $e->getMessage(), "\n";
        }
    }

    private function bundle () {
        $defaultBundle = $this->root . '/../bundles/bundles.yml';
        if (file_exists($defaultBundle)) {
            return;
        }
        file_put_contents($defaultBundle, file_get_contents(__DIR__ . '/../static/bundles.yml'));
    }

    private function bundles () {
        $this->cache->set($this->root . '-bundles', $this->bundleRoute->build());
    }

    private function collections () {
        $this->cache->set($this->root . '-collections', $this->collectionModel->build(), 2, 0);
    }

    private function forms () {
        $this->cache->set($this->root . '-forms', $this->formModel->build(), 2, 0);
    }

    private function helpers () {
        $this->helperRoute->buildAll();
    }

    private function topics () {
        $topics = json_encode($this->pubSubBuild->build());
        file_put_contents($this->root . '/../cache/topics.json', $topics);
        $this->cache->set($this->root . '-topics', $topics, 2, 0);
    }

/*
    private function acl () {
        $folder = $this->root . '/../acl';
        if (!file_exists($folder)) {
            mkdir($folder);
        }
        $path = $this->root . '/../acl/custom.yml';
        if (!file_exists($path)) {
            file_put_contents($path, 'imports:' . "\n\n" . 'groups:');
        }
        $this->authentication->build();
    }
*/
    private function db () {
        $dbPath = $this->root . '/../config/db.php';
        if (!file_exists($dbPath)) {
            file_put_contents($dbPath, file_get_contents(__DIR__ . '/../static/db.php'));
        }
    }

    private function moveStatic () {
        @copy($this->root . '/../vendor/opine/separation/dependencies/jquery.min.js', $this->root . '/js/jquery.min.js');
        @copy($this->root . '/../vendor/opine/separation/dependencies/jquery.form.js', $this->root . '/js/jquery.form.js');
        @copy($this->root . '/../vendor/opine/form/js/formXHR.js', $this->root . '/js/formXHR.js');
        @copy($this->root . '/../vendor/opine/form/js/formHelperSemantic.js', $this->root . '/js/formHelperSemantic.js');
    }

    private function route () {
        $routePath = $this->root . '/../Route.php';
        if (!file_exists($routePath)) {
            file_put_contents($routePath, file_get_contents(__DIR__ . '/../static/Route.php'));
        }
        $routes = json_encode($this->route->cacheGenerate());
        $this->cache->set($this->root . '-routes', $routes, 2, 0);
    }

    private function directories () {
        foreach (['css', 'js', 'layouts', 'partials', 'images', 'fonts', 'helpers'] as $dir) {
            $dirPath = $this->root . '/' . $dir;
            if (!file_exists($dirPath)) {
                mkdir($dirPath);
            }
        }
        foreach (['collections', 'config', 'forms', 'app', 'models', 'views', 'controllers', 'bundles', 'cache'] as $dir) {
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
            $dirPath = $this->root . '/../app/' . $dir;
            if (!file_exists($dirPath)) {
                mkdir($dirPath);
            }
        }
    }

    public function container () {
        $this->containerCache->clear();
        $this->containerCache->read($this->root . '/../container.yml');
        $this->cache->set($this->root . '-container', $this->containerCache->write(), 2, 0);
    }
}