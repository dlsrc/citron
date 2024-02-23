<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Fast;

use Citron\Component;
use Citron\Childless;

abstract class Leaf extends Component {
	use Sequence;
	use Childless;

	protected array $_var;
	protected array $_ref;
	protected array $_chain;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_var   = $state['_var'];
		$this->_ref   = $state['_ref'];
		$this->_chain = $state['_chain'];

		foreach ($this->_ref as $i => $name) {
			$this->_chain[$i] =&$this->_var[$name];
		}
	}

	public function __clone(): void {
		$clone = [];

		foreach ($this->_var as $name => $value) {
			$clone[$name] = $value;
		}

		$this->_var = $clone;

		foreach ($this->_ref as $i => $name) {
			$this->_chain[$i] =&$this->_var[$name];
		}
	}

	final public function common(string $name, int|float|string $value): void {
		$this->_var[$name] = $value;
	}
}
