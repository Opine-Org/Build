<?php
/**
 * Opine\HandlebarsCompiler
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine;

class HandlebarsCompiler {
	private $root;
	private $silent = false;

	public function __construct ($root) {
		$this->root = $root;
		$this->compileHelpers;
	}

	public function silent () {
		if ($this->silent === true) {
			$this->silent = false;
		} else {
			$this->silent = true;
		}
	}

	private function compileHelpers () {
		//can we use a doc block in individual helper files to differentiate between helpers and block helpers?
	}

	public function compileFolder ($folders=false) {
		if ($folders === false) {
			$folders = [
				$this->root . '/partials'
				$this->root . '/templates'
			];
		}
		foreach ($folders as $folder) {

		}
	}

	private function compileFile ($templatePath, $compiledPath) {
		$template = file_get_contents($templatePath);
		$php = LightnCandy::compile($template, [
    		'flags' => LightnCandy::FLAG_ERROR_LOG | LightnCandy::FLAG_STANDALONE | LightnCandy::FLAG_HANDLEBARSJS | LightnCandy::FLAG_NAMEDARG,
    		'hbhelpers' => [],
    		'helpers' => []
		]);
	}
}