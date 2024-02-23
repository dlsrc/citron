<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Collector;

use Citron\Builder;
use Citron\Collector;
use Citron\Config;

abstract class Unit {
	abstract public function dropImportDirective(array $directive);

	public function __construct(
		protected string $content,
		public readonly string $filename,
		public readonly int $config,
	) {
		$this->content = preg_replace(['/\xEF\xBB\xBF/', '/\x0D/',], ['', '',], $this->content);
	}

	public function getContent(): string {
		return $this->content;
	}

	public function importLibs(Collector $c): array {
		if (0 == preg_match_all(
			'/<!--\s*import\s+(%|(?:\.\.\/)+|\.\/|)([^\s>]+)\s*-->/i',
			$this->content,
			$matches,
			PREG_PATTERN_ORDER)
		) {
			return [];
		}

		$lib = [];
		$tpldir = strtr(dirname($this->filename), '\\', '/');
		$cfg = $c->getConfig($this->config);

		foreach (array_keys($matches[0]) as $key) {
			if ($filename = $c->realpath($matches[1][$key], $matches[2][$key], $tpldir)) {
				if ($c->isLib($filename)) {
					continue;
				}

				if (!$content = file_get_contents($filename)) {
					continue;
				}

				$config = $cfg->setup($content, $c, false);

				$lib[] = $filename;
				$c->addLib(new Library(
					content: $content,
					filename: $filename,
					config: $c->getConfigId($config),
				));
			}			
		}

		$this->dropImportDirective($matches[0]);
		return $lib;
	}

	public function prepareVariables(Collector $c): void {
		$b_cfg = Config::get();
		$c_cfg = $c->getConfig($this->config);

		if ($b_cfg === $c_cfg) {
			return;
		}

		$search  = [];
		$replace = [];

		if (!$c_cfg->isLocalEqual($b_cfg)) {
			/// Обработка локальных переменных,
			$pattern = $c_cfg->patternLocal(false); 

			if (preg_match_all($pattern, $this->content, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $var) {
					$isglobal = false;

					if (isset($var[5])) {
						$var[2] = $var[5];
						$isglobal = true;
					}
					elseif (isset($var[4])) {
						$var[2] = $var[3].$var[4].$var[3];
					}
					elseif (!isset($var[2])) {
						$var[2] = '';
					}
					
					$search[]  = $var[0];
					$replace[] = $b_cfg->viewLocal($var[1], $var[2], $isglobal);
				}
			}
		}

		if (!$c_cfg->isGlobalEqual($b_cfg)) {
			/// Обработка глобальных переменных
			$pattern = $c_cfg->patternGlobal();

			if (preg_match_all($pattern, $this->content, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $var) {
					if (isset($var[4])) {
						$var[2] = $var[3].$var[4].$var[3];
					}
					elseif (!isset($var[2])) {
						$var[2] = '';
					}
					
					$search[]  = $var[0];
					$replace[] = $b_cfg->viewGlobal($var[1], $var[2]);
				}
			}
		}

		if (empty($search)) {
			return;
		}

		$this->content = str_replace($search, $replace, $this->content);
	}
}
