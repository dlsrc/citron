<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace citron\collector;

readonly class Component {
	public function __construct(
		/**
		 * Имя компонента
		 */
		public string $name,

		/**
		 * Тип компонента
		 */
		public string $type,

		/**
		 * Имя сниппета
		 */
		public string $snippet,

		/**
		 * Обёртка компонента
		 */
		public string $wrap,

		/**
		 * Отступ
		 */
		public string $indent,

		/**
		 * Параметры для настройки компонента
		 */
		public string $tuning,

		/**
		 * Полная строка компонента для поиска
		 * и замены в шаблоне на содержимое компонента
		 */
		public string $search,

		/**
		 * Индекс шаблона в котором размещён компонент
		 */
		public int $template,

		/**
		 * Флаг "Как есть", сохраняющего атрибуты классов в шаблоне
		 */
		public bool $asis,

		/**
		 * Флаг фиксированного компонента.
		 */
		public bool $fixed,
	) {}

	public function prepareTemplate(\citron\Collector $c, Template $t): void {
		if (!$snippet = $c->getSnippet($this->snippet)) {
			return;
		}

		$variant = $snippet->variant ? '^' : '';

		$replace = \str_replace(
			"\n",
			"\n".$this->indent,
			Snippet::makeReplacement(
				name:    $this->name,
				content: $snippet->getBlockTemplate($this),
				type:    $this->type,
				variant: $variant,
				indent:  $this->indent,
				wrap:    $this->wrap,
			)
		);

		$t->replaceComponent($this->search, $replace);
	}
}
