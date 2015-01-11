<?php
namespace Opine;

use PHPUnit_Framework_TestCase;
use Opine\Container\Service as Container;
use Opine\Config\Service as Config;

class BuildTest extends PHPUnit_Framework_TestCase
{
    private $build;

    public function setup()
    {
        $root = __DIR__.'/../public';
        $config = new Config($root);
        $config->cacheSet();
        $this->container = Container::instance($root, $config, $root.'/../config/containers/test-container.yml');
        $this->build = $this->container->get('build');
    }

    public function testBuild()
    {
        $cache = $this->build->project();
        $this->assertTrue(isset($cache['collections']));
        $this->assertTrue(isset($cache['forms']));
        $this->assertTrue(isset($cache['bundles']));
        $this->assertTrue(isset($cache['topics']));
        $this->assertTrue(isset($cache['routes']));
        $this->assertTrue(isset($cache['container']));
        $this->assertTrue(isset($cache['languages']));
        $this->assertTrue(isset($cache['config']));
    }
}
