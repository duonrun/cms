<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Context;
use Duon\Cms\Db\Dialect;
use Duon\Cms\Db\Dialects;

final class QueryCompiler
{
	private readonly Dialect $dialect;

	public function __construct(
		private readonly Context $context,
		private readonly array $builtins,
	) {
		$this->dialect = Dialects::for($this->context->db);
	}

	public function compile(string $query): string
	{
		return $this->compileFragment($query)->sql;
	}

	public function compileFragment(string $query): SqlFragment
	{
		$parser = new QueryParser($this->context, $this->builtins);

		return $this->build($parser->parse($query));
	}

	private function build(array $parserOutput): SqlFragment
	{
		if (count($parserOutput) === 0) {
			return SqlFragment::empty();
		}

		$clause = '';

		foreach ($parserOutput as $output) {
			$clause .= $this->dialect->compileConditionPart($output, $this->context, $this->builtins);
		}

		return new SqlFragment($clause);
	}
}
