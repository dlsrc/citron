<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Fast;

trait PerformerMaster {
	public function getOriginal(): Performer {
		$component = [];

		foreach (array_keys($this->_component) as $name) {
			$component[$name] = clone $this->_component[$name];
		}

		$var = [];

		foreach ($this->_var as $name => $value) {
			$var[$name] = $value;
		}

		if (str_ends_with(__CLASS__, 'Map')) {
			$class = OriginalCompositeMap::class;
		}
		else {
			$class = OriginalComposite::class;
		}

		return new $class([
			'_chain'     => $this->_chain,
			'_var'       => $var,
			'_ref'       => $this->_ref,
			'_child'     => $this->_child,
			'_class'     => $this->_class,
			'_name'      => $this->_name,
			'_component' => $component,
		]);
	}
}
