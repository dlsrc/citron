<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Compilation;

final class Path {
	// Папка корневого шаблона
	public readonly string $root;

	// Папка репозитория citron/snippet
	public readonly string $citron;

	public function __construct(string $root_template) {
		$this->root = strtr(dirname($root_template), '\\', '/');
		$this->citron = strtr(dirname(__DIR__, 3).'/snippets', '\\', '/');
	}

	/**
	 * Разрешение имен файлов импортируемых библиотек и подключаемых подшаблонов
	 * в абсолютные пути с проверкой существования файлов этих библиотек или подшаблонов.
	 * Параметры.
	 * type - строка, содержащая префикс типа пути к файлу.
	 * В именах файлов ищутся следующие префиксы:
	 * @citron - указывает, что файл нужно искать в папке репозитория citrom/snippets;
	 * % - указывает, что абсолютный путь нужно построить от папки корневого подшаблона;
	 * ./ и множественный ../ - указывает, что абсолютный путь нужно построить относительно папки текущего подшаблона;
	 * Пустой префикс, может соответствовать как абсолютному пути, так и относительному (аналог ./).
	 * file - имя файла без префикса.
	 * tpldir - абсолютный путь к папке текущего подшаблона или библиотеки.
	 * Возвращаемое значение.
	 * Если файл по абсолютному пути существует, возвращается строка этого абсолютного пути,
	 * ести файл не существует, возвращается пустая строка.
	 */
	public function realpath(string $type, string $file, string $tpldir): string {
		return match ($type) {
			'@citron' => $this->_getCitronPath($file),
			'%' => $this->_getRootPath($file),
			'./' => $this->_getLocalPath($file, $tpldir),
			default => $this->_getRelativePath($type, $file, $tpldir),
		};
	}

	// Абсолютный путь от папки репозитория сниппетов citron.
	private function _getCitronPath(string $file): string {
		if ($path = realpath($this->citron.'/'.$file)) {
			return strtr($path, '\\', '/');
		}

		return '';
	}

	// Абсолютный путь папки корневорго шаблона + подключаемый файл.
	private function _getRootPath(string $file): string {
		if ($path = realpath($this->root.'/'.$file)) {
			return strtr($path, '\\', '/');
		}

		return '';
	}

	// Абсолютный путь до указанной папки $tpldir (папка текушего файла) + подключаемый файл.
	private function _getLocalPath(string $file, string $tpldir): string {
		if ($path = realpath($tpldir.'/'.$file)) {
			return strtr($path, '\\', '/');
		}

		return '';
	}

	private function _getRelativePath(string $type, string $file, string $tpldir): string {
		if ($count = substr_count($type, '../')) {
			// Абсолютный путь текущего файла + подключаемый файл.
			$file = strtr(dirname($tpldir, $count), '\\', '/').'/'.$file;
	
			if (file_exists($file)) {
				return $file;
			}
	
			return '';
		}
		
		// Далее, путь к файлу может быть указан как абсолютный,
		// либо как относительный для текущего файла.
		if ($path = realpath($file)) {
			// realpath() как и file_exists() выполняет проверку существования файла,
			// Если реальный путь к файлу установлен и файл существует,
			// значит был указан абсолютный файл.
			return strtr($path, '\\', '/');
		}

		// Либо нужно попробовать достроить путь от папки текущего файла (аналог ./).
		if (file_exists($tpldir.'/'.$file)) {
			return $tpldir.'/'.$file;
		}
	
		return '';
	}
}
