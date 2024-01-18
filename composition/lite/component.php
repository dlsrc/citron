<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace citron\lite;

use citron\Derivative;
use citron\Wrapped;
use citron\Childless;
use citron\Performance;
use citron\Composite;
use citron\Component;
use citron\DependentComponent;
use citron\IndependentComponent;
use citron\RootComponent;
use citron\WrappedComponent;
use citron\Result;
use citron\DependentResult;
use citron\WrappedResult;
use citron\WrappedDependentResult;
use ultra\SetStateDirectly as Direct;

trait Sequence {
	protected array $_ref;
	protected array $_chain;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_ref   = $state['_ref'];
		$this->_chain = $state['_chain'];

		foreach ($this->_ref as $k => $v) {
			$this->_chain[$k] =&$this->_chain[$v];
		}
	}

	final public function __invoke(array $data, array $order=[]): void {
		if (empty($order)) {
			foreach ($data as $name => $value) {
				if (isset($this->_chain[$name])) {
					$this->_chain[$name] = $value;
				}
			}
		}
		else {
			if (!array_is_list($data)) {
				$data = array_values($data);
			}

			foreach ($order as $id => $name) {
				if (isset($this->_chain[$name])) {
					$this->_chain[$name] = $data[$id];
				}
			}
		}

		$this->ready();
	}
}

trait Insertion {
	final public function __set(string $name, int|float|string $value): void {
		if (isset($this->_chain[$name])) {
			$this->_chain[$name] = $value;
		}
	}
}

trait InsertionMap {
	final public function __set(string $name, int|float|string|array $value): void {
		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$this->__set($name.'.'.$key, $val);
			}
		}
		elseif (isset($this->_chain[$name])) {
			$this->_chain[$name] = $value;
		}
	}
}

trait ReadyComposite {
	use IndependentComponent;

	public function ready(): void {
		$this->notify();
		$this->_result.= implode($this->_chain);
	}
}

trait ReadyLeaf {
	use IndependentComponent;

	public function ready(): void {
		$this->_result.= implode($this->_chain);
	}
}

trait DependentCompositeResult {
	final public function getRawResult(): string {
		if ($this->_exert) {
			$this->_exert = false;
			$this->notify();
			$this->_result = implode($this->_chain);
		}

		return $this->_result;
	}
}

trait DependentLeafResult {
	final public function getRawResult(): string {
		if ($this->_exert) {
			$this->_exert = false;
			$this->_result = implode($this->_chain);
		}

		return $this->_result;
	}
}

trait PerformerMaster {
	public function getOriginal(): Performer {
		$component = [];

		foreach (array_keys($this->_component) as $name) {
			$component[$name] = clone $this->_component[$name];
		}

		if (str_ends_with(__CLASS__, 'Map')) {
			$class = OriginalCompositeMap::class;
		}
		else {
			$class = OriginalComposite::class;
		}

		return new $class([
			'_chain'     => $this->_chain,
			'_ref'       => $this->_ref,
			'_class'     => $this->_class,
			'_name'      => $this->_name,
			'_component' => $component,
		]);
	}
}

trait LeafMaster {
	public function getOriginal(): Leaf {
		if (str_ends_with(__CLASS__, 'Map')) {
			$class = OriginalLeafMap::class;
		}
		else {
			$class = OriginalLeaf::class;
		}

		return new $class([
			'_chain'  => $this->_chain,
			'_ref'    => $this->_ref,
			'_class'  => $this->_class,
			'_name'   => $this->_name,
		]);
	}
}

abstract class Leaf extends Component {
	use Sequence;
	use Childless;

	final public function common(string $name, int|float|string $value): void {
		if (isset($this->_chain[$name])) {
			$this->_chain[$name] = $value;
		}
	}
}

abstract class Performer extends Composite {
	use Sequence;
	use Performance;

	final public function __clone(): void {
		foreach (array_keys($this->_component) as $name) {
			$this->_component[$name] = clone $this->_component[$name];
		}
	}

	final public function __unset(string $name): void {
		if (isset($this->_component[$name])) {
			$this->_chain[Component::NS.$name] = $this->_component[$name]->getResult();
			unset($this->_component[$name]);
		}
	}

	final public function common(string $name, int|float|string $value): void {
		if (isset($this->_chain[$name])) {
			$this->_chain[$name] = $value;
		}

		foreach ($this->_component as $component) {
			$component->common($name, $value);
		}
	}

	final protected function notify(): void {
		foreach ($this->_component as $component) {
			$name = Component::NS.$component->getName();
			$this->_chain[$name] = $component->getResult();
			$component->update();
		}
	}
}

abstract class DependentLeaf extends Leaf {
	use DependentComponent;

	public function isReady(): bool {
		return $this->_exert;
	}
}

abstract class DependentPerformer extends Performer {
	use DependentComponent;

	public function isReady(): bool {
		foreach ($this->_component as $component) {
			if ($component->isReady()) return true;
		}

		return false;
	}
}

#[Direct]
final class OriginalComposite extends Performer {
	use Insertion;
	use ReadyComposite;
	use Result;
}

#[Direct]
final class OriginalCompositeMap extends Performer {
	use InsertionMap;
	use ReadyComposite;
	use Result;
}

#[Direct]
final class FixedComposite extends DependentPerformer implements Derivative {
	use Insertion;
	use DependentCompositeResult;
	use PerformerMaster;
	use DependentResult;
}

#[Direct]
final class FixedCompositeMap extends DependentPerformer implements Derivative {
	use InsertionMap;
	use DependentCompositeResult;
	use PerformerMaster;
	use DependentResult;
}

#[Direct]
final class WrappedOriginalComposite extends Performer implements Derivative, Wrapped {
	use Insertion;
	use ReadyComposite;
	use PerformerMaster;
	use WrappedComponent;
	use WrappedResult;
}

#[Direct]
final class WrappedOriginalCompositeMap extends Performer implements Derivative, Wrapped {
	use InsertionMap;
	use ReadyComposite;
	use PerformerMaster;
	use WrappedComponent;
	use WrappedResult;
}

#[Direct]
final class WrappedFixedComposite extends DependentPerformer implements Derivative, Wrapped {
	use Insertion;
	use DependentCompositeResult;
	use PerformerMaster;
	use WrappedComponent;
	use WrappedDependentResult;
}

#[Direct]
final class WrappedFixedCompositeMap extends DependentPerformer implements Derivative, Wrapped {
	use InsertionMap;
	use DependentCompositeResult;
	use PerformerMaster;
	use WrappedComponent;
	use WrappedDependentResult;
}

#[Direct]
final class OriginalLeaf extends Leaf {
	use Insertion;
	use ReadyLeaf;
	use Result;
}

#[Direct]
final class OriginalLeafMap extends Leaf {
	use InsertionMap;
	use ReadyLeaf;
	use Result;
}

#[Direct]
final class FixedLeaf extends DependentLeaf implements Derivative {
	use Insertion;
	use DependentLeafResult;
	use LeafMaster;
	use DependentResult;
}

#[Direct]
final class FixedLeafMap extends DependentLeaf implements Derivative {
	use InsertionMap;
	use DependentLeafResult;
	use LeafMaster;
	use DependentResult;
}

#[Direct]
final class WrappedOriginalLeaf extends Leaf implements Derivative, Wrapped {
	use Insertion;
	use ReadyLeaf;
	use LeafMaster;
	use WrappedComponent;
	use WrappedResult;
}

#[Direct]
final class WrappedOriginalLeafMap extends Leaf implements Derivative, Wrapped {
	use InsertionMap;
	use ReadyLeaf;
	use LeafMaster;
	use WrappedComponent;
	use WrappedResult;
}

#[Direct]
final class WrappedFixedLeaf extends DependentLeaf implements Derivative, Wrapped {
	use Insertion;
	use DependentLeafResult;
	use LeafMaster;
	use WrappedComponent;
	use WrappedDependentResult;
}

#[Direct]
final class WrappedFixedLeafMap extends DependentLeaf implements Derivative, Wrapped {
	use InsertionMap;
	use DependentLeafResult;
	use LeafMaster;
	use WrappedComponent;
	use WrappedDependentResult;
}

#[Direct]
final class Complex extends Performer {
	use RootComponent;
	use IndependentComponent;

	protected array  $_global;
	protected string $_first;
	protected string $_last;

	public function __construct(array $state) {
		parent::__construct($state);
		$this->_global = $state['_global'];
//		$this->_first  = $state['_first'];
//		$this->_last   = $state['_last'];
		$this->_first  = '{%';
		$this->_last   = '}';
}

	public function __set(string $name, int|float|string|array $value): void {
		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$this->__set($name.'.'.$key, $val);
			}
		}
		elseif (isset($this->_chain[$name])) {
			$this->_chain[$name] = $value;
		}
		else {
			$this->_global[$this->_first.$name.$this->_last] = $value;
		}
	}

	public function ready(): void {
		if ('' == $this->_result) {
			$this->notify();
			$this->_result = implode($this->_chain);
			
			if (!empty($this->_global)) {
				$this->_result = str_replace(
					array_keys($this->_global),
					$this->_global,
					$this->_result
				);
			}
		}
	}

	public function force(string $name, string $text): bool {
		if (!isset($this->_component[$name])) {
			return false;
		}

		$this->_component[$name]->insert($text);
		return true;
	}
}

#[Direct]
final class Document extends Leaf {
	use InsertionMap;
	use RootComponent;
	use IndependentComponent;

	public function ready(): void {
		$this->_result = implode($this->_chain);
	}
}
