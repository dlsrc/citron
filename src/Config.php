<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Citron\Config\Brackets;
use Citron\Config\GlobalVariable;
use Citron\Config\LocalVariable;
use Citron\Config\Seed;
use Citron\Compilation\Collector;
use Citron\Main\Component;
use Ultra\Container\Getter;

final class Config extends Getter {
	final public const array WRAP_TAGS = [
		'div', 'article', 'section', 'nav', 'aside', 'hgroup',
		'header', 'footer', 'address', 'menu', 'main', 'pre',
	];

	final public const array TRUE_VALUE  = ['1', 'true', 'on', 'yes', 'ok'];
	final public const array FALSE_VALUE = ['0', 'false', 'off', 'no', 'none'];

	final public const array COMPOSITE_ROOT = [
		'Complex', 'Document', 'OriginalComposite', 'OriginalCompositeMap',
		'OriginalLeaf', 'OriginalLeafMap', 'OriginalText',
	];

	final public const array REFERENCE = ['&', '~', '#',];

	private const string CONFIG  = '/<!--\s*(~|\/|)config\s*\x7B(.+)\x7D\s*-->/Uis';
	private const string OPTIONS = '/\s*(\w+)\s*=\s*(\S+|(\x22|\x27|\x60)(.+)\g{3})\s*/Uis';

	protected function initialize(): void {
		$this->_property['root']            = self::COMPOSITE_ROOT[0];

		$this->_property['wrap_tag']        = self::WRAP_TAGS[0];
		$this->_property['wrap_class']      = 'wrap';

		$this->_property['local_variable']  = LocalVariable::Dot;
		$this->_property['local_brackets']  = Brackets::Curly;
		$this->_property['local_gaps']      = true;

		$this->_property['global_variable'] = GlobalVariable::Percent;
		$this->_property['global_brackets'] = Brackets::Curly;
		$this->_property['global_gaps']     = true;

		$this->_property['reference']       = self::REFERENCE[0];

		$this->_property['rtl']             = false;
	}

	public function __set(string $name, mixed $value): void {
		switch ($name) {
		case 'wrap_tag':
			if (in_array($value, self::WRAP_TAGS)) {
				$this->_property['wrap_tag'] = $value;
			}

			break;

		case 'wrap_class':
			if (preg_match('/^[^\W\d]([\w\.\-\s]*\w)?$/', $value)) {
				$this->_property['wrap_class'] = $value;
			}

			break;

		case 'local_gaps':
			if (in_array($value, self::TRUE_VALUE)) {
				$this->_property['local_gaps'] = true;
			}
			elseif (in_array($value, self::TRUE_VALUE)) {
				$this->_property['local_gaps'] = false;
			}

			break;

		case 'local_brackets':
			if ($b = Brackets::tryFrom($value)) {
				$this->_property['local_brackets'] = $b;
			}

			break;

		case 'local_variable':
			if ($v = LocalVariable::tryFrom($value)) {
				$this->_property['local_variable'] = $v;
			}

			break;

		case 'global_gaps':
			if (in_array($value, self::TRUE_VALUE)) {
				$this->_property['global_gaps'] = true;
			}
			elseif (in_array($value, self::TRUE_VALUE)) {
				$this->_property['global_gaps'] = false;
			}

			break;

		case 'global_brackets':
			if ($b = Brackets::tryFrom($value)) {
				$this->_property['global_brackets'] = $b;
			}
			
			break;

		case 'global_variable':
			if ($v = GlobalVariable::tryFrom($value)) {
				$this->_property['global_variable'] = $v;
			}

			break;
		
		case 'root':
			if (in_array($value, self::COMPOSITE_ROOT)) {
				$this->_property['root'] = $value;
			}

			break;

		case 'reference':
			if (in_array($value, self::REFERENCE)) {
				$this->_property['reference'] = $value;
			}
	
			break;
		}
	}

	public function isGlobalEqual(Config $builder): bool {
		if (
			$builder->global_gaps == $this->_property['global_gaps'] &&
			$builder->global_variable == $this->_property['global_variable'] &&
			$builder->global_brackets == $this->_property['global_brackets']
		) {
			return true;
		}

		return false;
	}

	public function isLocalEqual(Config $builder): bool {
		if (
			$builder->local_gaps == $this->_property['local_gaps'] &&
			$builder->local_variable == $this->_property['local_variable'] &&
			$builder->local_brackets == $this->_property['local_brackets']
		) {
			return true;
		}

		return false;
	}

	public function equalizeVariables(): void {
		$builder = self::get();
		
		if ($this === $builder) {
			return;
		}

		$this->_property['local_variable']  = $builder->local_variable;
		$this->_property['local_brackets']  = $builder->local_brackets;
		$this->_property['local_gaps']      = $builder->local_gaps;
		$this->_property['global_variable'] = $builder->global_variable;
		$this->_property['global_brackets'] = $builder->global_brackets;
		$this->_property['global_gaps']     = $builder->global_gaps;
	}

	public function patternGlobal(): string {
		$spaces   = $this->_property['global_gaps'] ? '\s*' : '';
		$start    = $this->_property['global_variable']->start();
		$brackets = $this->_property['global_brackets'];

		return '/(?:\x0A[\x09\x20]*)?'.$brackets->open().$spaces.$start.
		'(\pL[^\W_]?|\pL[\w\.]+[^\W_])(?:'.$spaces.'='.$spaces.'(?U)
		([^\s\x22\x27\x60'.$brackets->ignore().'][^'.$brackets->ignore().']*|(\x22|\x27|\x60)(.+)\g{3})
		(?-U))?'.$spaces.$brackets->close().'/xis';
	}

	public function patternLocal(bool $buider = true): string {
		$spaces   = $this->_property['local_gaps'] ? '\s*' : '';
		$start    = $this->_property['local_variable']->start();
		$brackets = $this->_property['local_brackets'];

		$main = self::get();

		if ($buider) {
			$start = '('.preg_quote(Component::NS, '/').'|'.preg_quote($main->reference, '/').'|'.$start.')';
			$b_ref = '4';
		}
		else {
			$b_ref = '3';
		}

		$g_spaces   = $main->global_gaps ? '\s*' : '';
		$g_start    = $main->global_variable->start();
		$g_brackets = $main->global_brackets;

		return '/(?:\x0A[\x09\x20]*)?'.$brackets->open().$spaces.$start.'(\pL[^\W_]?|\pL[\w\.]+[^\W_])
		(?:'.$spaces.'='.$spaces.'(?U)
			([^\s\x22\x27\x60'.$brackets->ignore().'][^'.$brackets->ignore().']* | (\x22|\x27|\x60)(.+)\g{'.$b_ref.'} | '.
			$g_brackets->open().$g_spaces.$g_start.'(\pL[^\W_]? | \pL\w+[^\W_])'.$g_spaces.$g_brackets->close().
		')(?-U))?'.$spaces.$brackets->close().'/xis';
	}

	public function patternLocalSet(): array {
		$spaces     = $this->_property['local_gaps'] ? '\s*' : '';
		$start      = $this->_property['local_variable']->start();
		$brackets   = $this->_property['local_brackets'];
		$g_spaces   = $this->_property['global_gaps'] ? '\s*' : '';
		$g_start    = $this->_property['global_variable']->start();
		$g_brackets = $this->_property['global_brackets'];

		return [
			'/'.$brackets->open().$spaces.$start,
			'(?:'.$spaces.'='.$spaces.'(?U)'.
				'([^\x22\x27\x60'.$brackets->ignore().'][^'.$brackets->ignore().']* | (\x22|\x27|\x60)(.+)\g{3} | '.
				$g_brackets->open().$g_spaces.$g_start.'(\pL[^\W_]? | \pL\w+[^\W_])'.$g_spaces.$g_brackets->close().
			')(?-U))?'.$spaces.$brackets->close().'/xis',
		];
	}

	public function viewLocal(string $name, string $value, bool $isglobal): string {
		$space = $this->_property['local_gaps'] ? ' ' : '';
		$build = $this->_property['local_brackets']->apart();
		$start = $this->_property['local_variable']->value;

		if ('' == $value) {
			return $build[0].$space.$start.$name.$space.$build[1];
		}

		if ($isglobal) {
			$main = self::get();
			$g_space = $main->global_gaps ? '\s*' : '';
			$g_build = $main->global_brackets->apart();
			$g_start = $main->global_variable->value;

			$value = $g_build[0].$g_space.$g_start.$value.$g_space.$g_build[1];
		}

		return $build[0].$space.$this->value.$name.$space.'='.$space.$value.$space.$build[1];
	}

	public function viewGlobal(string $name, string $value): string {
		$space = $this->_property['global_gaps'] ? ' ' : '';
		$build = $this->_property['global_brackets']->apart();
		$start = $this->_property['global_variable']->value;

		if ('' == $value) {
			return $build[0].$space.$start.$name.$space.$build[1];
		}

		return $build[0].$space.$start.$name.$space.'='.$space.$value.$space.$build[1];
	}

	public function setup(string &$template, Collector|null $c = null, bool $cut = true): Config {
		if (0 == preg_match(self::CONFIG, $template, $match)) {
			return $this->_selectSeed($c);
		}

		if ($cut) {
			$template = str_replace($match[0], '', $template);
		}

		if (0 == preg_match_all(self::OPTIONS, $match[2], $options, PREG_SET_ORDER)) {
			return $this->_selectSeed($c);
		}

		if ('' == $match[1] || null == $c) {
			// Клон конфигурации родительского шаблона
			$config = clone $this;
		}
		elseif ('~' == $match[1]) {
			// Клон конфигурации корневого шаблона
			$config = clone $c->getConfig();
		}
		else {
			// Клон глобальной конфигурации
			$config = clone self::get();
		}

		foreach ($options as $option) {
			if (isset($option[4])) {
				$option[2] = $option[4];
			}

			$config->__set($option[1], $option[2]);
		}

		return $config;
	}

	private function _selectSeed(Collector|null $c): Config {
		if (null == $c) {
			return $this;
		}

		return match (Seed::main()) {
			Seed::Node => $this,
			Seed::Root => $c->getConfig(),
			Seed::Main => self::get(),
		};
	}
}
