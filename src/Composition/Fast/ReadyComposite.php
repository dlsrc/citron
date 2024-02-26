<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Fast;

use Citron\IndependentComponent;

trait ReadyComposite {
	use IndependentComponent;

	public function ready(): void {
		$this->notify();
		$this->_result.= implode($this->_chain);
	}
}
