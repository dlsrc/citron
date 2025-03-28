<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Ultra\Enum\Dominant;
use Ultra\Enum\DominantCase;

enum Build implements Dominant {
	use DominantCase;

	case Fast;
	case Lite;
	case Idle;

	public function ns(): string {
		return match($this) {
			self::Fast => __NAMESPACE__.'\\Fast',
			self::Lite => __NAMESPACE__.'\\Lite',
			self::Idle => __NAMESPACE__.'\\Idle',
		};
	}

	public function builder(): string {
		return match($this) {
			self::Fast => namespace\Fast\Builder::class,
			self::Lite => namespace\Lite\Builder::class,
			self::Idle => namespace\Idle\Builder::class,
		};
	}
}
