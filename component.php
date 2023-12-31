<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace citron;

interface Derivative {
	public function getOriginal(): Component;
}

interface Wrapped {
	public function unwrap(): void;
}

trait Childless {
	final public function __call(string $name, array $value): bool {
		return false;
	}

	final public function __get(string $name): Component {
		Component::error(Info::message('e_no_child', $name), Code::Component);
		return Component::emulate();
	}

	final public function __isset(string $name): bool {
		return false;
	}

	final public function __unset(string $name): void {}

	final public function drop(): void {
		$this->_result = '';
	}

	final public function isComponent(string $name): bool {
		return false;
	}

	final public function getChild(string $class): Component {
		Component::error(Info::message('e_no_class', $class), Code::Type);
		return Component::emulate();
	}

	final public function getChildName(string $class): string|null {
		return null;
	}

	final public function getChildNames(string $class): array {
		return [];
	}
}

trait Performance {
	final public function __call(string $name, array $data): bool {
		if (!isset($this->_component[$name])) {
			Component::error(Info::message('e_no_child', $name), Code::Component);
			return false;
		}

		if (isset($data[1])) {
			$this->_component[$name]($data[0], $data[1]);
		}
		elseif (isset($data[0])) {
			$this->_component[$name]($data[0]);
		}

		return true;
	}

	final public function __get(string $name): Component {
		if (isset($this->_component[$name])) {
			return $this->_component[$name];
		}

		Component::error(Info::message('e_no_child', $name), Code::Component);
		return Component::emulate();
	}
}

trait DependentComponent {
	protected bool $_exert;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_exert = false;
	}

	public function ready(): void {
		$this->_exert = true;
	}
}

trait IndependentComponent {
	public function isReady(): bool {
		return '' != $this->_result;
	}
}

trait ReadyText {
	use IndependentComponent;

	public function ready(): void {
		$this->_result.= $this->_text;
	}
}

trait ReadyVariant {
	use IndependentComponent;

	public function ready(): void {
		$this->_component[$this->_variant]->ready();
		$this->_result.= $this->_component[$this->_variant]->getResult();
		$this->_component[$this->_variant]->update();
	}
}

trait WrappedComponent {
	protected string $_before;
	protected string $_after;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_before = $state['_before'];
		$this->_after  = $state['_after'];
	}

	final public function unwrap(): void {
		$this->_before  = '';
		$this->_after = '';
	}
}

trait RootComponent {
	final public function __toString(): string {
		$this->ready();
		return $this->_result;
	}

	final public function getResult(): string {
		$this->ready();
		return $this->_result;
	}

	final public function getRawResult(): string {
		return $this->getResult();
	}

	final public function insert(string $text): void {}
}

trait InsertionStub {
	final public function __set(string $name, int|float|string $value): void {}
}

trait RawResult {
	final public function getRawResult(): string {
		return $this->_result;
	}

	final public function insert(string $text): void {
		$this->_result.= $text;
	}
}

trait Result {
	use RawResult;

	final public function getResult(): string {
		return $this->_result;
	}
}

trait WrappedResult {
	use RawResult;

	final public function getResult(): string {
		if ('' != $this->_result) {
			return $this->_before.$this->_result.$this->_after;
		}

		return '';
	}
}

trait DependentInsert {
	final public function insert(string $text): void {
		$this->_result = $text;
		$this->_exert = false;
	}
}

trait DependentTextResult {
	final public function getRawResult(): string {
		if ($this->_exert) {
			$this->_exert = false;
			$this->_result = $this->_text;
		}

		return $this->_result;
	}
}

trait DependentResult {
	use DependentInsert;

	final public function getResult(): string {
		if ($result = $this->getRawResult()) {
			$this->_result = '';
			return $result;
		}

		return '';
	}
}

trait WrappedDependentResult {
	use DependentInsert;

	final public function getResult(): string {
		if ($result = $this->getRawResult()) {
			$this->_result = '';
			return $this->_before.$result.$this->_after;
		}

		return '';
	}
}

trait TextMaster {
	public function getOriginal(): OriginalText {
		return new OriginalText([
			'_text'  => $this->_text,
			'_class' => $this->_class,
			'_name'  => $this->_name,
		]);
	}
}

abstract class Component {
	final public const string NS = ':';

	abstract public function drop(): void;
	abstract public function isComponent(string $name): bool;
	abstract public function getChild(string $class): Component;
	abstract public function getChildName(string $class): string|null;
	abstract public function getChildNames(string $class): array;
	abstract public function __call(string $name, array $value): bool;
	abstract public function __get(string $name): Component;
	abstract public function __invoke(array $data, array $order=[]): void;
	abstract public function __isset(string $name): bool;
	abstract public function __unset(string $name): void;
	abstract public function __set(string $name, string|int|float $value): void;
	abstract public function getResult(): string;
	abstract public function getRawResult(): string;
	abstract public function isReady(): bool;
	abstract public function insert(string $text): void;
	abstract public function common(string $name, string|int|float $value): void;
	abstract public function ready(): void;

	protected string $_name;
	protected string $_class;
	protected string $_result;

	public function __construct(array $state) {
		$this->_name   = $state['_name'];
		$this->_class  = $state['_class'];
		$this->_result = '';
	}

	final public function getName(): string {
		return $this->_name;
	}

	final public function getClass(): string {
		return $this->_class;
	}

	final public function isClass(string $class): bool {
		return $this->_class == $class;
	}

	final protected function update(): void {
		$this->_result = '';
	}

	final public static function emulate(): Emulator {
		return new Emulator([
			'_class' => 'Emulator',
			'_name'  => 'Emulator',
		]);
	}

	final public static function error(string $message, Code $code, bool $pro=false): void {
		if (Mode::Develop->current()) {
			\ultra\Error::log($message, $code, true);
		}
		elseif (Mode::Rebuild->current() || $pro) {
			\ultra\Error::log($message, $code);
		}
	}
}

abstract class Composite extends Component {
	protected array $_component;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_component = $state['_component'];
	}

	final public function __isset(string $name): bool {
		return isset($this->_component[$name]);
	}

	final public function drop(): void {
		foreach ($this->_component as $component) {
			$component->drop();
		}

		$this->update();
	}

	final public function isComponent(string $name): bool {
		if (isset($this->_component[$name])) {
			return true;
		}

		if (\str_contains($name, Component::NS)) {
			$branch = \explode(Component::NS, $name);
			$com = $this;

			foreach ($branch as $n) {
				if (!$com->isComponent($n)) {
					return false;
				}

				$com = $com->{$n};
			}

			return true;
		}

		return false;
	}

	final public function getChild(string $class): Component {
		foreach ($this->_component as $component) {
			if ($component->isClass($class)) {
				return $component;
			}
		}

		Component::error(Info::message('e_no_class', $class), Code::Type);
		return Component::emulate();
	}

	final public function getChildName(string $class): string|null {
		foreach ($this->_component as $name => $component) {
			if ($component->isClass($class)) {
				return $name;
			}
		}

		return null;
	}

	final public function getChildNames(string $class): array {
		$names = [];

		foreach ($this->_component as $name => $component) {
			if ($component->isClass($class)) {
				$names[] = $name;
			}
		}

		return $names;
	}
}

abstract class Variant extends Composite {
	protected string $_variant;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_variant = $state['_variant'];
	}

	final public function __clone(): void {
		foreach (\array_keys($this->_component) as $name) {
			$this->_component[$name] = clone $this->_component[$name];
		}
	}

	final public function __invoke(array $data, array $order=[]): void {
		$this->_component[$this->_variant]($data, $order);
	}

	final public function __call(string $name, array $data): bool {
		if (!isset($this->_component[$name])) {
			Component::error(Info::message('e_no_child', $name), Code::Component);
			return false;
		}

		$this->_variant = $name;

		if (isset($data[1])) {
			$this->_component[$name]($data[0], $data[1]);
		}
		elseif (isset($data[0])) {
			$this->_component[$name]($data[0]);
		}

		return true;
	}

	final public function __get(string $name): Component {
		if (isset($this->_component[$name])) {
			$this->_variant = $name;
			return $this->_component[$name];
		}

		Component::error(Info::message('e_no_child', $name), Code::Component);
		return Component::emulate();
	}

	final public function __unset(string $name): void {
		unset($this->_component[$name]);
	}

	final public function __set(string $name, int|float|string|array $value): void {
		$this->_component[$this->_variant]->$name = $value;
	}

	final public function common(string $name, int|float|string $value): void {
		foreach ($this->_component as $component) {
			$component->common($name, $value);
		}
	}
}

abstract class Text extends Component {
	use Childless;

	protected string $_text;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_text = $state['_text'];
	}

	final public function __invoke(array $data, array $order=[]): void {}
	final public function common(string $name, int|float|string $value): void {}
}

abstract class DependentText extends Text {
	use DependentComponent;

	public function isReady(): bool {
		return $this->_exert;
	}
}

final class Emulator extends Component {
	public function drop(): void {}
	public function isComponent(string $name): bool {return false;}
	public function getChild(string $class): Component {return $this;}
	public function getChildName(string $class): string|null {return null;}
	public function getChildNames(string $class): array {return [];}
	public function __call(string $name, array $data): bool {return false;}
	public function __get(string $name): Component {return $this;}
	public function __invoke(array $data, array $order=[]): void {}
	public function __isset(string $name): bool {return false;}
	public function __unset(string $name): void {}
	public function __set(string $name, string|int|float $value): void {}
	public function getResult(): string {return '';}
	public function getRawResult(): string {return '';}
	public function isReady(): bool {return true;}
	public function insert(string $text): void {}
	public function common(string $name, array|string|int|float $value): void {}
	public function ready(): void {}
	public function __toString(): string {return '';}
	public function force(string $name, string $text): bool {return true;}
}

#[\ultra\SetStateDirectly]
final class OriginalText extends Text {
	use InsertionStub;
	use ReadyText;
	use Result;
}

#[\ultra\SetStateDirectly]
final class FixedText extends DependentText implements Derivative {
	use InsertionStub;
	use DependentResult;
	use DependentTextResult;
	use TextMaster;
}

#[\ultra\SetStateDirectly]
final class WrappedOriginalText extends Text implements Derivative, Wrapped {
	use WrappedComponent;
	use ReadyText;
	use InsertionStub;
	use WrappedResult;
	use TextMaster;
}

#[\ultra\SetStateDirectly]
final class WrappedFixedText extends DependentText implements Derivative, Wrapped {
	use WrappedComponent;
	use InsertionStub;
	use DependentTextResult;
	use WrappedDependentResult;
	use TextMaster;
}

#[\ultra\SetStateDirectly]
final class Variator extends Variant {
	use ReadyVariant;
	use Result;
}

#[\ultra\SetStateDirectly]
final class WrappedVariator extends Variant implements Derivative, Wrapped {
	use WrappedComponent;
	use ReadyVariant;
	use WrappedResult;

	public function getOriginal(): Variator {
		$component = [];
		
		foreach (\array_keys($this->_component) as $name) {
			$component[$name] = clone $this->_component[$name];
		}

		return new Variator([
			'_class'     => $this->_class,
			'_name'      => $this->_name,
			'_component' => $component,
			'_variant'   => $this->_variant,
		]);
	}
}
