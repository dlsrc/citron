<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron\Compilation;

use Ultra\Container\Getter;

/**
 * Атрибуты HTML5 и их значения по умолчанию
 */
final class TagsAttribute extends Getter {
	protected function initialize(): void {
		$this->_property['abbr']            = '';
		$this->_property['accept']          = '';
		$this->_property['accesskey']       = '';
		$this->_property['action']          = '';
		$this->_property['alt']             = '';
		$this->_property['async']           = '';
		$this->_property['autocomplete']    = '';
		$this->_property['autofocus']       = '';
		$this->_property['autoplay']        = '';
		$this->_property['border']          = '';
		$this->_property['challenge']       = '';
		$this->_property['charset']         = '';
		$this->_property['checked']         = '';
		$this->_property['cite']            = '';
		$this->_property['class']           = 'none';
		$this->_property['cols']            = '48';
		$this->_property['colspan']         = '';
		$this->_property['content']         = '';
		$this->_property['contenteditable'] = '';
		$this->_property['controls']        = '';
		$this->_property['coords']          = '';
		$this->_property['crossorigin']     = '';
		$this->_property['data']            = '';
		$this->_property['datetime']        = '';
		$this->_property['default']         = '';
		$this->_property['defer']           = '';
		$this->_property['dir']             = '';
		$this->_property['dirname']         = '';
		$this->_property['disabled']        = '';
		$this->_property['download']        = '';
		$this->_property['enctype']         = '';
		$this->_property['for']             = '';
		$this->_property['form']            = '';
		$this->_property['formaction']      = '';
		$this->_property['formenctype']     = '';
		$this->_property['formmethod']      = '';
		$this->_property['formnovalidate']  = '';
		$this->_property['formtarget']      = '';
		$this->_property['headers']         = '';
		$this->_property['height']          = '100%';
		$this->_property['hidden']          = '';
		$this->_property['high']            = '';
		$this->_property['href']            = '';
		$this->_property['hreflang']        = '';
		$this->_property['id']              = '';
		$this->_property['ismap']           = '';
		$this->_property['keytype']         = '';
		$this->_property['kind']            = '';
		$this->_property['label']           = '';
		$this->_property['lang']            = '';
		$this->_property['list']            = '';
		$this->_property['loop']            = '';
		$this->_property['low']             = '';
		$this->_property['manifest']        = '';
		$this->_property['max']             = '';
		$this->_property['maxlength']       = '';
		$this->_property['media']           = '';
		$this->_property['mediagroup']      = '';
		$this->_property['method']          = '';
		$this->_property['min']             = '';
		$this->_property['minlength']       = '';
		$this->_property['multiple']        = '';
		$this->_property['muted']           = '';
		$this->_property['name']            = '';
		$this->_property['novalidate']      = '';
		$this->_property['optimum']         = '';
		$this->_property['pattern']         = '';
		$this->_property['placeholder']     = '';
		$this->_property['poster']          = '';
		$this->_property['preload']         = '';
		$this->_property['readonly']        = '';
		$this->_property['rel']             = 'group';
		$this->_property['required']        = '';
		$this->_property['reversed']        = '';
		$this->_property['rows']            = '4';
		$this->_property['rowspan']         = '';
		$this->_property['sandbox']         = '';
		$this->_property['spellcheck']      = '';
		$this->_property['scope']           = '';
		$this->_property['selected']        = '';
		$this->_property['shape']           = '';
		$this->_property['size']            = '';
		$this->_property['sizes']           = '';
		$this->_property['span']            = '';
		$this->_property['src']             = '';
		$this->_property['srcdoc']          = '';
		$this->_property['srclang']         = '';
		$this->_property['start']           = '';
		$this->_property['step']            = '';
		$this->_property['style']           = '';
		$this->_property['tabindex']        = '';
		$this->_property['target']          = '_self';
		$this->_property['title']           = '&nbsp;';
		$this->_property['translate']       = '';
		$this->_property['type']            = '';
		$this->_property['typemustmatch']   = '';
		$this->_property['usemap']          = '';
		$this->_property['value']           = '';
		$this->_property['width']           = '100%';
		$this->_property['wrap']            = '';
	}
}
