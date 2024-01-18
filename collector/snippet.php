<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace citron\collector;

class Snippet {
	private const array PATTERN = [
		'insert' =>
		'/(?:\x0A([\x09\x20]*)|(>))\[\s*
		(%|)(\p{Lu}\w*|\p{Lu}[\w\.]+\w)
		(?:\s*(\:)\s*(\p{Lu}\w*|\p{Lu}[\w\.]+\w|))?
		(?:\s*\=\s*(_self\.|\.|)(\p{Lu}\w*|\p{Lu}[\w\.]+\w))?
		(?:\s*\{\s*(.+)\s*\})?
		\s*\]/Usimx',
	
		'block' =>
		'/([\x09\x20]*)(?:\/\/)?<!--\s*(\^|)(\pL\w*)
		(?:\s*:\s*(\pL\w*))?\s*-->
		(.+)
		(?:\/\/)?<!--\s*~\g{3}\s*-->/Usx',
	
		'child' => '/\{\*(\p{Lu}\w*|\p{Lu}[\w\.]+\w)\}/Usim',
	];

	public readonly string $name;
	public readonly string $type;
	public readonly bool $variant;
	public readonly bool $primitive;
	private array $_stack;
	private array $_block;
	private array $_insert;

	public function __construct(
		string $name,
		string $content,
		bool $variant,
		bool $primitive,
		string $type = '',
	) {
		$this->name      = $name;
		$this->type      = $type ? $type : $name;
		$this->variant   = $variant;
		$this->primitive = $primitive;
		$this->_stack    = [$name];
		$this->_block    = [];
		$this->_block[$name]['content']   = $content;
		$this->_block[$name]['indent']    = '';
		$this->_block[$name]['name']      = $name;
		$this->_block[$name]['type']      = $type;
		$this->_block[$name]['variant']   = $variant ? '^' : '';
		$this->_block[$name]['composite'] = false;
		$this->_insert   = [];

		$this->_getInnerState();
	}

	public static function makeReplacement(
		string $name,
		string $content,
		string $type,
		string $variant,
		string $indent,
		string $wrap=''
	): string {
		if ('' == $type) {
			return $indent.
			'<!-- '.$variant.$name.' '.$wrap.' -->'.
			$content.
			'<!-- ~'.$name.' -->';
		}
		else {
			return $indent.
			'<!-- '.$variant.$name.' : '.$type.' '.$wrap.' -->'.
			$content.
			'<!-- ~'.$name.' -->';
		}
	}

//	public function getContent(bool $inner=false): string {
	public function getContent(): string {
		$content = $this->_block[$this->_stack[0]]['content'];
		$count = count($this->_stack);

		while (preg_match_all(self::PATTERN['child'], $content, $matches, PREG_SET_ORDER) > 0 && $count > 0) {
			foreach ($matches as $match) {
				if (isset($this->_block[$match[1]])) {
					$content = str_replace(
						$match[0],
						self::makeReplacement(
							name:    $this->_block[$match[1]]['name'],
							content: $this->_block[$match[1]]['content'],
							type:    $this->_block[$match[1]]['type'],
							variant: $this->_block[$match[1]]['variant'],
							indent:  $this->_block[$match[1]]['indent'],
						),
						$content
					);
				}
			}

			$count--;
		}
/*
		if ($inner) {
			return $content;
		}
*/
		return self::makeReplacement(
			name:    $this->_block[$this->_stack[0]]['name'],
			content: $content,
			type:    $this->_block[$this->_stack[0]]['type'],
			variant: $this->_block[$this->_stack[0]]['variant'],
			indent:  ''
		);
	}

	public function isComplex(): bool {
		return !empty($this->_insert);
	}

	public function isReady(string $name): bool {
		if (isset($this->_block[$name])) {
			return !isset($this->_insert[$name]);
		}

		return false;
	}

	/**
	 * Разбор вставок в библиотечные сниппеты.
	 */
	public function prepareLib(\citron\Collector $c): void {
		foreach ($this->_insert as $name => $inserts) {
			foreach ($inserts as $i => $insert) {
				if (str_contains($insert['path'], '.')) {
					$snippet = substr($insert['path'], 0, strpos($insert['path'], '.'));
				}
				else {
					$snippet = $insert['path'];
				}

				if (!$s = $c->getSnippet($snippet)) {
					continue;
				}

				if (!$s->isReady($insert['path'])) {
					continue;
				}

				$block = $s->getBlockLib($insert);

				if ($insert['inner']) {
					if ($insert['compact']) {
						$block['content'] = trim($block['content']);
					}
					else {
						$block['content'] = str_replace("\n".$block['indent'], "\n".$insert['indent'], $block['content']);
					}
				}
				else {
					if ('' == $insert['type']) {
						if ('' == $block['type']) {
							$insert['type'] = $block['name'];
						}
						else {
							$insert['type'] = $block['type'];
						}
					}

					$block['content'] = "\n".$insert['indent'].
					'<!-- '.$insert['name'].' : '.$insert['type'].' -->'.
					str_replace("\n".$block['indent'], "\n".$insert['indent'], $block['content']).
					'<!-- ~'.$insert['name'].' -->';
				}

				$this->_block[$name]['content'] = str_replace(
					"\n".$insert['indent'].'[[::'.$i.'::]]',
					$block['content'],
					$this->_block[$name]['content']
				);

				$last = array_key_last($this->_stack);

				$this->_findInnerBlocks($name);

				for (++$last; isset($this->_stack[$last]); $last++) {
					$this->_findInnerBlocks($this->_stack[$last]);
				}

				unset($this->_insert[$name][$i]);
			}

			if (empty($this->_insert[$name])) {
				unset($this->_insert[$name]);
			}
		}
	}

	/**
	 * Создание нового блока для вставки в 
	 */
	public function getBlockLib(array $insert): array {
		$block = $this->_block;

		if ('' != $insert['tuning']) {
			self::_tuneContent($block, $insert['tuning'], $insert['path'], true);
		}

		$count = count($block);

		while (preg_match_all(self::PATTERN['child'], $block[$insert['path']]['content'], $matches, PREG_SET_ORDER) > 0 && $count > 0) {
			foreach ($matches as $match) {
				if (isset($block[$match[1]])) {
					$block[$insert['path']]['content'] = str_replace(
						$match[0],
						self::makeReplacement(
							name:    $block[$match[1]]['name'],
							content: $block[$match[1]]['content'],
							type:    $block[$match[1]]['type'],
							variant: $block[$match[1]]['variant'],
							indent:  $block[$match[1]]['indent'],
						),
						$block[$insert['path']]['content']
					);
				}
			}

			$count--;
		}

		return $block[$insert['path']];
	}

	public function getBlockTemplate(Component $com): string {
		$block = $this->_block;

		if ('' != $com->tuning) {
			self::_tuneContent($block, $com->tuning, $com->snippet, $com->asis);
		}

		$count = count($block);

		while (preg_match_all(self::PATTERN['child'], $block[$com->snippet]['content'], $matches, PREG_SET_ORDER) > 0 && $count > 0) {
			foreach ($matches as $match) {
				if (isset($block[$match[1]])) {
					$block[$com->snippet]['content'] = str_replace(
						$match[0],
						self::makeReplacement(
							name:    $block[$match[1]]['name'],
							content: $block[$match[1]]['content'],
							type:    $block[$match[1]]['type'],
							variant: $block[$match[1]]['variant'],
							indent:  $block[$match[1]]['indent'],
						),
						$block[$com->snippet]['content']
					);
				}
			}

			$count--;
		}

		return $block[$com->snippet]['content'];
	}

	private static function _tuneContent(array &$block, string $tuning, string $path, bool $asis): void {
		$r = new Resolver($tuning, $path, $asis);

		$cut = $r->cut();

		foreach ($cut as $name) {
			$parent = substr($name, 0, strrpos($name, '.'));

			if (!isset($block[$parent])) {
				continue;
			}

			$block[$parent]['content'] = preg_replace(
				'/\x0A[\x09\x20]*\{\*'.preg_quote($name).'\}/',
				'',
				$block[$parent]['content']
			);
		}

		$sets = $r->sets();
		
		[$start, $end] = \citron\Config::get()->patternLocalSet();

		foreach ($sets as $name => $set) {
			if (!isset($block[$name])) {
				continue;
			}

			foreach ($set as $var => $val) {
				$block[$name]['content'] = preg_replace(
					$start.preg_quote($var).$end,
					$val,
					$block[$name]['content']
				);
			}
		}

		$attrs = $r->attrs();

		foreach ($attrs as $name => $attr) {
			if (!isset($block[$name])) {
				continue;
			}

			foreach ($attr as $class => $val) {
				$block[$name]['content'] = str_replace(
					'class="'.$class.'"',
					$val,
					$block[$name]['content']
				);
			}
		}
	}

	/**
	 * Получение внутренней блочной сруктуры
	 */
	private function _findInnerBlocks(string $parent): void {
		if (preg_match_all(self::PATTERN['block'], $this->_block[$parent]['content'], $matches, PREG_SET_ORDER)) {
			$this->_block[$parent]['composite'] = true;

			foreach ($matches as $match) {
				$child = $parent.'.'.$match[3];

				if ('' == $match[4]) {
					$match[4] = $match[3];
				}

				$this->_stack[] = $child;
				$this->_block[$child]['content'] = $match[5];
				$this->_block[$child]['indent']  = $match[1];
				$this->_block[$child]['name']    = $match[3];
				$this->_block[$child]['type']    = $match[4];
				$this->_block[$child]['variant'] = $match[2];

				$this->_block[$parent]['content'] = str_ireplace($match[0], '{*'.$child.'}', $this->_block[$parent]['content']);
			}
		}
	}

	/**
	 * Поиск вставок примитивов и сниппетов, как самомтоятельных объектов,
	 * так и частей этих объектов.
	 * name      - имя вставляемого блока. Может быть как у исходного примитива,
	 *             а может отличаться, если указано явно.
	 * type      - тип блока. Может быть указан явно или может наследовать тип
	 *             исходного блока и оставаться пустым, тогда тип блока будет
	 *             вычислен позже при разборе исходника.
	 * indent    - отступ нового блока, в контексте текущего сниппета.
	 * inner     - флаг внутренней вставки, когда вставляется только содержимое
	 *             исходного блока без маркеров имени и типа.
	 * compact   - вставка в компактной форме (только для inner блоков).
	 *             Указывает на то, что в строке содержимого внутренней вставки
	 *             нужно удалить ведущие и конечные пробелы.
	 * path      - Полный путь к примитиву или имя сниппета.
	 * tuning    - Параметры настройки исходного сниппета или примитива для
	 *             приминения в новом блоке.
	 */
	private function _findInserts(string $parent): void {
		if (0 == preg_match_all(self::PATTERN['insert'], $this->_block[$parent]['content'], $matches, PREG_SET_ORDER)) {
			return;
		}

		$search = [];
		$replace = [];

		foreach ($matches as $i => $match) {
			$search[$i] = $match[0];

			if ('>' == $match[2]) {
				$replace[$i] = '>[[::'.$i.'::]]';
				$this->_insert[$parent][$i]['compact'] = true;
				$this->_insert[$parent][$i]['indent'] = '';
			}
			else {
				$replace[$i] = "\n".$match[1].'[[::'.$i.'::]]';
				$this->_insert[$parent][$i]['compact'] = false;
				$this->_insert[$parent][$i]['indent'] = $match[1];
			}
		
			if ('%' == $match[3]) {
				$this->_insert[$parent][$i]['inner'] = true;
			}
			else {
				$this->_insert[$parent][$i]['inner'] = false;
			}
		
			$this->_insert[$parent][$i]['name'] = $match[4];
		
			if (isset($match[5]) && isset($match[6]) && ':' == $match[5]) {
				if ('' == $match[6]) {
					$this->_insert[$parent][$i]['type'] = $match[4];
				}
				else {
					$this->_insert[$parent][$i]['type'] = $match[6];
				}
			}
			else {
				$this->_insert[$parent][$i]['type'] = '';
			}
		
			if (isset($match[7])) {
				$path = match($match[7]) {
					'_self.' => 'local',
					'.' => 'parent',
					default => 'global'
				};
			}
			else {
				$path = 'global';
			}
		
			if (isset($match[8]) && '' != $match[8]) {
				$this->_insert[$parent][$i]['path'] = match($path) {
					'global' => $match[8],
					'parent' => $parent.'.'.$match[8],
					'local'  => $this->name.'.'.$match[8],
				};
			}
			else {
				$this->_insert[$parent][$i]['path'] = $match[4];
			}
		
			if (isset($match[9])) {
				$this->_insert[$parent][$i]['tuning'] = $match[9];
			}
			else {
				$this->_insert[$parent][$i]['tuning'] = '';
			}
		}

		$this->_block[$parent]['content'] = str_replace($search, $replace, $this->_block[$parent]['content']);
	}

	/**
	 * Получение внутренней структуры сниппета
	 */
	private function _getInnerState(): void {
		for ($i = 0; isset($this->_stack[$i]); $i++) {
			$this->_findInnerBlocks($this->_stack[$i]);
			$this->_findInserts($this->_stack[$i]);
		}
	}
}
