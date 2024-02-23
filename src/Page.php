<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Ultra\Export\Exporter;

final class Page {
	use Develop;

	private static Component|null $_page = null;

	public static function make(string $template): Component {
		if (null == self::$_page) {
			$page = Info::build($template);

			if (is_readable($page) && Mode::Product->current()) {
				self::$_page = include $page;
			}
			else {
				if (Mode::Develop->current()) {
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

				self::$_page = Builder::get()->build($tpl);
				(new Exporter($page))->save(self::$_page);
			}
		}

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
}
