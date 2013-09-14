<?php
class Route {
	public function custom (&$app) {
		$app->get('/', function () {
			echo 'Homepage';
		});
	}
}