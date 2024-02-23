<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

trait ReadyVariant {
	use IndependentComponent;

	public function ready(): void {
		$this->_component[$this->_variant]->ready();
		$this->_result.= $this->_component[$this->_variant]->getResult();
		$this->_component[$this->_variant]->update();
	}
}
