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
final class WrappedOriginalLeafMap extends Leaf implements Derivative, Wrapped {
	use InsertionMap;
	use ReadyLeaf;
	use LeafMaster;
	use WrappedComponent;
	use WrappedResult;
}
