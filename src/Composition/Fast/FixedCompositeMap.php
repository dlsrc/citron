<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Fast;

use Citron\Main\Derivative;
use Citron\Main\DependentResult;
use Ultra\Export\SetStateDirectly;

#[SetStateDirectly]
final class FixedCompositeMap extends DependentPerformer implements Derivative {
	use InsertionMap;
	use DependentCompositeResult;
	use PerformerMaster;
	use DependentResult;
}
