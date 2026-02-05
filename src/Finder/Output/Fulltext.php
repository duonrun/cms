<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\Input\TokenType;
use Duon\Cms\Finder\ParamCounter;

/**
 * Output for fulltext search filtering in the Finder DSL.
 *
 * DSL syntax:
 *   - `fulltext = "search query"` - fulltext search across all locales
 *   - `fulltext.en = "search query"` - fulltext search in specific locale
 *
 * Generates a subquery against the fulltext index.
 *
 * PostgreSQL: n.node IN (SELECT node FROM cms.fulltext WHERE document @@ websearch_to_tsquery(...))
 * SQLite: n.node IN (SELECT node FROM cms_fulltext_idx idx JOIN cms_fulltext_fts fts ON ...)
 */
final readonly class Fulltext extends Expression implements Output
{
	public function __construct(
		public Token $left,
		public Token $operator,
		public Token $right,
		public ?Context $context = null,
		public ?ParamCounter $paramCounter = null,
	) {}

	protected function getDialect(): ?SqlDialect
	{
		return $this->context?->dialect();
	}

	public function get(): CompiledQuery
	{
		$dialect = $this->getDialect();
		if ($dialect === null) {
			throw new ParserOutputException($this->left, 'Dialect is required for fulltext search');
		}

		// Only = operator is supported for fulltext
		if ($this->operator->type !== TokenType::Equal) {
			throw new ParserOutputException(
				$this->operator,
				'Only the = operator is supported for fulltext search',
			);
		}

		// Determine which side has the fulltext token
		$fulltextToken = $this->left->type === TokenType::Fulltext ? $this->left : $this->right;
		$valueToken = $this->left->type === TokenType::Fulltext ? $this->right : $this->left;

		// Parse locale from fulltext.xx syntax
		$locale = $this->parseLocale($fulltextToken->lexeme);

		// Get the search query value
		if ($valueToken->type !== TokenType::String) {
			throw new ParserOutputException(
				$valueToken,
				'Fulltext search requires a string query',
			);
		}

		$params = [];
		$paramIndex = $this->paramCounter?->current() ?? 0;

		$paramName = $this->addParam($valueToken->lexeme, $params, $paramIndex);

		// Generate the fulltext search condition using the dialect
		$sql = $dialect->fulltext('n.node', $paramName, $locale);

		// Update the shared counter if we have one
		if ($this->paramCounter !== null) {
			while ($this->paramCounter->current() < $paramIndex) {
				$this->paramCounter->next();
			}
		}

		return new CompiledQuery($sql, $params);
	}

	/**
	 * Parse locale from fulltext token (e.g., "fulltext.en" -> "en", "fulltext" -> null).
	 */
	private function parseLocale(string $fulltextLexeme): ?string
	{
		if ($fulltextLexeme === 'fulltext') {
			return null;
		}

		// fulltext.xx format
		if (str_starts_with($fulltextLexeme, 'fulltext.')) {
			$locale = substr($fulltextLexeme, 9);

			// Handle ? placeholder for current locale
			if ($locale === '?') {
				return $this->context?->localeId();
			}

			return $locale;
		}

		return null;
	}
}
