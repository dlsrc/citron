<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Lite;

use Citron\Main\Derivative;
use Citron\Main\Wrapped;
use Citron\Main\WrappedComponent;
use Citron\Main\WrappedResult;
use Ultra\Export\SetStateDirectly;

#[SetStateDirectly]
final class WrappedOriginalComposite extends Performer implements Derivative, Wrapped {
	use Insertion;
	use ReadyComposite;
	use PerformerMaster;
	use WrappedComponent;
	use WrappedResult;
}
