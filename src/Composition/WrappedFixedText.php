<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Ultra\SetStateDirectly;

#[SetStateDirectly]
final class WrappedFixedText extends DependentText implements Derivative, Wrapped {
	use WrappedComponent;
	use InsertionStub;
	use DependentTextResult;
	use WrappedDependentResult;
	use TextMaster;
}
