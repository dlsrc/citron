<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

use Ultra\Enum\Dominant;
use Ultra\Enum\DominantCase;

enum Mode implements Dominant {
	use DominantCase;

	/**
	 * Основной (производственный) режим работы.
	 * Композиция шаблонов строится однократно и экспортируется в исполняемый файл,
	 * далее шаблонизатор работает с экспортированной копией готовой композиции объектов шаблонов.
	 * Недостающие компоненты эмулируются без предупреждений.
	 */
	case Product;

	/**
	 * Режим разработки.
	 * Шаблоны компилируются при каждом обращении, композициа объектов шаблонов каждый раз заново
	 * выстраивается и экспортируется в исполняемый файл.
	 * В случае ошибок происходит остановка с выбрасыванием исключения.
	 */
	case Develop;

	/**
	 * Режим пересборки.
	 * Как и в случае режима разработки, шаблоны проходят полный цикл создания композиции объектов,
	 * но в случае ошибки работа не останавливается, недостающие компоненты композиции эмулируются.
	 */
	case Rebuild;
}