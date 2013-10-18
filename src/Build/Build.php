<?php
namespace Build;

class Build {
	private $root = false;
	private $url = false;
	private $pubSubBuild;
	private $collectionRoute;
	private $helperRoute;
	private $configRoute;
	private $filter;
	private $cache;

	public function __construct ($pubSubBuild, $collectionRoute, $helperRoute, $configRoute, $filter, $cache) {
		$this->pubSubBuild = $pubSubBuild;
		$this->collectionRoute = $collectionRoute;
		$this->helperRoute = $helperRoute;
		$this->configRoute = $configRoute;
		$this->filter = $filter;
		$this->cache = $cache;
	}

	public function project ($path, $url='http://json.virtuecenter.com') {
		$this->root = $path;
		$this->url = $url;
		
		$this->clearCache();
		$this->config();
		$this->directories();
		$this->db();
		$this->route();
		$this->collections();
		$this->filters();
		$this->forms();
		$this->helpers();
		$this->topics();
		$this->moveStatic();
		
		echo 'Built', "\n";
		exit;
	}

	private function config () {
		$this->configRoute->build($this->root);
	}

	private function clearCache () {
		$this->cache->deleteBatch([
			$this->root . '-collections.json',
			$this->root . '-filters.json',
			$this->root . '-helpers.json',
			$this->root . '-events.json'
		]);
	}

	private function collections () {
		$this->cache->set($this->root . '-collections.json', $this->collectionRoute->build($this->root, $this->url, __DIR__), 2, 0);
	}

	private function filters () {
		$this->cache->set($this->root . '-filters.json', $this->filter->build($this->root), 2, 0);
	}

	private function helpers () {
		$this->cache->set($this->root . '-helpers.json', $this->helperRoute->build($this->root), 2, 0);
	}

	private function topics () {
		$this->pubSubBuild->build($this->root);
	}

	private function db () {
		$dbPath = $this->root . '/../config/db.php';
		if (!file_exists($dbPath)) {
			file_put_contents($dbPath, file_get_contents(__DIR__ . '/../../static/db.php'));
		}
	}

	private function moveStatic () {
		@symlink($this->root . '/../vendor/virtuecenter/separation/dependencies/jquery.min.js', $this->root . '/js/jquery.min.js');
		@symlink($this->root . '/../vendor/virtuecenter/separation/dependencies/handlebars.min.js', $this->root . '/js/handlebars.min.js');
		@symlink($this->root . '/../vendor/virtuecenter/separation/jquery.separation.js', $this->root . '/js/jquery.separation.js');
		@symlink($this->root . '/../vendor/virtuecenter/separation/dependencies/jquery.ba-hashchange.js', $this->root . '/js/jquery.ba-hashchange.js');
		@symlink($this->root . '/../vendor/virtuecenter/separation/dependencies/jquery.form.js', $this->root . '/js/jquery.form.js');
		@symlink($this->root . '/../vendor/virtuecenter/separation/dependencies/require.js', $this->root . '/js/require.js');
		@symlink($this->root . '/../vendor/virtuecenter/form/js/formXHR.js', $this->root . '/js/formXHR.js');
		@symlink($this->root . '/../vendor/virtuecenter/form/js/formHelperSemantic.js', $this->root . '/js/formHelperSemantic.js');
	}

	private function route () {
		$routePath = $this->root . '/../Route.php';
		if (!file_exists($routePath)) {
			file_put_contents($routePath, file_get_contents(__DIR__ . '/../../static/Route.php'));
		}
	}

	private function directories () {
		foreach (['css', 'js', 'layouts', 'partials', 'images', 'fonts', 'helpers'] as $dir) {
			$dirPath = $this->root . '/' . $dir;
			if (!file_exists($dirPath)) {
				mkdir($dirPath);
			}
		}

		foreach (['collections', 'config', 'forms', 'app', 'mvc', 'subscribers', 'filters', 'bundles'] as $dir) {
			$dirPath = $this->root . '/../' . $dir;
			if (!file_exists($dirPath)) {
				mkdir($dirPath);
			}
		}
	}

	private function forms () {
		$this->forms = [];
		$dirFiles = glob($this->root . '/../forms/*.php');
		foreach ($dirFiles as $form) {
			$class = basename($form, '.php');
			$this->forms[] = $class;
		}
		file_put_contents($this->root . '/../forms/cache.json', json_encode($this->forms, JSON_PRETTY_PRINT));
		
		foreach ($this->forms as $form) {
			$filename = $this->root . '/layouts/form-' . $form . '.html';
			if (!file_exists($filename)) {
				$data = file_get_contents(__DIR__ . '/../../static/form.html');
				$data = str_replace(['{{$form}}'], [$form], $data);
				file_put_contents($filename, $data);
			}
			$filename = $this->root . '/partials/form-' . $form . '.hbs';
			if (!file_exists($filename)) {
				$data = file_get_contents(__DIR__ . '/../../static/form.hbs');
				require $this->root . '/../forms/' . $form . '.php';
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
/*
			$filename = $this->root . '/sep/form-' . $form . '.js';
			if (!file_exists($filename)) {
				$data = file_get_contents(__DIR__ . '/../../static/sep-form.js');
				$data = str_replace(['{{$form}}', '{{$url}}'], [$form, $this->url], $data);
				file_put_contents($filename, $data);	
			}
*/
			$filename = $this->root . '/../app/form-' . $form . '.yml';
			if (!file_exists($filename)) {
				$data = file_get_contents(__DIR__ . '/../../static/app-form.yml');
				$data = str_replace(['{{$form}}', '{{$url}}'], [$form, $this->url], $data);
				file_put_contents($filename, $data);
			}
		}
	}
}
