<?php
class Route {
    private $route;

    public function __construct ($container) {
        $this->route = $container->route;
    }

    public function paths () {
        $this->route->get('/', function () {
            echo '<html><body>Homepage</body></html>';
        });
    }
}