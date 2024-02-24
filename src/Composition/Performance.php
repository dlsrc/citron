<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Main;

use Citron\Code;
use Citron\Info;

trait Performance {
	final public function __call(string $name, array $data): bool {
		if (!isset($this->_component[$name])) {
			Component::error(Info::message('e_no_child', $name), Code::Component);
			return false;
		}

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
			return $this->_component[$name];
		}

		Component::error(Info::message('e_no_child', $name), Code::Component);
		return Component::emulate();
	}
}
