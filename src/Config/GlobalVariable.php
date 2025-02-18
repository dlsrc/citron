<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

enum GlobalVariable: string implements VariableStartSign {
	case Percent = '%';
	case AtSign  = '@';
	case Caret   = '^';

	public function start(): string {
		return match($this) {
			self::Percent => '%',
			self::AtSign  => '@',
			self::Caret   => '\^',
		};
	}
}
