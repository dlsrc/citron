<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Idle;

trait LeafMaster {
	public function getOriginal(): Leaf {
		if (str_ends_with(__CLASS__, 'Map')) {
			$class = OriginalLeafMap::class;
		}
		else {
			$class = OriginalLeaf::class;
		}

		return new $class([
			'_text'  => $this->_text,
			'_var'   => $this->_var,
			'_ref'   => $this->_ref,
			'_class' => $this->_class,
			'_name'  => $this->_name,
		]);
	}
}
