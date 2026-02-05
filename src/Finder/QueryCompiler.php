<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Context;

final class QueryCompiler
{
	use CompilesField;

	public function __construct(
		private readonly Context $context,
		private readonly array $builtins,
	) {}

	public function compile(string $query): CompiledQuery
	{
		$paramCounter = new ParamCounter();
		$parser = new QueryParser($this->context, $this->builtins, $paramCounter);

		return $this->build($parser->parse($query));
	}

	/**
	 * @param array<\Duon\Cms\Finder\Output\Output> $parserOutput
	 */
	private function build(array $parserOutput): CompiledQuery
	{
		if (count($parserOutput) === 0) {
			return CompiledQuery::empty();
		}

		$result = CompiledQuery::empty();

		foreach ($parserOutput as $output) {
			$result = $result->merge($output->get());
		}

		return $result;
	}

	private function translateKeyword(string $keyword): string
	{
		return match ($keyword) {
			'now' => 'NOW()',
			'fulltext' => 'tsv websearch_to_tsquery',
		};
	}
}
