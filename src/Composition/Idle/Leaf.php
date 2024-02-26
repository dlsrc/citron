<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Idle;

use Citron\Component;
use Citron\Childless;

abstract class Leaf extends Component {
	use Sequence;
	use Childless;

	protected string $_text;
    protected array  $_var;
	protected array  $_ref;

	public function __construct(array $state) {
		parent::__construct($state);
        $this->_var  = $state['_var'];
        $this->_ref  = $state['_ref'];
        $this->_text = $state['_text'];
	}

	final public function common(string $name, int|float|string $value): void {
		$this->_var[$name] = $value;
	}
}
