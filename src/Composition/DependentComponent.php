<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

trait DependentComponent {
	protected bool $_exert;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_exert = false;
	}

	public function ready(): void {
		$this->_exert = true;
	}
}
