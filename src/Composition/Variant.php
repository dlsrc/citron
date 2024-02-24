<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Main;

use Citron\Code;
use Citron\Info;

abstract class Variant extends Composite {
	protected string $_variant;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_variant = $state['_variant'];
	}

	final public function __clone(): void {
		foreach (array_keys($this->_component) as $name) {
			$this->_component[$name] = clone $this->_component[$name];
		}
	}

	final public function __invoke(array $data, array $order=[]): void {
		$this->_component[$this->_variant]($data, $order);
	}

	final public function __call(string $name, array $data): bool {
		if (!isset($this->_component[$name])) {
			Component::error(Info::message('e_no_child', $name), Code::Component);
			return false;
		}

		$this->_variant = $name;

		if (isset($data[1])) {
			$this->_component[$name]($data[0], $data[1]);
		}
		elseif (isset($data[0])) {
			$this->_component[$name]($data[0]);
		}

		return true;
	}

	final public function __get(string $name): Component {
		if (isset($this->_component[$name])) {
			$this->_variant = $name;
			return $this->_component[$name];
		}

		Component::error(Info::message('e_no_child', $name), Code::Component);
		return Component::emulate();
	}

	final public function __unset(string $name): void {
		unset($this->_component[$name]);
	}

	final public function __set(string $name, int|float|string|array $value): void {
		$this->_component[$this->_variant]->$name = $value;
	}

	final public function common(string $name, int|float|string $value): void {
		foreach ($this->_component as $component) {
			$component->common($name, $value);
		}
	}
}
