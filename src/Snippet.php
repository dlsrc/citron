<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Ultra\Exporter;

final class Snippet {
	use Develop;

	private static array $_snippet = [];

	public static function attach(Component $com, array $vars = [], bool $byname = false): string {
		if ($byname) {
			$name = $com->getName();
		}
		else {
			$name = $com->getClass();
		}

		if ($com instanceof Derivative) {
			self::$_snippet[$name] = $com->getOriginal();
		}
		else {
			self::$_snippet[$name] = clone $com;
			self::$_snippet[$name]->drop();

			if (self::$_snippet[$name] instanceof Wrapped) {
				self::$_snippet[$name]->unwrap();
			}
		}

		if (!empty($vars)) {
			self::prepare($name, $vars);
		}

		return $name;
	}

	public static function exists(Component $com, bool $byname = false): bool {
		if ($byname) {
			$name = $com->getName();
		}
		else {
			$name = $com->getClass();
		}

		return isset(self::$_snippet[$name]);
	}

	public static function prepare(string $name, array $vars): void {
		if (!isset(self::$_snippet[$name])) {
			return;
		}

		foreach ($vars as $key => $val) {
			if (str_contains($key, '.')) {
				$path = explode('.', $key);
				$size = count($path) - 1;
				$com  = self::$_snippet[$name];

				for($i = 0; $i < $size; $i++) {
					if ($com->isComponent($path[$i])) {
						$com = $com->{$path[$i]};
					}
					else {
						continue 2; // foreach continue
					}
				}

				$com->{$path[$i]} = $val;
			}
			else {
				self::$_snippet[$name]->$key = $val;
			}
		}		
	}

	public static function make(string $template, string $name = '', string $markup = 'ROOT'): Component {
		if ('' == $name) {
			$name = $markup;
		}

		if ('ROOT' == $name || '' == $name) {
			$name = $template;
		}

		if (!isset(self::$_snippet[$name])) {
			if ($name == $template) {
				$snippet = Info::build($template);
			}
			else {
				$snippet = Info::build($template, $name);
			}

			if (is_readable($snippet) && Mode::Product()) {
				self::$_snippet[$name] = include $snippet;
			}
			else {
				if (Mode::Develop()) {
					$tpl = self::develop($template, $name);
				}
				else {
					$tpl = self::build($template, $name);
				}

				if (!$tpl) {
					// Ошибку регистрирует метод Snippet::develop (см. trait Develop)
					return Component::emulate();
				}

				$cfg = Config::get();
				$cfg->root = 'OriginalComposite';
				$component = Builder::get()->build($tpl);

				if ('ROOT' != $markup) {
					$type = explode('.', $markup);

					if ('ROOT' == $type[0]) {
						array_shift($type);
					}

					foreach ($type as $name) {
						if ($component->isComponent($name)) {
							$component = $component->$name;
						}
						else {
							Component::error(Info::message('e_no_snippet', $markup), Code::Make, true);
							return Component::emulate();
						}
					}
				}

				self::$_snippet[$name] = $component;
				(new Exporter($snippet))->save(self::$_snippet[$name]);
				$cfg->root = 'Complex';
			}
		}

		return clone self::$_snippet[$name];
	}

	public static function open(string $name, array $vars=[]): Component {
		if (isset(self::$_snippet[$name])) {
			if (!empty($vars)) {
				self::prepare($name, $vars);
			}

			return clone self::$_snippet[$name];
		}

		if (Page::exists() && Page::child($name)) {
			$com = Page::open()->$name;

			if ($com instanceof Derivative) {
				self::$_snippet[$name] = $com->getOriginal();
			}
			else {
				self::$_snippet[$name] = clone $com;
				self::$_snippet[$name]->drop();

				if (self::$_snippet[$name] instanceof Wrapped) {
					self::$_snippet[$name]->unwrap();
				}
			}

			if (!empty($vars)) {
				self::prepare($name, $vars);
			}

			return clone self::$_snippet[$name];
		}

		Component::error(Info::message('e_no_snippet', $name), Code::Open, true);
		return Component::emulate();
	}
}
