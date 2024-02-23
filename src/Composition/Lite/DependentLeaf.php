<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Lite;

use Citron\DependentComponent;

abstract class DependentLeaf extends Leaf {
	use DependentComponent;

	public function isReady(): bool {
		return $this->_exert;
	}
}
