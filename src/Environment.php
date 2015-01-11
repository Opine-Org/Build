<?php
/**
 * Opine\Build\Environment
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

use Exception;
use MongoClient;
use Memcache;
use Pheanstalk_Pheanstalk;

class Environment {
    private $root;
    private $config;

    public function __construct($root, $config)
    {
        $this->root = $root;
        $this->config = $config;
    }

    public function check()
    {
        $authConfigFile = $this->root.'/../config/auth.php';
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
}
