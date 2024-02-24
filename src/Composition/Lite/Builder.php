<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Lite;

use Citron\Build;
use Citron\Config;
use Citron\Main\Builder as Main;
use Citron\Main\Component;

final class Builder extends Main {
	protected array $stack;

	protected function __construct(Build $build) {
		parent::__construct($build);
		$this->stack  = [];
	}

	protected function prepareStacks(): void {
		$cfg = Config::get();
		$pattern = $cfg->patternLocal();
		$refns = $cfg->reference;

		[$open, $close] = $cfg->global_brackets->apart();
		$open = $open.$cfg->global_variable->value;

		foreach (array_keys($this->block) as $i) {
			$key = 0;
			$this->ref[$i] = [];

			if (0 == preg_match_all($pattern, $this->block[$i], $matches, PREG_SET_ORDER)) {
				continue;
			}

			$split = preg_split($pattern, $this->block[$i]);

			foreach ($matches as $id => $match) {
				if ('' != trim($split[$id])) {
					$this->stack[$i][$key] = $split[$id];
					$key++;
				}

				if (isset($match[6])) {
					$match[3] = $open.$match[6].$close;
				}
				elseif (isset($match[5])) {
					$match[3] = $match[5];
				}

				if ($refns == $match[1] || Component::NS == $match[1]) {
					$match[2] = Component::NS.$match[2];
				}

				if (!isset($this->stack[$i][$match[2]])) {
					$this->stack[$i][$match[2]] = $match[3]??'';
				}
				else {
					if (isset($match[3]) && '' == $this->stack[$i][$match[2]]) {
						$this->stack[$i][$match[2]] = $match[3];
					}

					$this->stack[$i][$key] = $this->stack[$i][$match[2]];
					$this->ref[$i][$key] = $match[2];
					$key++;
				}
			}

			$id++;

			if ('' != trim($split[$id])) {
				$this->stack[$i][$key] = $split[$id];
			}
		}
	}

	protected function isTextComponent(int $id): bool {
		return !isset($this->stack[$id]);
	}

	protected function isMapComponent(int $id, string $prefix, bool $leaf): bool {
		foreach (array_keys($this->stack[$id]) as $var) {
			if (is_string($var) && str_contains($var, Component::NS) && Component::NS != $var[0]) {
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
			'_chain'     => $this->stack[$i],
			'_ref'       => $this->ref[$i],
			'_class'     => $this->id[$i],
			'_name'      => $this->names[$i],
			'_component' => $this->getComposition($i),
		]);
	}

	protected function buildWrappedOriginalComposite(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_chain'     => $this->stack[$i],
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
			'_chain'     => $this->stack[$i],
			'_ref'       => $this->ref[$i],
			'_class'     => $this->id[$i],
			'_name'      => $this->names[$i],
			'_component' => $this->getComposition($i),
			'_exert'     => false,
		]);
	}

	protected function buildWrappedFixedComposite(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_chain'     => $this->stack[$i],
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
			'_chain'  => $this->stack[$i],
			'_ref'    => $this->ref[$i],
			'_class'  => $this->id[$i],
			'_name'   => $this->names[$i],
		]);
	}

	protected function buildWrappedOriginalLeaf(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_chain'  => $this->stack[$i],
			'_ref'    => $this->ref[$i],
			'_class'  => $this->id[$i],
			'_name'   => $this->names[$i],
			'_before' => $this->before[$i],
			'_after'  => $this->after[$i],
		]);
	}

	protected function buildFixedLeaf(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_chain'  => $this->stack[$i],
			'_ref'    => $this->ref[$i],
			'_class'  => $this->id[$i],
			'_name'   => $this->names[$i],
			'_exert'  => false,
		]);
	}

	protected function buildWrappedFixedLeaf(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_chain'  => $this->stack[$i],
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
			'_chain'     => $this->stack[$i],
			'_ref'       => $this->ref[$i],
			'_class'     => $this->id[$i],
			'_name'      => $this->names[$i],
			'_component' => $this->getComposition($i),
			'_global'    => $this->globs,
			]);
	}

	protected function buildDocument(int $i): void {
		$this->block[$i] = new $this->types[$i]([
			'_chain'  => $this->stack[$i],
			'_ref'    => $this->ref[$i],
			'_class'  => $this->id[$i],
			'_name'   => $this->names[$i],
		]);
	}
}
