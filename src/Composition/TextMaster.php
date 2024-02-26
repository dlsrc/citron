<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

trait TextMaster {
	public function getOriginal(): OriginalText {
		return new OriginalText([
			'_text'  => $this->_text,
			'_class' => $this->_class,
			'_name'  => $this->_name,
		]);
	}
}
