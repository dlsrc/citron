<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace citron;

enum Code: int implements \ultra\Condition {
	// VALUE RANGE 100 - 149
	case Make      = 100; // Ошибка создания объекта шаблона
	case Open      = 101; // Ошибка открытия объекта шаблона
	case Type      = 102; // Неверный тип компонента
	case Component = 103; // Дочерний компонент отсутствует
	case File      = 104; // Файл корневого шаблона не существует
	case Content   = 105; // Не удалось получить содержимое файла шаблона

	public function isFatal(): bool {
		return false;
	}
}
