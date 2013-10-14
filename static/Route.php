<?php
class Route {
	private $slim;

	public function __construct ($container) {
		$this->slim = $container->slim;
	}

	public function custom () {
		$this->slim->get('/', function () {
			echo '<html><body>Homepage</body></html>';
		});
	}
}