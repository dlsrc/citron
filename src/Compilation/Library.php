<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Compilation;

use Citron\Config;
use Citron\Builder;

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
			if ($c->isSnippet($match[2])) {
				continue;
			}

			if ('%' == $match[1]) {
				$primitive = true;
			}
			else {
				$primitive = false;
			}

			if ('^' == $match[2]) {
				$variant = true;
			}
			else {
				$variant = false;
			}

			$c->addSnippet(new Snippet(
				name: $match[3],
				primitive: $primitive,
				variant: $variant,
				content: $match[5],
				type: $match[4],
			));
		}
	}

	public function dropImportDirective(array $directive) {}
	public function normalizeContent() {}
}
