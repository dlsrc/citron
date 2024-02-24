<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Citron\Compilation\Collector;
use Ultra\IO;

trait Develop {
	private static function develop(string $template): string {
		if (!is_readable($template)) {
			Component::error(Info::message('e_no_tpl', $template), Code::Make, true);
			return '';
		}

		if (!$c = Collector::make($template)) {
			Component::error(Info::message('e_collect', $template), Code::Make, true);
			return '';
		}

		$tpl = $c->collect();
		IO::fw(Info::collect($template), $tpl);
		return $tpl;
	}

	private static function build(string $template): string {
		$file = Info::collect($template);

		if (is_readable($file)) {
			if (!$tpl = file_get_contents($file)) {
				Component::error(Info::message('e_get_file', $file), Code::Content, true);
				return '';
			}

			return $tpl;
		}

		return self::develop($template);
	}

	private function __construct() {}
}
