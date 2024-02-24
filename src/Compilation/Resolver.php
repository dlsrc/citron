<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Compilation;

class Resolver {
	/**
	 * Имя набора атрибутов.
	 * Соответствует имени сниппета, в котором выполняется модификация
	 */
	private string $_name;

	/**
	 * Путь до обрабатываемого блока
	 */
	private string $_path;

	/**
	 * Признак сохранения для всех незадействованых в разборе атрибутов классов.
	 */
	private bool $_asis;

	/**
	 * Список меток вида %%INT%%, заменяющих обрамленные кавычками строковые
	 * литералы в разбираемых аттрибутах.
	 */
	private array $_mark;

	/**
	 * Список литералов отделенных от строки атрибутов.
	 * Соответствуют меткам $_mark.
	 */
	private array $_literal;

	/**
	 * Список блоков(путей), которые будут вырезаны из модифицируемого сниппета.
	 */
	private array $_cut;

	/**
	 * Многомммерный список блоков(путей) и классов в них, для которых добавлены
	 * аттрибуты и(или) модифицированы переменные.
	 */
	private array $_class;

	/**
	 * Многомерный список блоков(путей) и переменных в них, которым будут статически
	 * присвоены указанные значения на этапе сборки шаблона.
	 */
	private array $_sets;

	/**
	 * Общая схема компонента или сниппета: '[ !$name : Type = @Snippet { $attrs }]'
	 *
	 * $attrs - строка атрибутов, всё что попало между фигурных скобок в объявлении
	 *          компонента шаблона или библиотечного сниппета.
	 * $name  - новое имя сниппета, то что стоит сразу после открывающей квадратной
	 *          скобки и, если есть, маркера фиксации компонента - '!'.
	 * $asis  - флаг, определяющий что нужно сделать с атрибутами классов,
	 *          если они никак не упомянуты в модификациях.
	 *          Актуален только для компонентов шаблонов, для библиотек всегда TRUE.
	 *          Флаг $asis определяется наличием или отсутствием маркера '@' перед
	 *          именем сниппета.
	 *          TRUE  - оставить все атрибуты классов как есть;
	 *          FALSE - удалить все явно не затронутые атрибуты классов.
	 */
	public function __construct(string $attrs, string $name, bool $asis) {
		if (str_contains($name, '.')) {
			$this->_name = substr($name, strrpos($name, '.') + 1);
		}
		else {
			$this->_name = $name;
		}

		if ('' == $name) {
			$this->_path = '_self.';
		}
		else {
			$this->_path = $name.'.';
		}

		$this->_asis    = $asis;
		$this->_mark    = [];
		$this->_literal = [];
		$this->_cut     = [];
		$this->_class   = [];
		$this->_sets    = [];

		$this->_parse($attrs);
    }

	/**
	 * Геттеры для списков обнаруженных модификаций.
	 * Атрибуты основанные на классах
	 */
	public function attrs(): array {
		return $this->_class;
	}

	/**
	 * Удаляемые внутренние блоки
	 */
	public function cut(): array {
		return $this->_cut;
	}

	/**
	 * Значения переменных
	 */
	public function sets(): array {
		return $this->_sets;
	}

	/**
	 * Обнаруживает в переданной строке модификаций инструкцию (слеш - '/')
	 * для смены базового блока (пути) и устанавливает путь к блоку как базу
	 * для дальнейших модификаций, если маркер пути обнаружен.
	 */
	private function _changePath(string &$line): void {
		if ('' == $this->_name) {
			$this->_path = '';
		}
		else {
			$this->_path = $this->_name.'.';
		}

		$path = explode('/', $line);

		$path[0] = trim($path[0]);
		$line = trim($path[1]);

		if ('' != $path[0]) {
			$this->_path = $this->_name.'.'.$path[0].'.';
		}

		if ('' == $this->_path) {
			$this->_path = '_self.';
		}
	}

	/**
	 * Извлекает из списка блоки, которые нужно вырезать из компонента
	 */
	private function _extractCuts(string $cuttings): void {
		$cuts = explode(',', $cuttings);
		
		foreach ($cuts as $cut) {
			$cut = trim($cut);
		
			if ('' == $cut) {
				continue;
			}

			$this->_cut[] = $this->_path.$cut;
		}
	}

	/**
	 * Завершает обработку списка атрибутов для классов шаблона (см. Resolver->_parse()).
	 * Все списки атрибутов составленные для каждого класса, преобразуются в строку атрибутов.
	 * Все строковые литералы возвращаются в эти строки вместо временных меток.
	 */
	private function _setClassMap(array $classmap): void {
		foreach ($classmap as $path => $attrs) {
			$class = $this->_class($path);
			$block = $this->_block($path);
		
			$this->_class[$block][$class] = str_replace($this->_mark, $this->_literal, implode(' ', $attrs));
		
			if (isset($attrs['class'])) {
				if ('class' == array_key_first($attrs)) {
					$this->_class[$block][$class] = str_replace($this->_mark, $this->_literal, implode(' ', $attrs));
				}
				else {
					$attr_class = $attrs['class'].' ';
					unset($attrs['class']);
					$this->_class[$block][$class] = str_replace($this->_mark, $this->_literal, $attr_class.implode(' ', $attrs));
				}
			}
			elseif ($this->_asis) {
				$this->_class[$block][$class] = str_replace($this->_mark, $this->_literal, 'class="'.$class.'" '.implode(' ', $attrs));
			}
			else {
				$this->_class[$block][$class] = str_replace($this->_mark, $this->_literal, implode(' ', $attrs));
			}
		}
	}

	/**
	 * Получение атрибуров для одной строки инструкций.
	 * $attr_line - строка инструкций для одного или нескольких блоков.
	 *              Блоки указываются через запятую и отделяются от значения двоеточием.
	 *              После двоеточия может быть указано нескользо значений через запятую.
	 *              Сами инструкции отделяются друг от друга точкой с запятой (см. Resolver->_parse()).
	 *              Метод обрабатывает одну инструкцию.
	 * $a         - объект-контейнер, содержит все атрибуты html и значения
	 *              по умолчанию для них;
	 * $attr_map  - карта атрибутов для блоков и классов шаблона, которые блоки содержат.
	 */
	private function _extractAttrs(array $attr_line, TagsAttribute $a, array &$attr_map): void {
		$block = explode(',', $attr_line[0]); // $line[0]!!!
		
		foreach (array_keys($block) as $i) {
			$block[$i] = trim($block[$i]);
			
			if ('' == $block[$i]) {
				unset($block[$i]);
				continue;
			}
		
			$path = $this->_path.$block[$i];
			$attrs = $this->_makeAttrsMap($attr_line[1], $a, $this->_class($block[$i]));
		
			foreach ($attrs as $attr => $value) {
				$attr_map[$path][$attr] = $value;
			}
		}
	}

	/**
	 * Удаление переменной шаблона
	 */
	private function _dropVariable(string $variable): void {
		$path = preg_replace('/^\-\s*/', '', $variable);
		$var   = $this->_class($path);
		$block = $this->_block($this->_path.$path);
		$this->_sets[$block][$var] = '';
	}

	/**
	 * Присвоение статических значений переменным шаблона
	 */
	private function _setVariableValues(string $variables): void {
		$block_var = explode('=', $variables);
		$block_var[1] = trim($block_var[1]);
		
		if (str_contains($block_var[0], ',')) {
			$vars = explode(',', $block_var[0]);
		
			foreach (array_keys($vars) as $i) {
				$vars[$i] = trim($vars[$i]);
						
				if ('' == $vars[$i]) {
					unset($vars[$i]);
					continue;
				}
			
				$var   = $this->_class($vars[$i]);
				$block = $this->_block($this->_path.$vars[$i]);
				$this->_sets[$block][$var] = str_replace($this->_mark, $this->_literal, $block_var[1]);
			}
		}
		else {
			$block_var[0] = trim($block_var[0]);
			$var   = $this->_class($block_var[0]);
			$block = $this->_block($this->_path.$block_var[0]);
			$this->_sets[$block][$var] = str_replace($this->_mark, $this->_literal, $block_var[1]);
		}
	}

	/**
	 * Парсинг строки модификации компонента
	 */
	private function _parse(string $attrs): void {
		// Контейнер, содержащий атрибуты и их значения по умолчанию.
		$a   = TagsAttribute::get();
		// TODO: Заменить вызов глобальной конфигурации на конфигурацию
		// шаблона в котором находится компонент.
//		$cfg = Citron\Config::get();

		// Перед разбором модификаторов нужно убрать все строковые
		// литералы из строки инструкций.
		$this->_extractLiterals($attrs);

		$_lines = explode(';', $attrs);
		$_map = [];

		foreach (array_keys($_lines) as $i) {
			$_lines[$i] = trim($_lines[$i]);

			if ('' == $_lines[$i]) {
				unset($_lines[$i]);
				continue;
			}

			if (str_contains($_lines[$i], '/')) {
				$this->_changePath($_lines[$i]);
			}

			$line = explode(':', $_lines[$i]);
			$line[0] = trim($line[0]);

			if (isset($line[1])) {
				$line[1] = trim($line[1]);

				if ('CUT' == $line[0]) {
					$this->_extractCuts($line[1]); // $line[1]!!!
				}
				else {
					$this->_extractAttrs($line, $a, $_map);
				}
			}
			else {
				if (str_contains($line[0], '=')) {
					$this->_setVariableValues($line[0]);
				}
				elseif (str_starts_with($line[0], '-')) {
					$this->_dropVariable($line[0]);
				}
			}
		}

		$this->_setClassMap($_map);
	}

	/**
	 * Извлечь подстроки взятые в кавычки в отдельный список литералов.
	 * Заменить подстроки метками вида %%INT%%, сохранить список меток.
	 * Реальные значения будут собраны в список $this->_literal,
	 * а метки-указатели в список $this->_mark.
	 */
	private function _extractLiterals(string &$attrs): void {
		if (preg_match_all('/"([^"]*)"/U', $attrs, $match, PREG_PATTERN_ORDER)) {
			$this->_literal = $match[1];
		
			foreach (array_keys($this->_literal) as $i) {
				$this->_mark[$i] = '%%'.$i.'%%';
			}
		
			$attrs = str_replace($match[0], $this->_mark, $attrs);
		}
	}

	/**
	 * Выделить имя класса из пути в секции настройки сниппета.
	 * Например, если путь `Item.CurrentItem.link`, то метод вернет `link`.
	 */
	private function _class(string $path): string {
		if (str_contains($path, '.')) {
			$path = substr($path, strrpos($path, '.') + 1);
		}

		return $path;
	}

	/**
	 * Выделить имя логического блока из пути в секции настройки сниппета.
	 * Например, если путь `Item.CurrentItem.link`, то метод вернет `Item.CurrentItem`.
	 */
	private function _block(string $path): string {
		if (str_contains($path, '.')) {
			$path = substr($path, 0, strrpos($path, '.'));
		}
		else {
			$path = '';
		}

		return $path;
	}

	/**
	 * Составление карты атрибутов из строки значений (всё что после двоеточия).
	 * В строке значение может быть одно или строка может содержать несколько
	 * значений разделённых запятой.
	 * $attrs_string - разбираемая строка;
	 * $a            - объект-контейнер, содержит все атрибуты html и значения
	 *                 по умолчанию для них;
	 * $class_name   - имя класса (html-атрибут в шаблоне) для которого
	 *                 составляется карта атрибутов.
	 */
	private function _makeAttrsMap(string $attrs_string, TagsAttribute $a, string $class_name): array {
		$map = [];
		$attrs_list = explode(',', $attrs_string);
	
		foreach ($attrs_list as $value) {
			$v = explode('=', $value);

			$v[0] = trim($v[0]);

			$v[0] = preg_replace(
				['/^([*_\.+]+)\s+/', '/^([*_\.]+)\s+/', '/\s+([_\-\.*?!=]+)$/', '/\s+([_\-\.*?!=]+)$/', '/\s+([_\-\.*?!=]+)$/',],
				['$1', '$1', '$1', '$1', '$1',],
				$v[0]
			);

			$v[1] = isset($v[1]) ? trim($v[1]) : '';
	
			if (preg_match('/^([_\+\-\.\*\?\!]+)$/', $v[0])) {
				// Класс
				if ('' == $v[1]) {
					switch ($v[0]) {
					case '_':
						$map['class'] = 'class="{.'.$class_name.' }"';
						break;
	
					case '*':
						$map['class'] = 'class="{.class }"';
						break;
	
					case '_*':
						$map['class'] = 'class="{.'.$class_name.'_class }"';
						break;
	
					case '*_':
						$map['class'] = 'class="{.class_'.$class_name.' }"';
						break;
	
					case '.*':
						$map['class'] = 'class="{.'.$class_name.'.class }"';
						break;
	
					case '*.':
						$map['class'] = 'class="{.class.'.$class_name.' }"';
						break;
	
					case '_!':
						$map['class'] = 'class="{.'.$class_name.' = '.$class_name.'}"';
						break;
	
					case '*!':
						$map['class'] = 'class="{.class = '.$class_name.'}"';
						break;
	
					case '_*!':
						$map['class'] = 'class="{.'.$class_name.'_class = '.$class_name.'}"';
						break;
	
					case '*_!':
						$map['class'] = 'class="{.class_'.$class_name.' = '.$class_name.'}"';
						break;
	
					case '.*!':
						$map['class'] = 'class="{.'.$class_name.'.class = '.$class_name.'}"';
						break;
	
					case '*.!':
						$map['class'] = 'class="{.class.'.$class_name.' = '.$class_name.'}"';
						break;
	
					case '_?':
						$map['class'] = 'class="{.'.$class_name.' = '.$a->class.'}"';
						break;
	
					case '*?':
						$map['class'] = 'class="{.class = '.$a->class.'}"';
						break;
	
					case '_*?':
						$map['class'] = 'class="{.'.$class_name.'_class = '.$a->class.'}"';
						break;
	
					case '*_?':
						$map['class'] = 'class="{.class_'.$class_name.' = '.$a->class.'}"';
						break;
	
					case '.*?':
						$map['class'] = 'class="{.'.$class_name.'.class = '.$a->class.'}"';
						break;
	
					case '*.?':
						$map['class'] = 'class="{.class.'.$class_name.' = '.$a->class.'}"';
						break;
	
					case '+':
						//if (!$snippet) {
						//	$map['class'] = 'class="'.$class_name.'"';
						//}
						$map['class'] = 'class="'.$class_name.'"';
						break;
					}
				}
				else {
					switch ($v[0]) {
					case '_':
						$map['class'] = 'class="{.'.$class_name.' = '.$v[1].'}"';
						break;
	
					case '*':
						$map['class'] = 'class="{.class = '.$v[1].'}"';
						break;
	
					case '_*':
						$map['class'] = 'class="{.'.$class_name.'_class = '.$v[1].'}"';
						break;
	
					case '*_':
						$map['class'] = 'class="{.class_'.$class_name.' = '.$v[1].'}"';
						break;
	
					case '.*':
						$map['class'] = 'class="{.'.$class_name.'.class = '.$v[1].'}"';
						break;
	
					case '*.':
						$map['class'] = 'class="{.class.'.$class_name.' = '.$v[1].'}"';
						break;
					}
				}
			}
			elseif (preg_match('/^(_|\.|\+|\*|)([A-Za-z\s\%\d\-]+)(\*|\.|_|\!|\?|[_\.][\?\!]{1,2}|)$/is', $v[0], $match)) {
				$attr  = $match[2];
				$value = $v[1];
				$def   = isset($a->$attr) ? $a->$attr : '';
	
				if (!$match[1] && !$match[3]) {
					if ($value) {
						$map[$attr] = $attr.'="'.$value.'"';
					}
					else {
						$map[$attr] = $attr;
					}
				}
				elseif ('+' == $match[1]) {
					$map['class'] = 'class="'.$attr.'"';
				}
				elseif ('*' == $match[1]) {
					$map['class'] = 'class="{.class = '.$attr.'}"';
				}
				elseif (!$match[1] && '*' == $match[3]) {
					if ($value) {
						$map[$attr] = '{.'.$attr.' = '.$value.'}';
					}
					else {
						$map[$attr] = '{.'.$attr.' }';
					}
				}
				elseif (!$match[1] && '_' == $match[3]) {
					if ($value) {
						$map[$attr] = '{.'.$attr.'_'.$class_name.' = '.$value.'}';
					}
					else {
						$map[$attr] = '{.'.$attr.'_'.$class_name.' }';
					}
				}
				elseif ('_' == $match[1] && !$match[3]) {
					if ($value) {
						$map[$attr] = '{.'.$class_name.'_'.$attr.' = '.$value.'}';
					}
					else {
						$map[$attr] = '{.'.$class_name.'_'.$attr.' }';
					}
				}
				elseif (!$match[1] && '.' == $match[3]) {
					if ($value) {
						$map[$attr] = '{.'.$attr.'.'.$class_name.' = '.$value.'}';
					}
					else {
						$map[$attr] = '{.'.$attr.'.'.$class_name.' }';
					}
				}
				elseif ('.' == $match[1] && !$match[3]) {
					if ($value) {
						$map[$attr] = '{.'.$class_name.'.'.$attr.' = '.$value.'}';
					}
					else {
						$map[$attr] = '{.'.$class_name.'.'.$attr.' }';
					}
				}
				elseif (!$match[1] && '?' == $match[3]) {
					if ($def) {
						$map[$attr] = '{.'.$attr.' = '.$def.'}';
					}
					else {
						$map[$attr] = '{.'.$attr.' }';
					}
				}
				elseif (!$match[1] && '_?' == $match[3]) {
					if ($def) {
						$map[$attr] = '{.'.$attr.'_'.$class_name.' = '.$def.'}';
					}
					else {
						$map[$attr] = '{.'.$attr.'_'.$class_name.' }';
					}
				}
				elseif ('_' == $match[1] && '?' == $match[3]) {
					if ($def) {
						$map[$attr] = '{.'.$class_name.'_'.$attr.' = '.$def.'}';
					}
					else {
						$map[$attr] = '{.'.$class_name.'_'.$attr.' }';
					}
				}
				elseif (!$match[1] && '.?' == $match[3]) {
					if ($def) {
						$map[$attr] = '{.'.$attr.'.'.$class_name.' = '.$def.'}';
					}
					else {
						$map[$attr] = '{.'.$attr.'.'.$class_name.' }';
					}
				}
				elseif ('.' == $match[1] && '?' == $match[3]) {
					if ($def) {
						$map[$attr] = '{.'.$class_name.'.'.$attr.' = '.$def.'}';
					}
					else {
						$map[$attr] = '{.'.$class_name.'.'.$attr.' }';
					}
				}
				elseif (!$match[1] && '!' == $match[3]) {
					if ($value) {
						$map[$attr] = $attr.'="{.'.$attr.' = '.$value.'}"';
					}
					else {
						$map[$attr] = $attr.'="{.'.$attr.' }"';
					}
				}
				elseif (!$match[1] && '_!' == $match[3]) {
					if ($value) {
						$map[$attr] = $attr.'="{.'.$attr.'_'.$class_name.' = '.$value.'}"';
					}
					else {
						$map[$attr] = $attr.'="{.'.$attr.'_'.$class_name.' }"';
					}
				}
				elseif ('_' == $match[1] && '!' == $match[3]) {
					if ($value) {
						$map[$attr] = $attr.'="{.'.$class_name.'_'.$attr.' = '.$value.'}"';
					}
					else {
						$map[$attr] = $attr.'="{.'.$class_name.'_'.$attr.' }"';
					}
				}
				elseif (!$match[1] && '.!' == $match[3]) {
					if ($value) {
						$map[$attr] = $attr.'="{.'.$attr.'.'.$class_name.' = '.$value.'}"';
					}
					else {
						$map[$attr] = $attr.'="{.'.$attr.'.'.$class_name.' }"';
					}
				}
				elseif ('.' == $match[1] && '!' == $match[3]) {
					if ($value) {
						$map[$attr] = $attr.'="{.'.$class_name.'.'.$attr.' = '.$value.'}"';
					}
					else {
						$map[$attr] = $attr.'="{.'.$class_name.'.'.$attr.' }"';
					}
				}
				elseif (!$match[1] && ('?!' == $match[3] || '!?' == $match[3])) {
					if ($def) {
						$map[$attr] = '{.'.$attr.' = '.$def.'}';
					}
					else {
						$map[$attr] = '{.'.$attr.' }';
					}
				}
				elseif (!$match[1] && ('_?!' == $match[3] || '_!?' == $match[3])) {
					if ($def) {
						$map[$attr] = '{.'.$attr.'_'.$class_name.' = '.$def.'}';
					}
					else {
						$map[$attr] = '{.'.$attr.'_'.$class_name.' }';
					}
				}
				elseif ('_' == $match[1] && ('?!' == $match[3] || '!?' == $match[3])) {
					if ($def) {
						$map[$attr] = '{.'.$class_name.'_'.$attr.' = '.$def.'}';
					}
					else {
						$map[$attr] = '{.'.$class_name.'_'.$attr.' }';
					}
				}
				elseif (!$match[1] && ('.?!' == $match[3] || '.!?' == $match[3])) {
					if ($def) {
						$map[$attr] = '{.'.$attr.'.'.$class_name.' = '.$def.'}';
					}
					else {
						$map[$attr] = '{.'.$attr.'.'.$class_name.' }';
					}
				}
				elseif ('.' == $match[1] && ('?!' == $match[3] || '!?' == $match[3])) {
					if ($def) {
						$map[$attr] = '{.'.$class_name.'.'.$attr.' = '.$def.'}';
					}
					else {
						$map[$attr] = '{.'.$class_name.'.'.$attr.' }';
					}
				}
			}
		}
	
		return $map;
	}
}
