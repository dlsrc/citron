<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Lite;

trait Insertion {
	final public function __set(string $name, int|float|string $value): void {
		if (isset($this->_chain[$name])) {
			$this->_chain[$name] = $value;
		}
	}
}
