<?php
/**
 * Opine\Build\Person
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

use MongoDate;
use Symfony\Component\Yaml\Yaml;

class Person {
    private $root;
    private $db;
    private $authPath;

    public function __construct ($root, $db)
    {
        $this->root = $root;
        $this->db = $db;
        $this->authPath = $this->root.'/../config/settings/auth.yml';
    }

    public function build ()
    {
        $this->salt();
        $this->adminUserFirst();
    }

    private function salt()
    {
        if (file_exists($this->authPath)) {
            return;
        }
        file_put_contents($this->authPath, 'settings:
    salt: '.uniqid().uniqid().uniqid());
    }

    private function adminUserFirst()
    {
        if (!class_exists('MongoClient', false)) {
            echo 'Note: MongoDB client driver not installed.', "\n";

            return;
        }
        try {
            $auth = Yaml::parse(file_get_contents($this->authPath))['settings'];
            if (!isset($auth['salt'])) {
                echo 'Problem: No Salt set in auth config file';
            }
            $found = $this->db->collection('users')->findOne(['groups' => 'manager'], ['_id', 'groups']);
            if (isset($found['_id'])) {
                echo 'Good: Superadmin already exists.', "\n";

                return;
            }
            $id = $this->db->id();
            $dbURI = 'users:'.(string) $id;
            $this->db->document($dbURI)->upsert([
                '_id'          => $id,
                'first_name'   => 'Admin',
                'last_name'    => 'Admin',
                'email'        => 'admin@website.com',
                'groups'       => ['manager'],
                'password'     => sha1($auth['salt'].'password'),
                'created_date' => new MongoDate(strtotime('now')),
                'dbURI'        => 'users:'.(string) $id,
                'acl'          => ['manager'],
            ]);
            echo 'Good: Superuser created. admin@website.com : password', "\n";
        } catch (Exception $e) {
            echo 'Note: Can not create manager superuser because database credentials not yet set, or:', $e->getMessage(), "\n";
        }
    }
}