<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

abstract class DependentText extends Text {
	use DependentComponent;

	public function isReady(): bool {
		return $this->_exert;
	}
}
