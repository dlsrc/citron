<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Ultra\Exporter;

final class Page {
	use Develop;

	private static Component|null $_page = null;

	public static function make(string $template): Component {
		self::$_page ??= self::_make($template);
		return self::$_page;
	}

	public static function drop(): void {
		self::$_page = null;
	}

	public static function exists(): bool {
		return is_object(self::$_page);
	}

	public static function child(string $name): bool {
		if (self::$_page && self::$_page->isComponent($name)) {
			return true;
		}

		return false;
	}

	public static function open(): Component {
		if (self::$_page) {
			return self::$_page;
		}

		Component::error(Info::message('e_no_page'), Code::Open, true);
		return Component::emulate();
	}

	private static function _make(string $template): Component {
		$file = Info::build($template);

		if (is_readable($file) && Mode::Product()) {
			$page = include $file;

			if ($page instanceof Component) {
				return $page;
			}
		}
		
		if (Mode::Develop()) {
			$tpl = self::develop($template);
		}
		else {
			$tpl = self::build($template);
		}

		if (!$tpl) {
			// Ошибку регистрируют методы
			// Page::build и Page::develop (см. trait Develop)
			return Component::emulate();
		}

		if (!$page = Builder::get()?->build($tpl)) {
			return Component::emulate();
		}

		new Exporter($file)->save($page);
		return $page;
	}
}
