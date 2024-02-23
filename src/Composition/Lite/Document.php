<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Lite;

use Citron\IndependentComponent;
use Citron\RootComponent;
use Ultra\Export\SetStateDirectly;

#[SetStateDirectly]
final class Document extends Leaf {
	use InsertionMap;
	use RootComponent;
	use IndependentComponent;

	public function ready(): void {
		$this->_result = implode($this->_chain);
	}
}
