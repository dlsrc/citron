<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace citron\it;

final class Info extends \ultra\Getter {
	protected function initialize(): void {
		$this->_property['e_no_page']    = 'L\'oggetto composizione modello di pagina non esiste.';
		$this->_property['e_no_tpl']     = 'Il file modello "{0}" non esiste o l\'accesso è limitato.';
		$this->_property['e_no_child']   = 'Дочерний компонент "{0}" не существует.';
		$this->_property['e_no_class']   = 'Дочерний компонент с типом "{0}" не существует.';
		$this->_property['e_collect']    = 'В результате компиляции шаблона "{0}" сборщик вернул пустую строку.';
		$this->_property['e_no_snippet'] = 'Сниппет "{0}" не обнаружен.';
		$this->_property['e_get_file']   = 'Не удалось получить содержимое файла шаблона "{0}".';
	}
}
