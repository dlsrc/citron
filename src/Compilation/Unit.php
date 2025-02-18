<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Compilation;

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
			'/<!--\s*(?:import|required)\s+(%|(?:\.\.\/)+|\.\/|@citron|)([^\s>]+)\s*-->/i',
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
			if ($filename = $c->path->realpath($matches[1][$key], $matches[2][$key], $tpldir)) {
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
}
