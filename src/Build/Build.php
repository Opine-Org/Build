<?php
namespace Build;
use Event\EventRoute;
use Collection\CollectionRoute;
use Helper\HelperRoute;
use Cache\Cache;
use Config\ConfigRoute;
use Filter\Filter;

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
		
		$this->clearCache();
		$this->config();
		$this->vhost();
		$this->directories();
		$this->db();
		$this->route();
		$this->collections();
		$this->filters();
		$this->forms();
		$this->helpers();
		$this->events();
		$this->moveStatic();
		
		echo 'Built', "\n";
		exit;
	}

	private function config () {
		ConfigRoute::build($this->root);
	}

	private function clearCache () {
		Cache::deleteBatch([
			$this->root . '-collections.json',
			$this->root . '-filters.json',
			$this->root . '-helpers.json',
			$this->root . '-events.json'
		]);
	}

	private function collections () {
		Cache::factory()->set($this->root . '-collections.json', CollectionRoute::build($this->root, $this->url, __DIR__), MEMCACHE_COMPRESSED, 0);
	}

	private function filters () {
		Cache::factory()->set($this->root . '-filters.json', Filter::build($this->root), MEMCACHE_COMPRESSED, 0);
	}

	private function helpers () {
		Cache::factory()->set($this->root . '-helpers.json', HelperRoute::build($this->root), MEMCACHE_COMPRESSED, 0);
	}

	private function events () {
		Cache::factory()->set($this->root . '-events.json', EventRoute::build($this->root), MEMCACHE_COMPRESSED, 0);
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
		foreach (['collections', 'config', 'css', 'forms', 'js', 'layouts', 'partials', 'sep', 'images', 'fonts', 'mvc', 'events', 'helpers', 'filters'] as $dir) {
			$dirPath = $this->root . '/' . $dir;
			if (!file_exists($dirPath)) {
				mkdir($dirPath);
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
}