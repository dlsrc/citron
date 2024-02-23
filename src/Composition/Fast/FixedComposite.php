<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Fast;

use Citron\Derivative;
use Citron\DependentResult;
use Ultra\Export\SetStateDirectly;

#[SetStateDirectly]
final class FixedComposite extends DependentPerformer implements Derivative {
	use Insertion;
	use DependentCompositeResult;
	use PerformerMaster;
	use DependentResult;
}
