<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Lite;

trait InsertionMap {
	final public function __set(string $name, int|float|string|array $value): void {
		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$this->__set($name.'.'.$key, $val);
			}
		}
		elseif (isset($this->_chain[$name])) {
			$this->_chain[$name] = $value;
		}
	}
}
