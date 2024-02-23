<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Idle;

trait PerformerMaster {
	public function getOriginal(): Performer {
		$component = [];

		foreach (array_keys($this->_component) as $name) {
			$component[$name] = clone $this->_component[$name];
		}

		if (str_ends_with(__CLASS__, 'Map')) {
			$class = OriginalCompositeMap::class;
		}
		else {
			$class = OriginalComposite::class;
		}

		return new $class([
			'_text'      => $this->_text,
			'_var'       => $this->_var,
			'_ref'       => $this->_ref,
			'_class'     => $this->_class,
			'_name'      => $this->_name,
			'_component' => $component,
		]);
	}
}
