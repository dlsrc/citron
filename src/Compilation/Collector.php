<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Compilation;

use Citron\Config;
use Citron\Seed;

class Collector {
	/**
	 * Объект для разрешения путей к шаблонам.
	 */
	public readonly Path $path;

	/**
	 * Конфигурация всего дерева шаблонов
	 * Если в корневом шаблоне отсутствует блок конфигурации,
	 * то конфигурация дерева шаблонов идентична глобальному
	 * объекту конфигурации.
	 */
	//public readonly Config $config;
	private array $_config;

	/**
	 * Карта объектов подшаблонов.
	 * В качестве ключей используются абсолютные пути к файлам библиотек,
	 * соответствующие значениям списка файлов Collector::$files
	 */
	private array $_template;

	/**
	 * Карта библиотечных объектов.
	 * В качестве ключей используются абсолютные пути к файлам библиотек.
	 */
	private array $_library;
	
	/**
	 * Список сниппетов
	 */
	private array $_snippet;

	/**
	 * Список динамических компонентов шаблона
	 */
	private array $_component;

	/**
	 * Закрытый консруктор
	 */
	private function __construct(string $filename, string $content) {
		$this->path = new Path($filename);
		$this->_config = [Config::get()->setup($content)];

		$this->_template = [new Template(
			content: $content,
			filename: $filename,
			config: 0,
		)];

		$this->_library   = [];
		$this->_snippet   = [];
		$this->_component = [];
	}

	/**
	 * Создание сборщика шаблона
	 */
	public static function make(string $realpath): Collector|null {
		if (!$content = file_get_contents($realpath)) {
			return null;
		}

		return new Collector(content: $content, filename: $realpath);
	}

	/**
	 * Процедура сборки шаблона
	 */
	public function collect(): string {
		// Подключение файлов подшаблонов и составление списка объектов-частей шаблона
		$this->_makeTemplateList();

		// Импорт библиотечных файлов и составление списка объектов библиотек.
		// Пока файлы библиотек общие и примитивы в них могут использоваться всеми подшаблонами.
		$this->_importLibs();

		// Выделить примитивы и сниппеты для каждой библиотеки
		$this->_getSnippetsFromLibs();

		// Подготовка сниппетов и примитивов к применению в шаблонах
		$this->_prepareSnippets();

		// Замена сниппетов в подшаблонах шаблона на блоки компонентов шаблона
		$this->_prepareTemplates();

		// Сборка шаблона в единое целое, в одну строку.
		return $this->_collectTemplate();
    }

	/**
	 * Добавить библиотечный объект.
	 */
	public function addLib(Library $lib): void {
		$this->_library[$lib->filename] = $lib;
	}

	/**
	 * Проверить существование библиотечного объекта по имени.
	 */
	public function isLib(string $filename): bool {
		return isset($this->_library[$filename]);
	}

	/**
	 * Добавить новый объект в список сниппетов,
	 * если текого сниппета в списке ещё нет.
	 */
	public function addSnippet(Snippet $s): void {
		if (!isset($this->_snippet[$s->name])) {
			$this->_snippet[$s->name] = $s;
		}
	}

	/**
	 * Проверить существование объекта сниппета.
	 */
	public function isSnippet(string $name): bool {
		return isset($this->_snippet[$name]);
	}

	/**
	 * Вернуть объект сниппета по имени.
	 * Если сниппет не существует, вернуть NULL.
	 */
	public function getSnippet(string $name): Snippet|null {
		if (isset($this->_snippet[$name])) {
			return $this->_snippet[$name];
		}

		return null;
	}

	/**
	 * Добавить новый объект в список компонентов,
	 * если текого компонента в списке ещё нет.
	 */
	public function addComponent(Component $s): void {
		if (!isset($this->_component[$s->name])) {
			$this->_component[$s->name] = $s;
		}
	}

	/**
	 * Проверить существование компонента.
	 */
	public function isComponent(string $name): bool {
		return isset($this->_component[$name]);
	}

	/**
	 * Вернуть объект компонента по имени.
	 * Если компонент не существует, вернуть NULL.
	 */
	public function getComponent(string $name): Component|null {
		if (isset($this->_component[$name])) {
			return $this->_component[$name];
		}

		return null;
	}

	public function getConfig(int $id = 0): Config {
		if (isset($this->_config[$id])) {
			return $this->_config[$id];
		}
		
		return Config::get();
	}

	public function getConfigId(Config $config): int {
		$id = array_search($config, $this->_config, true);
						
		if (false === $id) {
			$this->_config[] = $config;
			$id = array_key_last($this->_config);
		}

		return $id;
	}

	/**
	 * Подключение списка файлов подшаблонов и составление списка объектов-частей шаблона
	 */
	private function _makeTemplateList(): void {
		$pattern = '/^([\x09\x20]*)<!--\s*include\s+(%|(?:\.\.\/)+|\.\/|)([^\s>]+)\s*-->/im';
		$_files = [$this->_template[0]->filename];

		for ($i = 0; isset($_files[$i]); $i++) {
			// родители
			$tpl = $this->_template[$i];
			$cfg = $this->_config[$tpl->config];

			if (preg_match_all($pattern, $tpl->getContent(), $matches, PREG_SET_ORDER)) {
				$tpldir  = strtr(dirname($tpl->filename), '\\', '/');

				// дети
				foreach ($matches as $match) {
					if ($file = $this->path->realpath($match[2], $match[3], $tpldir)) {
						if (in_array($file, $_files)) {
							continue;
						}

						if (!$content = file_get_contents($file)) {
							continue;
						}

						$config = $cfg->setup($content, $this);

						$_files[] = $file;
						$this->_template[] = new Template(
							content: $content,
							filename: $file,
							config: $this->getConfigId($config),
							indent: $match[1],
							parent: $i,
							search: $match[0],
						);
					}
				}
			}
		}
	}

	/**
	 * Импорт библиотечных файлов и составление списка объектов библиотек.
	 * Пока файлы библиотек общие и примитивы в них могут использоваться всеми подшаблонами.
	 */
	private function _importLibs(): void {
		// Библиотеки всегда составляются на основе глобальной конфигурации
		$seed = Seed::Main->setMain();

		$libs = [];

		foreach ($this->_template as $tpl) {
			$lib = $tpl->importLibs($this);

			foreach ($lib as $filename) {
				$libs[] = $filename;
			}
		}

		for ($i = 0; isset($libs[$i]); $i++) {
			$lib = $this->_library[$libs[$i]]->importLibs($this);

			foreach ($lib as $filename) {
				$libs[] = $filename;
			}
		}

//		$seed->setMain();
	}

	/**
	 * Выделить примитивы и сниппеты для каждой библиотеки.
	 */
	private function _getSnippetsFromLibs(): void {
		if (empty($this->_library)) {
			return;
		}
		
		$libs = array_keys($this->_library);

		for ($i = array_key_last($libs); $i >= 0; $i--) {
			$lib = $this->_library[$libs[$i]];
			$lib->createSnippets($this);
		}
	}

	/**
	 * Подготовка сниппетов и примитивов к применению в шаблонах.
	 */
	private function _prepareSnippets(): void {
		$repeat = [];

		foreach ($this->_snippet as $s) {
			if ($s->isComplex()) {
				$s->prepareLib($this);
			}

			if ($s->isComplex()) {
				$repeat[] = $s;
			}
		}

		foreach ($repeat as $s) {
			$s->prepareLib($this);
		}
	}

	/**
	 * Замена сниппетов в подшаблонах шаблона на блоки компонентов шаблона.
	 */
	private function _prepareTemplates(): void {
		$repeat = [];

		foreach ($this->_template as $i => $t) {
			foreach ($t->createComponents($this, $i) as $component) {
				$repeat[] = $component;
			}
		}

		foreach ($repeat as $i => $component) {
			if (Template::repeatComponent($this, $component)) {
				unset($repeat[$i]);
			}
		}

		foreach ($repeat as $component) {
			Template::repeatComponent($this, $component);
		}

		foreach ($this->_component as $c) {
			$c->prepareTemplate($this, $this->_template[$c->template]);
		}
	}

	/**
	 * Сборка шаблона в единое целое, в одну строку.
	 */
	private function _collectTemplate(): string {
		for ($i = array_key_last($this->_template); $i >= 0; $i--) {
			$sub = $this->_template[$i];

			if ($sub->isNotParent()) {
				$this->_template[$sub->parent]->includeSub($sub);
			}
		}

		return $sub->getContent();
	}
}
