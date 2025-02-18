<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Idle;

use Citron\Component;
use Citron\Composite;
use Citron\Performance;

abstract class Performer extends Composite {
	use Sequence;
	use Performance;

	protected string $_text;
	protected array  $_var;
	protected array  $_ref;

	public function __construct(array $state) {
		parent::__construct($state);
        $this->_text = $state['_text'];
        $this->_var  = $state['_var'];
        $this->_ref  = $state['_ref'];
	}

	public function __clone(): void {
		foreach (array_keys($this->_component) as $name) {
			$this->_component[$name] = clone $this->_component[$name];
		}
	}

	final public function __unset(string $name): void {
		if (isset($this->_component[$name])) {
			$this->_var[Component::NS.$name] = $this->_component[$name]->getResult();
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
			$this->_var[$name] = $component->getResult();
			$component->update();
		}
	}
}
