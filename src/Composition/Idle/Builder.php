<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Idle;

use Citron\Build;
use Citron\Builder as General;
use Citron\Config;
use Citron\Component;

final class Builder extends General {
	private array $var;

	protected function prepareStacks(): void {
		$cfg = Config::get();

		[$global_open, $global_close] = $cfg->global_brackets->apart();
		$global_open = $global_open.$cfg->global_variable->value;

		[$open, $close] = $cfg->local_brackets->apart();
		$start = $cfg->local_variable->value;

		$pattern = $cfg->patternLocal();
		$refns = $cfg->reference;
		$comns = Component::NS;

		foreach (array_keys($this->block) as $i) {
			if (0 == preg_match_all($pattern, $this->block[$i], $matches, PREG_SET_ORDER)) {
				continue;
			}

			$this->ref[$i] = [];
			$this->var[$i] = [];
			$search  = [];
			$replace = [];

			foreach ($matches as $match) {
				if (isset($match[6])) {
					$match[3] = $global_open.$match[6].$global_close;
				}
				elseif (isset($match[5])) {
					$match[3] = $match[5];
				}

				if ($refns == $match[1] || $comns == $match[1]) {
					$match[2] = $comns.$match[2];
					$this->var[$i][$match[2]] = '';
					$this->ref[$i][$match[2]] = $open.$comns.$match[2].$close;
				}
				elseif (!isset($this->var[$i][$match[2]])) {
					$this->var[$i][$match[2]] = $match[3]??'';
					$this->ref[$i][$match[2]] = $open.$start.$match[2].$close;
				}
				elseif (isset($match[3]) && '' == $this->var[$i][$match[2]]) {
					$this->var[$i][$match[2]] = $match[3];
				}

				if ($match[0] != $this->ref[$i][$match[2]]) {
					$search[]  = $match[0];
					$replace[] = $this->ref[$i][$match[2]];
				}
			}

			if (!empty($search)) {
				$this->block[$i] = str_replace($search, $replace, $this->block[$i]);
			}
		}
	}

	protected function isTextComponent(int $id): bool {
		return !isset($this->ref[$id]);
	}

	protected function isMapComponent(int $id, string $prefix, bool $leaf): bool {
		foreach (array_keys($this->var[$id]) as $name) {
			if (str_starts_with($name, Component::NS)) {
				if ($leaf) {
					$comp = $prefix.'_leaf_map';
				}
				else {
					$comp = $prefix.'_comp_map';
				}

				$this->types[$id] = $this->component[$comp];
				return true;
			}
		}

		return false;
	}

	protected function buildOriginalComposite(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_text'      => $this->block[$i],
			'_var'       => $this->var[$i],
			'_ref'       => $this->ref[$i],
			'_class'     => $this->id[$i],
			'_name'      => $this->names[$i],
			'_component' => $this->getComposition($i),
		]);
	}

	protected function buildWrappedOriginalComposite(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_text'      => $this->block[$i],
			'_var'       => $this->var[$i],
			'_ref'       => $this->ref[$i],
			'_class'     => $this->id[$i],
			'_name'      => $this->names[$i],
			'_before'    => $this->before[$i],
			'_after'     => $this->after[$i],
			'_component' => $this->getComposition($i),
		]);
	}

	protected function buildFixedComposite(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_text'      => $this->block[$i],
			'_var'       => $this->var[$i],
			'_ref'       => $this->ref[$i],
			'_class'     => $this->id[$i],
			'_name'      => $this->names[$i],
			'_component' => $this->getComposition($i),
			'_exert'     => false,
		]);
	}

	protected function buildWrappedFixedComposite(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_text'      => $this->block[$i],
			'_var'       => $this->var[$i],
			'_ref'       => $this->ref[$i],
			'_class'     => $this->id[$i],
			'_name'      => $this->names[$i],
			'_before'    => $this->before[$i],
			'_after'     => $this->after[$i],
			'_component' => $this->getComposition($i),
			'_exert'     => false,
		]);
	}

	protected function buildOriginalLeaf(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_text'  => $this->block[$i],
			'_var'   => $this->var[$i],
			'_ref'   => $this->ref[$i],
			'_class' => $this->id[$i],
			'_name'  => $this->names[$i],
		]);
	}

	protected function buildWrappedOriginalLeaf(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_text'   => $this->block[$i],
			'_var'    => $this->var[$i],
			'_ref'    => $this->ref[$i],
			'_class'  => $this->id[$i],
			'_name'   => $this->names[$i],
			'_before' => $this->before[$i],
			'_after'  => $this->after[$i],
		]);
	}

	protected function buildFixedLeaf(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_text'  => $this->block[$i],
			'_var'   => $this->var[$i],
			'_ref'   => $this->ref[$i],
			'_class' => $this->id[$i],
			'_name'  => $this->names[$i],
			'_exert' => false,
		]);
	}

	protected function buildWrappedFixedLeaf(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_text'   => $this->block[$i],
			'_var'    => $this->var[$i],
			'_ref'    => $this->ref[$i],
			'_class'  => $this->id[$i],
			'_name'   => $this->names[$i],
			'_before' => $this->before[$i],
			'_after'  => $this->after[$i],
			'_exert'  => false,
		]);
	}

	protected function buildComplex(int $i): void {
		$cfg = Config::get();
		$this->block[$i] = new $this->types[$i]([
			'_text'      => $this->block[$i],
			'_var'       => $this->var[$i],
			'_ref'       => $this->ref[$i],
			'_class'     => $this->id[$i],
			'_name'      => $this->names[$i],
			'_component' => $this->getComposition($i),
			'_global'    => $this->globs,
			]);
	}

	protected function buildDocument(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_text'   => $this->block[$i],
			'_var'    => $this->var[$i],
			'_ref'    => $this->ref[$i],
			'_class'  => $this->id[$i],
			'_name'   => $this->names[$i],
		]);
	}
}
