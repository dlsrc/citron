<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Idle;

use Citron\Main\IndependentComponent;
use Citron\Main\RootComponent;
use Ultra\Export\SetStateDirectly;

#[SetStateDirectly]
final class Complex extends Performer {
	use RootComponent;
	use IndependentComponent;

	protected array  $_global;
	protected string $_first;
	protected string $_last;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_global = $state['_global'];
//		$this->_first  = $state['_first'];
//		$this->_last   = $state['_last'];
		$this->_first  = '{%';
		$this->_last   = '}';
}

	public function __set(string $name, int|float|string|array $value): void {
		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$this->_var[$name.'.'.$key] = $value;
			}
		}
		elseif (isset($this->_var[$name])) {
			$this->_var[$name] = $value;
		}
		else {
			$this->_global[$this->_first.$name.$this->_last] = $value;
		}
	}

	public function ready(): void {
		if ('' == $this->_result) {
			$this->notify();
			$this->_result = str_replace($this->_ref, $this->_var, $this->_text);
			
			if (!empty($this->_global)) {
				$this->_result = str_replace(
					array_keys($this->_global),
					$this->_global,
					$this->_result
				);
			}
		}
	}

	final public function force(string $name, string $text): bool {
		if (!isset($this->_component[$name])) {
			return false;
		}

		$this->_component[$name]->insert($text);
		return true;
	}
}
