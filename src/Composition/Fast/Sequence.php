<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Fast;

trait Sequence {
	public function __invoke(array $data, array $order=[]): void {
		if (empty($order)) {
			foreach ($data as $name => $value) {
				$this->_var[$name] = $value;
			}
		}
		else {
			if (!array_is_list($data)) {
				$data = array_values($data);
			}

			foreach ($order as $id => $name) {
				$this->_var[$name] = $data[$id];
			}
		}

		$this->ready();
	}
}
