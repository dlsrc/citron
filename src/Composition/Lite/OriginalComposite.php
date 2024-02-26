<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Lite;

use Citron\Result;
use Ultra\SetStateDirectly;

#[SetStateDirectly]
final class OriginalComposite extends Performer {
	use Insertion;
	use ReadyComposite;
	use Result;
}
