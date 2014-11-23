<?php
class Route {
    private $route;

    public function __construct ($route) {
        $this->route = $route;
    }

    public function paths () {
        $this->route->get('/', function () {
            echo '<html><body>Homepage</body></html>';
        });
    }
}