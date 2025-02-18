<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

abstract class Text extends Component {
	use Childless;

	protected string $_text;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_text = $state['_text'];
	}

	final public function __invoke(array $data, array $order=[]): void {}
	final public function common(string $name, int|float|string $value): void {}
}
