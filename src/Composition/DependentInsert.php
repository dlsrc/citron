<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

trait DependentInsert {
	final public function insert(string $text): void {
		$this->_result = $text;
		$this->_exert = false;
	}
}
