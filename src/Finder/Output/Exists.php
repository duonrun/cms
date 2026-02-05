<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Context;
use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Input\Token;

/**
 * Output for field existence checks in the Finder DSL.
 *
 * DSL syntax: `field` (alone, without an operator)
 *
 * Checks if a JSON field exists in the node content.
 * For example, `title` checks if the 'title' field exists.
 */
final readonly class Exists extends Expression implements Output
{
	public function __construct(
		private Token $token,
		private ?Context $context = null,
	) {}

	protected function getDialect(): ?SqlDialect
	{
		return $this->context?->dialect();
	}

	public function get(): CompiledQuery
	{
		$dialect = $this->getDialect();

		// Default to PostgreSQL-style if no dialect (for backwards compatibility)
		if ($dialect === null) {
			return CompiledQuery::sql("n.content ? '{$this->token->lexeme}'");
		}

		// Get the field path - typically just the field name for existence check
		// The field stores its value at field.value in the JSON structure
		$fieldName = $this->token->lexeme;

		// Check for existence of the field itself (not field.value)
		// since we want to know if the field is present at all
		$sql = $dialect->jsonExists('n.content', $fieldName);

		return CompiledQuery::sql($sql);
	}
}
