<?php
namespace Build;

class Build {
	private $root = false;
	private $url = false;
	private $collections = [];

	public static function project ($path, $url='http://separation.localhost') {
		new Build($path, $url);
	}

	public function __construct ($path, $url) {
		$this->root = $path;
		$this->url = $url;
		
		$this->vhost();
		$this->directories();
		$this->db();
		$this->route();
		$this->collections();
		$this->forms();
		$this->helpers();
		$this->events();
		$this->moveStatic();
		$this->cache();
		
		echo 'Built', "\n";
		exit;
	}

	private function cache () {
		$caches = [
			'helpers', 'events', 'filters', 'collections', 'forms'
		];
		
	}

	private function helpers () {
		$this->helpers = [];
		$helpers = glob($this->root . '/helpers/*.php');
		foreach ($helpers as $helper) {
			$this->helpers[] = basename($helper, '.php');
		}
		file_put_contents($this->root . '/helpers/cache.json', json_encode($this->helpers, JSON_PRETTY_PRINT));

		$helpers = glob($this->root . '/helpers/*.js');
		$jsCache = '';
		foreach ($helpers as $helper) {
			if (basename($helper) == 'helpers.js') {
				continue;
			}
			$jsCache .= file_get_contents($helper) . "\n\n";
		}
		file_put_contents($this->root . '/js/helpers.js', $jsCache);
	}

	private function events () {
		$this->events = [];
		$directories = glob($this->root . '/events/*', GLOB_ONLYDIR);
		foreach ($directories as $signal) {
			$signal = basename($signal);
			$dirFiles = glob($this->root . '/events/' . $signal . '/*.php');
			foreach ($dirFiles as $event) {
				$this->events[$signal][] = basename($event, '.php');
			}
		}
		file_put_contents($this->root . '/events/cache.json', json_encode($this->events, JSON_PRETTY_PRINT));
	}

	private function db () {
		$dbPath = $this->root . '/config/db.php';
		if (!file_exists($dbPath)) {
			file_put_contents($dbPath, file_get_contents(__DIR__ . '/../static/db.php'));
		}
	}

	private function moveStatic () {
		@symlink($this->root . '/vendor/virtuecenter/separation/dependencies/jquery.min.js', $this->root . '/js/jquery.min.js');
		@symlink($this->root . '/vendor/virtuecenter/separation/dependencies/handlebars.min.js', $this->root . '/js/handlebars.min.js');
		@symlink($this->root . '/vendor/virtuecenter/separation/jquery.separation.js', $this->root . '/js/jquery.separation.js');
		@symlink($this->root . '/vendor/virtuecenter/separation/dependencies/jquery.ba-hashchange.js', $this->root . '/js/jquery.ba-hashchange.js');
		@symlink($this->root . '/vendor/virtuecenter/separation/dependencies/jquery.form.js', $this->root . '/js/jquery.form.js');
		@symlink($this->root . '/vendor/virtuecenter/separation/dependencies/require.js', $this->root . '/js/require.js');
		@symlink($this->root . '/vendor/twbs/bootstrap/dist', $this->root . '/bootstrap');
		@symlink($this->root . '/vendor/twbs/bootstrap/assets/css/docs.css', $this->root . '/css/docs.css');

		//separation builder
		@symlink($this->root . '/vendor/virtuecenter/build/static/separation-builder.html', $this->root . '/layouts/separation-builder.html');
		@symlink($this->root . '/vendor/virtuecenter/build/static/separation-builder.js', $this->root . '/js/separation-builder.js');
	}

	private function route () {
		$routePath = $this->root . '/Route.php';
		if (!file_exists($routePath)) {
			file_put_contents($routePath, file_get_contents(__DIR__ . '/../static/Route.php'));
		}
	}

	private function vhost () {
		$vhostPath = $this->root . '/vhost.conf';
		if (!file_exists($vhostPath)) {
			file_put_contents($vhostPath, file_get_contents(__DIR__ . '/../static/vhost.conf'));
		}
	}

	private function directories () {
		foreach (['collections', 'config', 'css', 'forms', 'js', 'layouts', 'partials', 'sep', 'images', 'fonts', 'mvc', 'events', 'helpers'] as $dir) {
			$dirPath = $this->root . '/' . $dir;
			if (!file_exists($dirPath)) {
				mkdir($dirPath);
			}
		}
	}

	private function collectionStubRead ($name, &$collection) {
		$data = file_get_contents(__DIR__ . '/../static/' . $name);
		return str_replace(['{{$url}}', '{{$plural}}', '{{$singular}}'], [$this->url, $collection['p'], $collection['s']], $data);
	}

	private function collections () {
		$this->collections = [];
		$dirFiles = glob($this->root . '/collections/*.php');
		foreach ($dirFiles as $collection) {
			require_once($collection);
			$class = basename($collection, '.php');
			$this->collections[] = [
				'p' => $class,
				's' => $class::$singular
			];
		}
		file_put_contents($this->root . '/collections/cache.json', json_encode($this->collections, JSON_PRETTY_PRINT));

		foreach ($this->collections as $collection) {
			$filename = $this->root . '/layouts/' . $collection['p'] . '.html';
			if (!file_exists($filename)) {
				file_put_contents($filename, $this->collectionStubRead('layout-collection.html', $collection));
			}
			$filename = $this->root . '/partials/' . $collection['p'] . '.hbs';
			if (!file_exists($filename)) {
				file_put_contents($filename, $this->collectionStubRead('partial-collection.hbs', $collection));
			}
			$filename = $this->root . '/layouts/' . $collection['s'] . '.html';
			if (!file_exists($filename)) {
				file_put_contents($filename, $this->collectionStubRead('layout-document.html', $collection));
			}
			$filename = $this->root . '/partials/' . $collection['s'] . '.hbs';
			if (!file_exists($filename)) {
				file_put_contents($filename, $this->collectionStubRead('partial-document.hbs', $collection));	
			}
			$filename = $this->root . '/sep/' . $collection['p'] . '.js';
			if (!file_exists($filename)) {
				file_put_contents($filename, $this->collectionStubRead('collection.js', $collection));	
			}
			$filename = $this->root . '/sep/' . $collection['s'] . '.js';
			if (!file_exists($filename)) {
				file_put_contents($filename, $this->collectionStubRead('document.js', $collection));	
			}
		}
	}

	private function forms () {
		$this->forms = [];
		$dirFiles = glob($this->root . '/forms/*.php');
		foreach ($dirFiles as $form) {
			$class = basename($form, '.php');
			$this->forms[] = $class;
		}
		file_put_contents($this->root . '/forms/cache.json', json_encode($this->forms, JSON_PRETTY_PRINT));
		
		foreach ($this->forms as $form) {
			$filename = $this->root . '/layouts/form-' . $form . '.html';
			if (!file_exists($filename)) {
				$data = file_get_contents(__DIR__ . '/../static/form.html');
				$data = str_replace(['{{$form}}'], [$form], $data);
				file_put_contents($filename, $data);
			}
			$filename = $this->root . '/partials/form-' . $form . '.hbs';
			if (!file_exists($filename)) {
				$data = file_get_contents(__DIR__ . '/../static/form.hbs');
				require $this->root . '/forms/' . $form . '.php';
				$obj = new $form();
				ob_start();
				foreach ($obj->fields as $field) {
					echo '
	<div class="form-group">
		<label for="' . $field['name'] . '" class="col-lg-2 control-label">' . ucwords(str_replace('_', ' ', $field['name'])) . '</label>
		<div class="col-lg-10">
			{{{' . $field['name'] . '}}}
		</div>
	</div>';
				}
				echo '
	<input type="submit" />';
				$generated = ob_get_clean();
				$data = str_replace(['{{$form}}', '{{$generated}}'], [$form, $generated], $data);
				file_put_contents($filename, $data);
			}
			$filename = $this->root . '/sep/form-' . $form . '.js';
			if (!file_exists($filename)) {
				$data = file_get_contents(__DIR__ . '/../static/form.js');
				$data = str_replace(['{{$form}}', '{{$url}}'], [$form, $this->url], $data);
				file_put_contents($filename, $data);	
			}
		}
	}

	private function intranets () {
		//read admin intranets

		//creeate file cache
	}
}