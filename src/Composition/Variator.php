<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Ultra\SetStateDirectly;

#[SetStateDirectly]
final class Variator extends Variant {
	use ReadyVariant;
	use Result;
}
