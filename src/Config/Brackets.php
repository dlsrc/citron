<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Citron template engine.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Citron;

enum Brackets: string {
	case Curly       = '{}';
	case Square      = '[]';
	case Round       = '()';
	case Angle       = '<>';
	case Braces      = '{{}}';
	case Brackets    = '[[]]';
	case Parentheses = '(())';
	case Chevrons    = '<<>>';
	case CurliyColon = '{::}';
	case SquareColon = '[::]';
	case RoundColon  = '(::)';
	case AngleColon  = '<::>';
	case CurliyPipe  = '{||}';
	case SquarePipe  = '[||]';
	case RoundPipe   = '(||)';
	case AnglePipe   = '<||>';

	public function open(): string {
		return match($this) {
			self::Curly       => '\x7B',
			self::Square      => '\x5B',
			self::Round       => '\x28',
			self::Angle       => '\x3C',
			self::Braces      => '\x7B{2}',
			self::Brackets    => '\x5B{2}',
			self::Parentheses => '\x28{2}',
			self::Chevrons    => '\x3C{2}',
			self::CurliyColon => '\x7B\x3A',
			self::SquareColon => '\x5B\x3A',
			self::RoundColon  => '\x28\x3A',
			self::AngleColon  => '\x3C\x3A',
			self::CurliyPipe  => '\x7B\x7C',
			self::SquarePipe  => '\x5B\x7C',
			self::RoundPipe   => '\x28\x7C',
			self::AnglePipe   => '\x3C\x7C',
		};
	}

	public function close(): string {
		return match($this) {
			self::Curly       => '\x7D',
			self::Square      => '\x5D',
			self::Round       => '\x29',
			self::Angle       => '\x3E',
			self::Braces      => '\x7D{2}',
			self::Brackets    => '\x5D{2}',
			self::Parentheses => '\x29{2}',
			self::Chevrons    => '\x3E{2}',
			self::CurliyColon => '\x3A\x7D',
			self::SquareColon => '\x3A\x5D',
			self::RoundColon  => '\x3A\x29',
			self::AngleColon  => '\x3A\x3E',
			self::CurliyPipe  => '\x7C\x7B',
			self::SquarePipe  => '\x7C\x5B',
			self::RoundPipe   => '\x7C\x28',
			self::AnglePipe   => '\x7C\x3C',
		};
	}

	public function ignore(): string {
		return match($this) {
			self::Curly, self::Braces      => '\x7B\x7D',
			self::Square, self::Brackets   => '\x5B\x5D',
			self::Round, self::Parentheses => '\x28\x29',
			self::Angle, self::Chevrons    => '\x3C\x3E',
			self::CurliyColon => '\x7B\x3A\x7D',
			self::SquareColon => '\x5B\x3A\x5D',
			self::RoundColon  => '\x28\x3A\x29',
			self::AngleColon  => '\x3C\x3A\x3E',
			self::CurliyPipe  => '\x7B\x7C\x7D',
			self::SquarePipe  => '\x5B\x7C\x5D',
			self::RoundPipe   => '\x28\x7C\x29',
			self::AnglePipe   => '\x3C\x7C\x3E',
		};
	}

	public function apart(): array {
		return match($this) {
			self::Curly       => ['{', '}'],
			self::Square      => ['[', ']'],
			self::Round       => ['(', ')'],
			self::Angle       => ['<', '>'],
			self::Braces      => ['{{', '}}'],
			self::Brackets    => ['[[', ']]'],
			self::Parentheses => ['((', '))'],
			self::Chevrons    => ['<<', '>>'],
			self::CurliyColon => ['{:', ':}'],
			self::SquareColon => ['[:', ':]'],
			self::RoundColon  => ['(:', ':)'],
			self::AngleColon  => ['<:', ':>'],
			self::CurliyPipe  => ['{|', '|}'],
			self::SquarePipe  => ['[|', '|]'],
			self::RoundPipe   => ['(|', '|)'],
			self::AnglePipe   => ['<|', '|>'],
		};
	}
}
