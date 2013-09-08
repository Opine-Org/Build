<?php
class Build {
	private $root = false; 
	private $collections = [];

	public static function project ($path) {
		new Build($path);
	}

	public function __construct ($$path) {
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

	private function collections () {
		//read collections

		//read packages

		//write generated file

		//creeate file cache
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

Build::run($argv[1], $argv[2], $argv[3]);