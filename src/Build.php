<?php
class Build {
	private $root = false; 
	private $collections = [];

	public static function project ($path) {
		new Build($path);
	}

	public function __construct ($path) {
		$this->root = $path;
		
		$this->collections();
		$this->layouts();
		$this->partials();
		$this->forms();
		$this->separations();
		
		//$this->admins();
		//$this->events();
		//$this->custom();
		
		//$this->masterCache();
		echo 'Built', "\n";
		exit;
	}

	private function getPackages ($type) {
		if (file_exists($this->root . '/' . $type . '/packages.json')) {
			$packageContainer = json_decode(file_get_contents($this->root . '/' . $type . '/packages.json'));
			foreach ($packageContainer as $package) {
				$package = json_decode(file_get_contents($package));
				foreach ($package as $path) {
					$destination = basename($path);
					if (file_exists($this->root . '/' . $type . '/' . $destination)) {
						continue;
					}
					file_put_contents($destination, file_get_contents($collectionPath));
				}
			}
		}
	}

	private function collections () {
		$this->getPackages('collections');
		$collections = [];
		$dirFiles = glob($this->root . '/collections/*.php');
		foreach ($dirFiles as $collection) {
			require_once($collection);
			$class = basename($collection, '.php');
			$collections[] = [
				'name' => $collection,
				'p' => $class,
				's' => $class::$singular
			];
		}
		file_put_contents($this->root . '/collections/cache.json', json_encode($collections, JSON_PRETTY_PRINT));
	}

	private function layouts () {
		//generate a layout for each collection and singular if they don't already exist
	}

	private function partials () {
		//generate a template for each collection and singular if they don't already exist
	}

	private function forms () {
		//read forms

		//read packages

		//creeate file cache
	}

	private function separations () {
		//generate a separation config for each collection and singular if they don't already exist

		//use mode to determine where to get data from: local, development, production
	}

	private function intranets () {
		//read admin instranets

		//read packages

		//creeate file cache
	}

	private function events () {
		//read admins

		//read packages

		//creeate file cache
	}

	private function custom () {
		//read mvc setup

		//read packages

		///create file cache
	}

	private function masterCache () {
		//create maste route config file for everything -- on disk and in ram
	}
}