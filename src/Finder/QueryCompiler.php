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

	public function compile(string $query, string $paramPrefix = 'q'): CompiledQuery
	{
		$params = new QueryParams($paramPrefix);
		$parser = new QueryParser($this->context, $this->builtins);

		return new CompiledQuery(
			$this->build($parser->parse($query), $params),
			$params->all(),
		);
	}

	private function build(array $parserOutput, QueryParams $params): string
	{
		if (count($parserOutput) === 0) {
			return '';
		}

		$clause = '';

		foreach ($parserOutput as $output) {
			$clause .= $output->get($params);
		}

		return $clause;
	}

	private function translateKeyword(string $keyword): string
	{
		return match ($keyword) {
			'now' => 'NOW()',
			'fulltext' => 'tsv websearch_to_tsquery',
		};
	}
}
