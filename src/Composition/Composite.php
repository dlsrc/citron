<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Main;

use Citron\Code;
use Citron\Info;

abstract class Composite extends Component {
	protected array $_component;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_component = $state['_component'];
	}

	final public function __isset(string $name): bool {
		return isset($this->_component[$name]);
	}

	final public function drop(): void {
		foreach ($this->_component as $component) {
			$component->drop();
		}

		$this->update();
	}

	final public function isComponent(string $name): bool {
		if (isset($this->_component[$name])) {
			return true;
		}

		if (str_contains($name, Component::NS)) {
			$branch = explode(Component::NS, $name);
			$com = $this;

			foreach ($branch as $n) {
				if (!$com->isComponent($n)) {
					return false;
				}

				$com = $com->{$n};
			}

			return true;
		}

		return false;
	}

	final public function getChild(string $class): Component {
		foreach ($this->_component as $component) {
			if ($component->isClass($class)) {
				return $component;
			}
		}

		Component::error(Info::message('e_no_class', $class), Code::Type);
		return Component::emulate();
	}

	final public function getChildName(string $class): string|null {
		foreach ($this->_component as $name => $component) {
			if ($component->isClass($class)) {
				return $name;
			}
		}

		return null;
	}

	final public function getChildNames(string $class): array {
		$names = [];

		foreach ($this->_component as $name => $component) {
			if ($component->isClass($class)) {
				$names[] = $name;
			}
		}

		return $names;
	}
}
