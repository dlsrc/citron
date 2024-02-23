<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Config;

use Ultra\Dominant\UnitEnum as Dominant;
use Ultra\Dominant\UnitCase;

// Источник конфигурации по умолчанию,
// то есть какую конфигурацию будет использовать текущий шаблон,
// если нет никаких дополнительных указаний в самом шаблоне.
enum Seed implements Dominant {
	use UnitCase;
	// Конфигурация предка
	case Node;
	// Конфигурация корневого шаблона
	case Root;
	// Глобальная конфигурация, она же конфигурация Builder
	case Main;
}
