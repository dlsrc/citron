<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Compilation;

use Citron\Config;
use Citron\Builder;

class Template extends Unit {
	private const string COMPONENT = '/^([\x09\x20]*)\[\s*
	(!|@|!@|@!|)(\p{Lu}\w*)
	(?:(?:\s*\:\s*(\p{Lu}\w*))?\s*\=\s*(@|)(\p{Lu}\w*))?
	(?:\s*(\(([^>]*)\)))?
	(?:\s*\{\s*(.+)\s*\})?
	\s*\]/Usimx';

	public function __construct(
		string $content,
		string $filename,
		int $config,
		public readonly string $indent = '',
		public readonly int|null $parent = null,
		public readonly string $search = '',
	) {
		parent::__construct($content, $filename, $config);
	}

	public function replaceComponent(string $search, string $replace): void {
		$this->content = str_replace($search, $replace, $this->content);
	}

	public function createComponents(Collector $c, int $template): array {
		if (0 == preg_match_all(self::COMPONENT, $this->content, $matches, PREG_SET_ORDER)) {
			return [];
		}

		$repeat = [];
//		$cfg = Config::get();
		$cfg = $c->getConfig($this->config);

		$wrap_tag   = $cfg->wrap_tag;
		$wrap_class = $cfg->wrap_class;

		foreach($matches as $match) {
			$component = [];

			$component['template'] = $template;
			$component['search'] = $match[0];
			$component['name'] = $match[3];

			if ($c->isComponent($component['name'])) {
				for ($i = 2; $c->isComponent($component['name'].$i); $i++);
				$component['name'].= $i;
			}

			$attr = [];

			$component['indent'] = $match[1];

			$component['fixed'] = match ($match[2]) {
				'!', '!@', '@!' => true,
				default => false,
			};
		
			$component['asis'] = match ($match[2]) {
				'@', '!@', '@!' => true,
				default => false,
			};
		
			if (!$component['asis'] && isset($match[5]) && '@' == $match[5]) {
				$component['asis'] = true;
			}
		
			if (isset($match[4]) && '' != $match[4]) {
				$component['type'] = $match[4];
			}
			else {
				$component['type'] = '';
			}
		
			if (isset($match[7]) && '' != $match[7]) {
				$component['wrap'] = self::_getWrap($match[8], $wrap_tag, $wrap_class);
			}
			else {
				$component['wrap'] = '';
			}

			if (isset($match[9])) {
				$component['tuning'] = $match[9];
			}
			else {
				$component['tuning'] = '';
			}

			if (isset($match[6]) && '' != $match[6]) {
				$component['snippet'] = $match[6];
				$component['repeat'] = true;
			}
			else {
				$component['snippet'] = $match[3];
				$component['repeat'] = false;
			}

			if (!$this->createComponent($c, $component)) {
				$repeat[] = $component;
			}
		}

		return $repeat;
	}

	public function createComponent(Collector $c, array $component): bool {
		if ($c->isSnippet($component['snippet'])) {
			if ('' == $component['type']) {
				$component['type'] = $c->getSnippet($component['snippet'])->type;
			}

			$c->addComponent(new Component(
				name:     $component['name'],
				type:     $component['type'],
				snippet:  $component['snippet'],
				wrap:     $component['wrap'],
				indent:   $component['indent'],
				tuning:   $component['tuning'],
				asis:     $component['asis'],
				fixed:    $component['fixed'],
				template: $component['template'],
				search:   $component['search'],
			));

			return true;
		}
		
		if ($component['repeat']) {
			return self::repeatComponent($c, $component);
		}

		return false;
	}

	public static function repeatComponent(Collector $c, array $component): bool {
		if (!$com = $c->getComponent($component['snippet'])) {
			return false;
		}

		$component['snippet'] = $com->snippet;

		if ('' == $component['type']) {
			$component['type'] = $com->type;
		}

		if ($com->asis) {
			$component['asis'] = true;
		}

		if ($com->fixed) {
			$component['fixed'] = true;
		}

		if ('' == $component['wrap']) {
			$component['wrap'] = $com->wrap;
		}

		if ('' != $component['tuning']) {
			$com_tuning = trim($com->tuning);

			if (str_ends_with($com_tuning, ';')) {
				$component['tuning'] = $com_tuning.' / '.$component['tuning'];
			}
			else {
				$component['tuning'] = $com_tuning.'; / '.$component['tuning'];
			}
		}
		elseif ('' != $com->tuning) {
			$component['tuning'] = $com->tuning;
		}

		$c->addComponent(new Component(
			name:     $component['name'],
			type:     $component['type'],
			snippet:  $component['snippet'],
			wrap:     $component['wrap'],
			indent:   $component['indent'],
			tuning:   $component['tuning'],
			asis:     $component['asis'],
			fixed:    $component['fixed'],
			template: $component['template'],
			search:   $component['search'],
		));

		return true;
	}

	public function isNotParent(): bool {
		return isset($this->parent);
	}

	public function includeSub(self $sub): void {
		$this->content = str_replace(
			$sub->search,
			$sub->indent.str_replace("\n", "\n".$sub->indent, trim($sub->content)),
			$this->content
		);
	}

	public function dropImportDirective(array $directive): void {
		$this->content = str_replace($directive, '', $this->content);
	}

	public function normalizeContent() {
		$this->content = $this->indent.str_replace("\n", $this->indent, trim($this->content));
	}

	private static function _getWrap(string $src, string $tag, string $class): string {
		$complex = Builder::extractComplexString($src);
		$src = preg_replace('/\s*\=\s*/', '=', trim($src));
	
		if ('' == $src) {
			return '<>';
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
			if ($wrap_tag == $tag) {
				return '<>';
			}
	
			return '<'.$wrap_tag.'>';
		}

		$wrap_src = [];

		foreach ($attrs as $attr) {
			if ('.' == $attr || '+' == $attr) {
				$wrap_src['class'][]  = $attr;
				continue;
			}

			if (!str_contains($attr, '=')) {
				if (str_starts_with($attr, '.') || str_starts_with($attr, '+')) {
					$attr  = substr($attr, 1);
	
					if (isset($complex[$attr])) {
						$wrap_src['class'][]  = '+"'.$complex[$attr].'"';
					}
					elseif ($attr == $class) {
						$wrap_src['class'][]  = '+';
					}
					else {
						$wrap_src['class'][]  = '+'.$attr;
					}
				}
				elseif (str_starts_with($attr, '#')) {
					$attr  = substr($attr, 1);
	
					if (isset($complex[$attr])) {
						$wrap_src['id']  = '#"'.$complex[$attr].'"';
					}
					else {
						$wrap_src['id']  = '#'.$attr;
					}
				}
				elseif (str_starts_with($attr, '@')) {
					$attr  = substr($attr, 1);
	
					if (isset($complex[$attr])) {
						$wrap_src['style']  = '@"'.$complex[$attr].'"';
					}
					else {
						$wrap_src['style']  = '@'.$attr;
					}
				}
				elseif (preg_match('/^[\w\-]+$/', $attr)) {
					$wrap_src[$attr]  = $attr;
				}
	
				continue;
			}
	
			$a = explode('=', $attr);
	
			if ('class' == $a[0]) {
				if (isset($complex[$a[1]])) {
					$wrap_src['class'][]  = '+"'.$complex[$a[1]].'"';
				}
				elseif ($class == $a[1]) {
					$wrap_src['class'][]  = '+';
				}
				else {
					$wrap_src['class'][]  = '+'.$a[1];
				}
			}
			elseif ('id' == $a[0]) {
				if (isset($complex[$a[1]])) {
					$wrap_src['id']  = '#"'.$complex[$a[1]].'"';
				}
				else {
					$wrap_src['id']  = '#'.$a[1];
				}
			}
			elseif ('style' == $a[0]) {
				if (isset($complex[$a[1]])) {
					$wrap_src['style']  = '@"'.$complex[$a[1]].'"';
				}
				else {
					$wrap_src['style']  = '@'.$a[1];
				}
			}
			elseif (isset($complex[$a[1]])) {
				$wrap_src[$a[0]]  = $a[0].' = "'.$complex[$a[1]].'"';
			}
			else {
				$wrap_src[$a[0]]  = $a[0].' = '.$a[1];
			} 
		}
	
		if (empty($wrap_src)) {
			if ($wrap_tag == $tag) {
				return '<>';
			}
	
			return '<'.$wrap_tag.'>';
		}
	
		if (isset($wrap_src['class'][0])) {
			if (count($wrap_src['class']) > 1) {
				$wrap_src['class']  = implode(' ', $wrap_src['class']);
			}
			else {
				$wrap_src['class']  = $wrap_src['class'][0];
			}
		}
	
		if ($wrap_tag == $tag) {
			return '<'.implode(' ', $wrap_src).'>';
		}
		else {
			return '<'.$wrap_tag.' '.implode(' ', $wrap_src).'>';
		}
	}
}
