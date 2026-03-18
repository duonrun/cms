<?php

declare(strict_types=1);

namespace Duon\Cms\Db;

use Duon\Cms\Context;
use Duon\Cms\Finder\Condition\Part;
use Duon\Cms\Finder\Input\TokenType;

interface Dialect
{
	public function table(string $name): string;

	public function compileField(string $fieldName, string $tableField, bool $asJson = false): string;

	public function compileConditionPart(Part $part, Context $context, array $builtins): string;

	public function compileSearchMatch(string $expression, string $needle): string;

	public function keyword(string $keyword): string;

	public function sqlOperator(TokenType $type): string;
}
