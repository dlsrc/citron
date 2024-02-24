<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Main;

trait RootComponent {
	final public function __toString(): string {
		$this->ready();
		return $this->_result;
	}

	final public function getResult(): string {
		$this->ready();
		return $this->_result;
	}

	final public function getRawResult(): string {
		return $this->getResult();
	}

	final public function insert(string $text): void {}
}
