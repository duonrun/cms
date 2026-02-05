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
 * Output for URL path filtering in the Finder DSL.
 *
 * DSL syntax:
 *   - `path = "/some/path"` - filter by exact path
 *   - `path.en = "/english/path"` - filter by path in specific locale
 *   - `path ~~ "/prefix%"` - filter with LIKE pattern
 *
 * Generates a subquery against the urlpaths table.
 */
final readonly class UrlPath extends Expression implements Output
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
		$params = [];
		$paramIndex = $this->paramCounter?->current() ?? 0;

		// Determine which side has the path token
		$pathToken = $this->left->type === TokenType::Path ? $this->left : $this->right;
		$valueToken = $this->left->type === TokenType::Path ? $this->right : $this->left;

		// Parse locale from path.xx syntax
		$locale = $this->parseLocale($pathToken->lexeme);

		// Get the comparison value
		$value = $this->getComparisonValue($valueToken, $params, $paramIndex);

		// Get the operator SQL
		$operatorSql = $this->getComparisonOperator();

		// Build the EXISTS subquery
		$table = $dialect?->table('cms', 'urlpaths') ?? 'cms.urlpaths';

		$conditions = [
			'up.node = n.node',
			'up.inactive IS NULL',
		];

		// Add path comparison
		if ($operatorSql === 'IS NULL') {
			$conditions[] = 'up.path IS NULL';
		} elseif ($operatorSql === 'IS NOT NULL') {
			$conditions[] = 'up.path IS NOT NULL';
		} elseif (in_array($this->operator->type, [TokenType::Like, TokenType::Unlike], true)) {
			$conditions[] = $dialect !== null
				? $dialect->like('up.path', $value)
				: "up.path LIKE {$value}";
		} elseif (in_array($this->operator->type, [TokenType::ILike, TokenType::IUnlike], true)) {
			$conditions[] = $dialect !== null
				? $dialect->ilike('up.path', $value)
				: "up.path ILIKE {$value}";
		} else {
			$conditions[] = "up.path {$operatorSql} {$value}";
		}

		// Add locale filter if specified
		if ($locale !== null) {
			$localeParam = $this->addParam($locale, $params, $paramIndex);
			$conditions[] = "up.locale = {$localeParam}";
		}

		$whereClause = implode(' AND ', $conditions);
		$sql = "EXISTS (SELECT 1 FROM {$table} up WHERE {$whereClause})";

		// Handle negation for NOT LIKE / NOT IN operators
		if (in_array($this->operator->type, [TokenType::Unlike, TokenType::IUnlike, TokenType::NotIn], true)) {
			$sql = "NOT {$sql}";
		}

		// Update the shared counter if we have one
		if ($this->paramCounter !== null) {
			while ($this->paramCounter->current() < $paramIndex) {
				$this->paramCounter->next();
			}
		}

		return new CompiledQuery($sql, $params);
	}

	/**
	 * Parse locale from path token (e.g., "path.en" -> "en", "path" -> null).
	 */
	private function parseLocale(string $pathLexeme): ?string
	{
		if ($pathLexeme === 'path') {
			return null;
		}

		// path.xx format
		if (str_starts_with($pathLexeme, 'path.')) {
			$locale = substr($pathLexeme, 5);

			// Handle ? placeholder for current locale
			if ($locale === '?') {
				return $this->context?->localeId();
			}

			return $locale;
		}

		return null;
	}

	/**
	 * Get the comparison value as a parameter placeholder or literal.
	 *
	 * @param array<string, scalar|null> $params
	 */
	private function getComparisonValue(Token $token, array &$params, int &$paramIndex): string
	{
		return match ($token->type) {
			TokenType::String => $this->addParam($token->lexeme, $params, $paramIndex),
			TokenType::Number => $token->lexeme,
			TokenType::Null => 'NULL',
			default => throw new ParserOutputException(
				$token,
				'URL path comparisons require a string, number, or null value',
			),
		};
	}

	/**
	 * Get the SQL comparison operator.
	 */
	private function getComparisonOperator(): string
	{
		return match ($this->operator->type) {
			TokenType::Equal => '=',
			TokenType::Unequal => '!=',
			TokenType::Greater => '>',
			TokenType::GreaterEqual => '>=',
			TokenType::Less => '<',
			TokenType::LessEqual => '<=',
			TokenType::Like, TokenType::Unlike => 'LIKE',
			TokenType::ILike, TokenType::IUnlike => 'ILIKE',
			default => throw new ParserOutputException(
				$this->operator,
				'Invalid operator for URL path comparison',
			),
		};
	}
}
