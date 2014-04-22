<?php
class Route {
    private $route;

    public function __construct ($container) {
        $this->route = $container->route;
    }

    public function custom () {
        $this->route->get('/', function () {
            echo '<html><body>Homepage</body></html>';
        });
    }
}