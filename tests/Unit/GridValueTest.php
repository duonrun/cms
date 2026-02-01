<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Context;
use Duon\Cms\Node\Document;
use Duon\Cms\Tests\Fixtures\Field\TestGrid;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\Grid as GridValue;
use Duon\Cms\Value\ValueContext;

/**
 * @internal
 *
 * @coversNothing
 */
final class GridValueTest extends TestCase
{
	private function createContext(): Context
	{
		$psrRequest = $this->psrRequest();
		$locales = new \Duon\Cms\Locales();
		$locales->add('en', title: 'English', domains: ['www.example.com']);
		$locales->add('de', title: 'Deutsch', domains: ['www.example.de'], fallback: 'en');

		$psrRequest = $psrRequest
			->withAttribute('locales', $locales)
			->withAttribute('locale', $locales->get('en'))
			->withAttribute('defaultLocale', $locales->getDefault());

		$request = new \Duon\Core\Request($psrRequest);

		return new Context(
			$this->db(),
			$request,
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
	}

	private function createNode(Context $context): Document
	{
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		return new class ($context, $finder, ['content' => []]) extends Document {
			public function title(): string
			{
				return 'Test';
			}
		};
	}

	private function createGridValue(array $data): GridValue
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new TestGrid('grid', $node, new ValueContext('grid', $data));

		return $field->value();
	}

	public function testUnwrapReturnsColumnsAndPreparedData(): void
	{
		$grid = $this->createGridValue([
			'columns' => 12,
			'value' => [
				'en' => [
					['type' => 'text', 'value' => 'Hello', 'colspan' => 12, 'rowspan' => 1],
				],
			],
		]);

		$unwrapped = $grid->unwrap();
		$this->assertSame(12, $unwrapped['columns']);
		$this->assertIsIterable($unwrapped['data']);
	}

	public function testHasImageDetectsImageItems(): void
	{
		$grid = $this->createGridValue([
			'columns' => 12,
			'value' => [
				['type' => 'text', 'value' => 'Hello', 'colspan' => 12, 'rowspan' => 1],
				['type' => 'image', 'files' => [['file' => 'test.jpg']], 'colspan' => 12, 'rowspan' => 1],
			],
		]);

		$this->assertTrue($grid->hasImage());
	}

	public function testExcerptReturnsEmptyWhenNoHtml(): void
	{
		$grid = $this->createGridValue([
			'columns' => 12,
			'value' => [
				['type' => 'text', 'value' => 'Hello', 'colspan' => 12, 'rowspan' => 1],
			],
		]);

		$this->assertSame('', $grid->excerpt());
	}

	public function testIssetReturnsFalseForEmptyValue(): void
	{
		$grid = $this->createGridValue([
			'columns' => 12,
			'value' => [],
		]);

		$this->assertFalse($grid->isset());
	}
}
