<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Ultra\Generic\Informer;
use Ultra\Generic\Sociable;

final class Info implements Sociable {
	use Informer;
	private const string VERSION = '1.1.1';
	private const string RELEASE = '';

	public static function build(string $template, string|null $markup=null): string {
		if (self::RELEASE) {
			$release = '-'.self::VERSION.'-'.strtolower(Build::getMainName()).'-'.self::RELEASE;
		}
		else {
			$release = '-'.self::VERSION.'-'.strtolower(Build::getMainName()).'-release';
		}

		if ($markup && 'ROOT' != $markup) {
			return substr($template, 0, strrpos($template, '.'))
			.'-'.$markup.$release.'.php';
		}

		return substr($template, 0, strrpos($template, '.')).$release.'.php';
	}

	public static function collect(string $template): string {
		return substr($template, 0, strrpos($template, '.')).
			'-'.self::VERSION.
			substr($template, strrpos($template, '.'));
	}
}
