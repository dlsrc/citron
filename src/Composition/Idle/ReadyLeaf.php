<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Idle;

use Citron\Main\IndependentComponent;

trait ReadyLeaf {
	use IndependentComponent;

	public function ready(): void {
		$this->_result.= str_replace($this->_ref, $this->_var, $this->_text);
	}
}
