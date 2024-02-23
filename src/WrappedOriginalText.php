<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Ultra\Export\SetStateDirectly;

#[SetStateDirectly]
final class WrappedOriginalText extends Text implements Derivative, Wrapped {
	use WrappedComponent;
	use ReadyText;
	use InsertionStub;
	use WrappedResult;
	use TextMaster;
}
