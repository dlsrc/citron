<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Lite;

trait Sequence {
	protected array $_ref;
	protected array $_chain;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_ref   = $state['_ref'];
		$this->_chain = $state['_chain'];

		foreach ($this->_ref as $k => $v) {
			$this->_chain[$k] =&$this->_chain[$v];
		}
	}

	final public function __invoke(array $data, array $order=[]): void {
		if (empty($order)) {
			foreach ($data as $name => $value) {
				if (isset($this->_chain[$name])) {
					$this->_chain[$name] = $value;
				}
			}
		}
		else {
			if (!array_is_list($data)) {
				$data = array_values($data);
			}

			foreach ($order as $id => $name) {
				if (isset($this->_chain[$name])) {
					$this->_chain[$name] = $data[$id];
				}
			}
		}

		$this->ready();
	}
}
