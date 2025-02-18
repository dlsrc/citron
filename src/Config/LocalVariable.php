<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

enum LocalVariable: string implements VariableStartSign {
	case Dot    = '.';
	case Dollar = '$';
	case None   = '';

	public function start(): string {
		return match($this) {
			self::Dot     => '\.',
			self::Dollar  => '\$',
			self::None    => '',
		};
	}
}
