<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Tests\IntegrationTestCase;

/**
 * DSL Operator Matrix Test.
 *
 * This test class exercises every Finder DSL operator on the current database
 * backend. Run with CMS_TEST_DRIVER=sqlite or CMS_TEST_DRIVER=pgsql to verify
 * parity between backends.
 *
 * Operators tested:
 * - Comparison: =, !=, <, >, <=, >=
 * - Pattern: ~~ (LIKE), ~~* (ILIKE)
 * - Regex: ~, ~*, !~, !~*
 * - Membership: @ (IN), !@ (NOT IN)
 * - Existence: field presence check
 * - Logical: & (AND), | (OR), parentheses
 * - Path: path = ..., path.locale = ...
 *
 * @internal
 */
final class DslOperatorTest extends IntegrationTestCase
{
	private int $typeId;

	protected function setUp(): void
	{
		parent::setUp();

		// Create a test type for DSL operator tests
		$this->typeId = $this->createTestType('dsl-test-page', 'page');

		// Create test nodes with various content patterns
		$this->createTestNode([
			'uid' => 'dsl-node-alpha',
			'type' => $this->typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Alpha Title', 'de' => 'Alpha Titel']],
				'rating' => ['type' => 'number', 'value' => 5],
				'category' => ['type' => 'text', 'value' => 'electronics'],
				'tags' => ['type' => 'text', 'value' => 'featured,sale'],
			],
		]);

		$this->createTestNode([
			'uid' => 'dsl-node-beta',
			'type' => $this->typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Beta Product', 'de' => 'Beta Produkt']],
				'rating' => ['type' => 'number', 'value' => 3],
				'category' => ['type' => 'text', 'value' => 'clothing'],
				'tags' => ['type' => 'text', 'value' => 'new'],
			],
		]);

		$this->createTestNode([
			'uid' => 'dsl-node-gamma',
			'type' => $this->typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'GAMMA Special', 'de' => 'GAMMA Spezial']],
				'rating' => ['type' => 'number', 'value' => 8],
				'category' => ['type' => 'text', 'value' => 'electronics'],
			],
		]);

		// Create a node with a URL path for path filtering tests
		$nodeId = $this->createTestNode([
			'uid' => 'dsl-node-with-path',
			'type' => $this->typeId,
			'content' => [
				'title' => ['type' => 'text', 'value' => ['en' => 'Path Test', 'de' => 'Pfad Test']],
				'rating' => ['type' => 'number', 'value' => 7],
				'category' => ['type' => 'text', 'value' => 'services'],
			],
		]);
		$this->createTestPath($nodeId, '/products/special', 'en');
		$this->createTestPath($nodeId, '/produkte/spezial', 'de');
	}

	// ========================================================================
	// Comparison Operators: =, !=, <, >, <=, >=
	// ========================================================================

	public function testEqualOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("category = 'electronics'"));

		$this->assertCount(2, $nodes);
		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-alpha', $uids);
		$this->assertContains('dsl-node-gamma', $uids);
	}

	public function testNotEqualOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("category != 'electronics'"));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-beta', $uids);
		$this->assertContains('dsl-node-with-path', $uids);
		$this->assertNotContains('dsl-node-alpha', $uids);
		$this->assertNotContains('dsl-node-gamma', $uids);
	}

	public function testLessThanOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter('rating < 5'));

		$this->assertCount(1, $nodes);
		$this->assertEquals('dsl-node-beta', $nodes[0]->uid());
	}

	public function testGreaterThanOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter('rating > 5'));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-gamma', $uids);
		$this->assertContains('dsl-node-with-path', $uids);
		$this->assertNotContains('dsl-node-alpha', $uids);
	}

	public function testLessThanOrEqualOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter('rating <= 5'));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-alpha', $uids);
		$this->assertContains('dsl-node-beta', $uids);
	}

	public function testGreaterThanOrEqualOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter('rating >= 7'));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-gamma', $uids);
		$this->assertContains('dsl-node-with-path', $uids);
	}

	// ========================================================================
	// Pattern Operators: ~~ (LIKE), ~~* (ILIKE)
	// ========================================================================

	public function testLikeOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("title.en ~~ '%Title%'"));

		$this->assertCount(1, $nodes);
		$this->assertEquals('dsl-node-alpha', $nodes[0]->uid());
	}

	public function testIlikeOperator(): void
	{
		$finder = $this->createFinder();
		// GAMMA is uppercase in the data, gamma is lowercase in the pattern
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("title.en ~~* '%gamma%'"));

		$this->assertCount(1, $nodes);
		$this->assertEquals('dsl-node-gamma', $nodes[0]->uid());
	}

	public function testLikeWithWildcardStart(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("title.en ~~ '%Product'"));

		$this->assertCount(1, $nodes);
		$this->assertEquals('dsl-node-beta', $nodes[0]->uid());
	}

	// ========================================================================
	// Regex Operators: ~, ~*, !~, !~*
	// ========================================================================

	public function testRegexCaseSensitive(): void
	{
		$finder = $this->createFinder();
		// Match nodes where title.en contains "Alpha" or "Beta" (case-sensitive)
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("title.en ~ 'Alpha|Beta'"));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-alpha', $uids);
		$this->assertContains('dsl-node-beta', $uids);
		$this->assertNotContains('dsl-node-gamma', $uids);
	}

	public function testRegexCaseInsensitive(): void
	{
		$finder = $this->createFinder();
		// Match nodes where title.en matches "special" case-insensitively
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("title.en ~* 'special'"));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-gamma', $uids);
	}

	public function testRegexNegated(): void
	{
		$finder = $this->createFinder();
		// Match nodes where title.en does NOT match "Alpha"
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("title.en !~ 'Alpha'"));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertNotContains('dsl-node-alpha', $uids);
		$this->assertContains('dsl-node-beta', $uids);
		$this->assertContains('dsl-node-gamma', $uids);
	}

	public function testRegexNegatedCaseInsensitive(): void
	{
		$finder = $this->createFinder();
		// Match nodes where title.en does NOT match "gamma" (case-insensitive)
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("title.en !~* 'gamma'"));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertNotContains('dsl-node-gamma', $uids);
		$this->assertContains('dsl-node-alpha', $uids);
	}

	// ========================================================================
	// Membership Operators: @ (IN), !@ (NOT IN)
	// ========================================================================

	public function testInOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("category @ ['electronics', 'services']"));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-alpha', $uids);
		$this->assertContains('dsl-node-gamma', $uids);
		$this->assertContains('dsl-node-with-path', $uids);
		$this->assertNotContains('dsl-node-beta', $uids);
	}

	public function testNotInOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("category !@ ['electronics', 'services']"));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-beta', $uids);
		$this->assertNotContains('dsl-node-alpha', $uids);
		$this->assertNotContains('dsl-node-gamma', $uids);
	}

	// ========================================================================
	// Field Existence
	// ========================================================================

	public function testFieldExists(): void
	{
		$finder = $this->createFinder();
		// Only alpha and beta have "tags" field
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter('tags'));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-alpha', $uids);
		$this->assertContains('dsl-node-beta', $uids);
		$this->assertNotContains('dsl-node-gamma', $uids);
	}

	// ========================================================================
	// Logical Operators: & (AND), | (OR), parentheses
	// ========================================================================

	public function testAndOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("category = 'electronics' & rating > 5"));

		$this->assertCount(1, $nodes);
		$this->assertEquals('dsl-node-gamma', $nodes[0]->uid());
	}

	public function testOrOperator(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("category = 'clothing' | rating >= 8"));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-beta', $uids);
		$this->assertContains('dsl-node-gamma', $uids);
	}

	public function testParenthesesGrouping(): void
	{
		$finder = $this->createFinder();
		// (electronics OR services) AND rating >= 7
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("(category = 'electronics' | category = 'services') & rating >= 7"));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-gamma', $uids);
		$this->assertContains('dsl-node-with-path', $uids);
		$this->assertNotContains('dsl-node-alpha', $uids);
	}

	public function testComplexNestedExpression(): void
	{
		$finder = $this->createFinder();
		// ((electronics AND rating > 4) OR clothing) AND title exists
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("((category = 'electronics' & rating > 4) | category = 'clothing') & title"));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-alpha', $uids);
		$this->assertContains('dsl-node-beta', $uids);
		$this->assertContains('dsl-node-gamma', $uids);
		$this->assertNotContains('dsl-node-with-path', $uids);
	}

	// ========================================================================
	// Path Filtering
	// ========================================================================

	public function testPathEquals(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("path = '/products/special'"));

		$this->assertCount(1, $nodes);
		$this->assertEquals('dsl-node-with-path', $nodes[0]->uid());
	}

	public function testPathWithLocale(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("path.de = '/produkte/spezial'"));

		$this->assertCount(1, $nodes);
		$this->assertEquals('dsl-node-with-path', $nodes[0]->uid());
	}

	public function testPathLike(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("path ~~ '/products/%'"));

		$this->assertCount(1, $nodes);
		$this->assertEquals('dsl-node-with-path', $nodes[0]->uid());
	}

	public function testPathNotFound(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("path = '/nonexistent'"));

		$this->assertEmpty($nodes);
	}

	// ========================================================================
	// Locale Wildcard (field.*)
	// ========================================================================

	public function testWildcardLocaleMatch(): void
	{
		$finder = $this->createFinder();
		// Match if ANY locale of title contains "Alpha" or "Produkt"
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("title.* ~~ '%Produkt%'"));

		// Beta has "Beta Produkt" in German
		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-beta', $uids);
	}

	// ========================================================================
	// Edge Cases
	// ========================================================================

	public function testEmptyResultSet(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter("category = 'nonexistent'"));

		$this->assertEmpty($nodes);
	}

	public function testMultipleConditionsWithSameField(): void
	{
		$finder = $this->createFinder();
		$nodes = iterator_to_array($finder->nodes()
			->types('dsl-test-page')
			->filter('rating >= 3 & rating <= 7'));

		$uids = array_map(fn($n) => $n->uid(), $nodes);
		$this->assertContains('dsl-node-alpha', $uids);
		$this->assertContains('dsl-node-beta', $uids);
		$this->assertContains('dsl-node-with-path', $uids);
		$this->assertNotContains('dsl-node-gamma', $uids); // rating = 8
	}
}
