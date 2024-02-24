<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Main;

use Citron\Build;
use Citron\Config;

abstract class Builder {
	abstract protected function prepareStacks(): void;
	abstract protected function isTextComponent(int $id): bool;
	abstract protected function isMapComponent(int $id, string $prefix, bool $leaf): bool;
	abstract protected function buildOriginalComposite(int $i): void;
	abstract protected function buildWrappedOriginalComposite(int $i): void;
	abstract protected function buildFixedComposite(int $i): void;
	abstract protected function buildWrappedFixedComposite(int $i): void;
	abstract protected function buildOriginalLeaf(int $i): void;
	abstract protected function buildWrappedOriginalLeaf(int $i): void;
	abstract protected function buildFixedLeaf(int $i): void;
	abstract protected function buildWrappedFixedLeaf(int $i): void;
	abstract protected function buildComplex(int $i): void;
	abstract protected function buildDocument(int $i): void;

	protected Build $build;
	protected array $component;
	protected array $block;
	protected array $names;
	protected array $id;
	protected array $types;
	protected array $ref;
	protected array $child;
	protected array $globs;
	protected array $before;
	protected array $after;

	public static function get(): Builder {
		$build = Build::main();
		$class = $build->builder();
		return new $class($build);
	}

	protected function __construct(Build $build) {
		$this->build = $build;
		$namespace = $this->build->ns();

		$this->component = [
			'complex'     => $namespace.'\\Complex',
			'document'    => $namespace.'\\Document',
			'a_comp'      => $namespace.'\\OriginalComposite',
			'a_comp_map'  => $namespace.'\\OriginalCompositeMap',
			'f_comp'      => $namespace.'\\FixedComposite',
			'f_comp_map'  => $namespace.'\\FixedCompositeMap',
			'wa_comp'     => $namespace.'\\WrappedOriginalComposite',
			'wa_comp_map' => $namespace.'\\WrappedOriginalCompositeMap',
			'wf_comp'     => $namespace.'\\WrappedFixedComposite',
			'wf_comp_map' => $namespace.'\\WrappedFixedCompositeMap',
			'a_leaf'      => $namespace.'\\OriginalLeaf',
			'a_leaf_map'  => $namespace.'\\OriginalLeafMap',
			'f_leaf'      => $namespace.'\\FixedLeaf',
			'f_leaf_map'  => $namespace.'\\FixedLeafMap',
			'wa_leaf'     => $namespace.'\\WrappedOriginalLeaf',
			'wa_leaf_map' => $namespace.'\\WrappedOriginalLeafMap',
			'wf_leaf'     => $namespace.'\\WrappedFixedLeaf',
			'wf_leaf_map' => $namespace.'\\WrappedFixedLeafMap',
			'a_text'      => namespace\OriginalText::class,
			'f_text'      => namespace\FixedText::class,
			'wa_text'     => namespace\WrappedOriginalText::class,
			'wf_text'     => namespace\WrappedFixedText::class,
			'variator'    => namespace\Variator::class,
			'w_variator'  => namespace\WrappedVariator::class,
		];

		$this->block  = [];
		$this->names  = [];
		$this->id     = [];
		$this->types  = [];
		$this->ref    = [];
		$this->child  = [];
		$this->globs  = [];
		$this->before = [];
		$this->after  = [];
	}

	public function create(string $filename): Component {
		if (!$tpl = file_get_contents($file)) {
			return Component::emulate();
		}

		return $this->build($tpl);
	}

	public function build(string $tpl): Component {
		$this->block[0] = $tpl;

		if ('' == $this->block[0]) {
			return Component::emulate();
		}

		$this->types[]  = $this->build->ns().'\\'.Config::get()->root;
		$this->names[]  = 'ROOT';
		$this->id[]     = 'ROOT';
		$this->before[] = '';
		$this->after[]  = '';

		$this->prepareGlobalVars();
		$this->prepareDependencies();
		$this->prepareStacks();
		$this->prepareComponents();

		return $this->block[0];
	}

	protected function prepareGlobalVars(): void {
		$cfg = Config::get();

		if (0 == preg_match_all($cfg->patternGlobal(), $this->block[0], $matches, PREG_SET_ORDER)) {
			return;
		}

		[$open, $close] = $cfg->global_brackets->apart();  // '{'   '}'
		$open.= $cfg->global_variable->value;              // '{%'  '}'

		foreach ($matches as $match) {
			if (isset($match[4])) {
				$match[2] = $match[4];
			}

			$match[1] = $open.$match[1].$close;

			if (!isset($this->globs[$match[1]])) {
				$this->globs[$match[1]] = $match[2]??'';
			}
			elseif (isset($match[2]) && '' == $this->globs[$match[1]]) {
				$this->globs[$match[1]] = $match[2];
			}

			if ($match[0] != $match[1]) {
				$this->block[0] = str_replace(
					$match[0],
					$match[1],
					$this->block[0]
				);
			}
		}
	}

	public static function extractComplexString(string &$attrs): array|null {
		if (!str_contains($attrs, '"')) {
			return null;
		}
	
		$complex = [];
	
		if (preg_match_all('/"([^"]*)"/U', $attrs, $match, PREG_PATTERN_ORDER)) {
			foreach (array_keys($match[1]) as $i) {
				$key = '%%'.$i.'%%';
				$complex[$key] = $match[1][$i];
				$attrs = str_replace($match[0][$i], $key, $attrs);
			}
		}
	
		return $complex;
	}

	private static function _getWrap(string $src, string $tag, string $class): array {
		$complex = self::extractComplexString($src);
		$src = preg_replace('/\s*\=\s*/', '=', trim($src));
	
		if ('' == $src) {
			return ['<'.$tag.'>', '</'.$tag.'>'];
		}
	
		$attrs = preg_split('/\s+/', $src);
	
		if (in_array($attrs[0], Config::WRAP_TAGS)) {
			$wrap_tag = $attrs[0];
			unset($attrs[0]);
		}
		else {
			$wrap_tag = $tag;
		}
	
		if (empty($attrs)) {
			return ['<'.$wrap_tag.'>', '</'.$wrap_tag.'>'];
		}
	
		$wrap_attr = [];
	
		foreach ($attrs as $attr) {
			if ('.' == $attr || '+' == $attr) {
				$wrap_attr['class'][] = $class;
				continue;
			}
	
			if (!str_contains($attr, '=')) {
				if (str_starts_with($attr, '.') || str_starts_with($attr, '+')) {
					$attr  = substr($attr, 1);
	
					if (isset($complex[$attr])) {
						$wrap_attr['class'][] = $complex[$attr];
					}
					elseif ($attr == $class) {
						$wrap_attr['class'][] = $attr;
					}
					else {
						$wrap_attr['class'][] = $attr;
					}
				}
				elseif (str_starts_with($attr, '#')) {
					$attr  = substr($attr, 1);
	
					if (isset($complex[$attr])) {
						$wrap_attr['id'] = 'id="'.$complex[$attr].'"';
					}
					else {
						$wrap_attr['id'] = 'id="'.$attr.'"';
					}
				}
				elseif (str_starts_with($attr, '@')) {
					$attr  = substr($attr, 1);
	
					if (isset($complex[$attr])) {
						$wrap_attr['style'] = 'style="'.$complex[$attr].'"';
					}
					else {
						$wrap_attr['style'] = 'style="'.$attr.'"';
					}
				}
				elseif (preg_match('/^[\w\-]+$/', $attr)) {
					$wrap_attr[$attr] = $attr;
				}
	
				continue;
			}
	
			$a = explode('=', $attr);
	
			if ('class' == $a[0]) {
				if (isset($complex[$a[1]])) {
					$wrap_attr['class'][] = $complex[$a[1]];
				}
				elseif ($class == $a[1]) {
					$wrap_attr['class'][] = $a[1];
				}
				else {
					$wrap_attr['class'][] = $a[1];
				}
			}
			elseif ('id' == $a[0]) {
				if (isset($complex[$a[1]])) {
					$wrap_attr['id'] = 'id="'.$complex[$a[1]].'"';
				}
				else {
					$wrap_attr['id'] = 'id="'.$a[1].'"';
				}
			}
			elseif ('style' == $a[0]) {
				if (isset($complex[$a[1]])) {
					$wrap_attr['style'] = 'style="'.$complex[$a[1]].'"';
				}
				else {
					$wrap_attr['style'] = 'style="'.$a[1].'"';
				}
			}
			elseif (isset($complex[$a[1]])) {
				$wrap_attr[$a[0]] = $a[0].'="'.$complex[$a[1]].'"';
			}
			else {
				$wrap_attr[$a[0]] = $a[0].'="'.$a[1].'"';
			} 
		}
	
		if (empty($wrap_attr)) {
			return ['<'.$wrap_tag.'>', '</'.$wrap_tag.'>'];
		}
	
		if (isset($wrap_attr['class'][0])) {
			if (count($wrap_attr['class']) > 1) {
				$wrap_attr['class'] = 'class="'.implode(' ', $wrap_attr['class']).'"';
			}
			else {
				$wrap_attr['class'] = 'class="'.$wrap_attr['class'][0].'"';
			}
		}
	
		return ['<'.$wrap_tag.' '.implode(' ', $wrap_attr).'>', '</'.$wrap_tag.'>'];
	}

	protected function prepareDependencies(): void {
		$cfg   = Config::get();
		$tag   = $cfg->wrap_tag;
		$class = $cfg->wrap_class;

		$pattern = '/(\x0A[\x09\x20]*)?
		<!--\s*(!|\^|)(\p{Lu}\w*)(?:\s*\:\s*(\p{Lu}\w*))?(\s*<([^>]*)>)?\s*-->
		(.+)<!--\s*~\g{3}\s*-->/Uxs';

		$k = 1;

		for ($i = 0; isset($this->block[$i]); $i++) {
			if (0 == preg_match_all($pattern, $this->block[$i], $matches, PREG_SET_ORDER)) {
				continue;
			}

			foreach ($matches as $match) {
				$this->block[$k] = rtrim($match[7]);
				$this->names[$k] = $match[3];

				if (isset($match[5]) && '' != $match[5]) {
					[$this->before[$k], $this->after[$k]] = self::_getWrap($match[6], $tag, $class);
					$this->before[$k] = $match[1].$this->before[$k];
					$this->after[$k]  = $match[1].$this->after[$k];
				}
				else {
					$this->before[$k] = '';
					$this->after[$k]  = '';
				}

				if ('' == $match[4]) {
					$this->id[$k] = $match[3];
				}
				else {
					$this->id[$k] = $match[4];
				}
				
				if ('' == $this->before[$k]) {
					if ('^' == $match[2]) {
						$this->types[$k] = $this->component['variator'];
					}
					elseif ('!' == $match[2]) {
						$this->types[$k] = $this->component['f_comp'];
					}
					else {
						$this->types[$k] = $this->component['a_comp'];
					}
				}
				else {
					if ('^' == $match[2]) {
						$this->types[$k] = $this->component['w_variator'];
					}
					elseif ('!' == $match[2]) {
						$this->types[$k] = $this->component['wf_comp'];
					}
					else {
						$this->types[$k] = $this->component['wa_comp'];
					}
				}

				$this->block[$i] = str_replace($match[0], '{'.Component::NS.$match[3].'}', $this->block[$i]);
				$this->child[$i][] = $k;
				$k++;
			}
		}
	}

	protected function identifyType(int $id, string $prefix): void {
		if ($leaf = !isset($this->child[$id][0])) {
			if ($this->isTextComponent($id)) {
				$comp = $prefix.'_text';
				$this->types[$id] = $this->component[$comp];
				return;
			}
		}

		if (!$this->isMapComponent($id, $prefix, $leaf) && $leaf) {
			$comp = $prefix.'_leaf';
			$this->types[$id] = $this->component[$comp];
		}
	}

	protected function getComposition(int $i): array {
		$component = [];

		foreach ($this->child[$i] as $id) {
			$name = $this->block[$id]->getName();
			$component[$name] = $this->block[$id];
		}

		return $component;
	}

	protected function prepareComponents(): void {
		for ($i = array_key_last($this->types); $i >= 0; $i--) {
			switch ($this->types[$i]) {
			case $this->component['a_comp']:
				$this->identifyType($i, 'a');
				break;

			case $this->component['wa_comp']:
				$this->identifyType($i, 'wa');
				break;

			case $this->component['f_comp']:
				$this->identifyType($i, 'f');
				break;

			case $this->component['wf_comp']:
				$this->identifyType($i, 'wf');
				break;

			case $this->component['complex']:
				if (!isset($this->child[$i][0])) {
					$this->types[$i] = $this->component['document'];
				}

				break;
			}

			switch ($this->types[$i]) {
			case $this->component['a_comp']:
			case $this->component['a_comp_map']:
				$this->buildOriginalComposite($i);
				break;

			case $this->component['wa_comp']:
			case $this->component['wa_comp_map']:
				$this->buildWrappedOriginalComposite($i);
				break;

			case $this->component['f_comp']:
			case $this->component['f_comp_map']:
				$this->buildFixedComposite($i);
				break;

			case $this->component['wf_comp']:
			case $this->component['wf_comp_map']:
				$this->buildWrappedFixedComposite($i);
				break;

			case $this->component['a_leaf']:
			case $this->component['a_leaf_map']:
				$this->buildOriginalLeaf($i);
				break;

			case $this->component['wa_leaf']:
			case $this->component['wa_leaf_map']:
				$this->buildWrappedOriginalLeaf($i);
				break;

			case $this->component['f_leaf']:
			case $this->component['f_leaf_map']:
				$this->buildFixedLeaf($i);
				break;

			case $this->component['wf_leaf']:
			case $this->component['wf_leaf_map']:
				$this->buildWrappedFixedLeaf($i);
				break;

			case $this->component['a_text']:
				$this->block[$i] = new $this->types[$i]([
					'_text'  => $this->block[$i],
					'_class' => $this->id[$i],
					'_name'  => $this->names[$i],
				]);
				break;

			case $this->component['wa_text']:
				$this->block[$i] = new $this->types[$i]([
					'_text'   => $this->block[$i],
					'_class'  => $this->id[$i],
					'_name'   => $this->names[$i],
					'_before' => $this->before[$i],
					'_after'  => $this->after[$i],
				]);
				break;

			case $this->component['f_text']:
				$this->block[$i] = new $this->types[$i]([
					'_text'  => $this->block[$i],
					'_class' => $this->id[$i],
					'_name'  => $this->names[$i],
					'_exert' => false,
				]);
				break;

			case $this->component['wf_text']:
				$this->block[$i] = new $this->types[$i]([
					'_text'   => $this->block[$i],
					'_class'  => $this->id[$i],
					'_name'   => $this->names[$i],
					'_before' => $this->before[$i],
					'_after'  => $this->after[$i],
					'_exert'  => false,
				]);
				break;

			case $this->component['variator']:
				if (isset($this->child[$i][0])) {
					$this->block[$i] = new $this->types[$i]([
						'_class'     => $this->id[$i],
						'_name'      => $this->names[$i],
						'_component' => $this->getComposition($i),
						'_variant'   => $this->names[$this->child[$i][0]],
					]);
				}
				else {
					$this->block[$i] = Component::emulate();
				}
				break;

			case $this->component['w_variator']:
				if (isset($this->child[$i][0])) {
					$this->block[$i] = new $this->types[$i]([
						'_class'     => $this->id[$i],
						'_name'      => $this->names[$i],
						'_before'    => $this->before[$i],
						'_after'     => $this->after[$i],
						'_component' => $this->getComposition($i),
						'_variant'   => $this->names[$this->child[$i][0]],
					]);
				}
				else {
					$this->block[$i] = Component::emulate();
				}
				break;

			case $this->component['complex']:
				$this->buildComplex($i);
				break;

			case $this->component['document']:
				$this->buildDocument($i);
				break;

			default:
				$this->block[$i] = Component::emulate();
			}
		}
	}
}
