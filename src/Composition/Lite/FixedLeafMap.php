<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Lite;

use Citron\Derivative;
use Citron\DependentResult;
use Ultra\SetStateDirectly;

#[SetStateDirectly]
final class FixedLeafMap extends DependentLeaf implements Derivative {
	use InsertionMap;
	use DependentLeafResult;
	use LeafMaster;
	use DependentResult;
}
