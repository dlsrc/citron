<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Compilation;

use Citron\Config;

class Library extends Unit {
	private const string SNIPPETS = '/^(?:\/\/)?<!--\s*(%|)\s*(\^|)(\p{Lu}\w*)
	(?:\s*\:\s*(\p{Lu}\w*))?\s*-->
	(.+)
	(?:\/\/)?<!--\s*~\g{3}\s*-->/Uxims';

	public function createSnippets(Collector $c): void {
		if (0 == preg_match_all(self::SNIPPETS, $this->content, $matches, PREG_SET_ORDER)) {
			return;
		}

		foreach ($matches as $match) {
			if ($c->isSnippet($match[3])) {
				continue;
			}

			$c->addSnippet(new Snippet(
				name: $match[3],
				primitive: '%' == $match[1],
				variant: '^' == $match[2],
				content: $match[5],
				type: $match[4],
			));
		}
	}

	public function dropImportDirective(array $directive) {}
	public function normalizeContent() {}
}
