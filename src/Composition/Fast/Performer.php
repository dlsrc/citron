<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Fast;

use Citron\Main\Component;
use Citron\Main\Composite;
use Citron\Main\Performance;

abstract class Performer extends Composite {
	use Sequence;
	use Performance;

	protected array $_var;
	protected array $_ref;
	protected array $_child;
	protected array $_chain;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_var   = $state['_var'];
		$this->_ref   = $state['_ref'];
		$this->_child = $state['_child'];
		$this->_chain = $state['_chain'];

		foreach ($this->_ref as $i => $name) {
			$this->_chain[$i] =&$this->_var[$name];
		}

		foreach ($this->_child as $k => $v) {
			$this->_chain[$k] =&$this->_chain[$v];
		}
	}

	public function __clone(): void {
		foreach (array_keys($this->_component) as $name) {
			$this->_component[$name] = clone $this->_component[$name];
		}

		$clone = [];

		foreach ($this->_var as $name => $value) {
			$clone[$name] = $value;
		}

		$this->_var = $clone;

		foreach ($this->_ref as $i => $name) {
			$this->_chain[$i] =&$this->_var[$name];
		}
	}

	final public function __unset(string $name): void {
		if (isset($this->_component[$name])) {
			$this->_chain[Component::NS.$name] = $this->_component[$name]->getResult();
			unset($this->_component[$name]);
		}
	}

	final public function common(string $name, int|float|string $value): void {
		$this->_var[$name] = $value;

		foreach ($this->_component as $component) {
			$component->common($name, $value);
		}
	}

	final protected function notify(): void {
		foreach ($this->_component as $component) {
			$name = Component::NS.$component->getName();
			$this->_chain[$name] = $component->getResult();
			$component->update();
		}
	}
}
