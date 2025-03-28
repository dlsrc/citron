<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Fast;

use Citron\Derivative;
use Citron\Wrapped;
use Citron\WrappedComponent;
use Citron\WrappedDependentResult;
use Ultra\SetStateDirectly;

#[SetStateDirectly]
final class WrappedFixedLeafMap extends DependentLeaf implements Derivative, Wrapped {
	use InsertionMap;
	use DependentLeafResult;
	use LeafMaster;
	use WrappedComponent;
	use WrappedDependentResult;
}
