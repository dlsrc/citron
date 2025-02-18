<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Ultra\Enum\Dominant;
use Ultra\Enum\DominantCase;

// Источник конфигурации по умолчанию,
// то есть какую конфигурацию будет использовать текущий шаблон,
// если нет никаких дополнительных указаний в самом шаблоне.
enum Seed implements Dominant {
	use DominantCase;
	// Конфигурация предка
	case Node;
	// Конфигурация корневого шаблона
	case Root;
	// Глобальная конфигурация, она же конфигурация Builder
	case Main;
}
