<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace citron;

enum Build implements \ultra\PreferredCase {
	use \ultra\CurrentCase;

	case Fast;
	case Lite;
	case Idle;

	public function ns(): string {
		return match($this) {
			self::Fast => __NAMESPACE__.'\\fast',
			self::Lite => __NAMESPACE__.'\\lite',
			self::Idle => __NAMESPACE__.'\\idle',
		};
	}

	public function builder(): string {
		return match($this) {
			self::Fast => __NAMESPACE__.'\\fast\\Builder',
			self::Lite => __NAMESPACE__.'\\lite\\Builder',
			self::Idle => __NAMESPACE__.'\\idle\\Builder',
		};
	}
}
