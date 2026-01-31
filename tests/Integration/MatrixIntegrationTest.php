<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Tests\Fixtures\Node\TestMatrix;
use Duon\Cms\Tests\Fixtures\Node\TestNodeWithMatrix;
use Duon\Cms\Tests\TestCase;

class MatrixIntegrationTest extends TestCase
{
	private function createContext(): \Duon\Cms\Context
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

		return new \Duon\Cms\Context(
			$this->db(),
			$request,
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
	}

	public function testMyMatrixIntegration(): void
	{
		$context = $this->createContext();
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		$node = new TestNodeWithMatrix($context, $finder, ['content' => [
			'title' => ['type' => 'text', 'value' => ['en' => 'Test Node']],
			'matrix' => ['type' => 'matrix', 'value' => [
				[
					'title' => ['type' => 'text', 'value' => ['en' => 'First Item']],
					'content' => ['type' => 'grid', 'columns' => 12, 'value' => ['en' => []]],
				],
				[
					'title' => ['type' => 'text', 'value' => ['en' => 'Second Item']],
					'content' => ['type' => 'grid', 'columns' => 12, 'value' => ['en' => []]],
				],
			]],
		]]);

	// Test that matrix field exists and is accessible
		$matrixField = $node->getField('matrix');
		$this->assertInstanceOf(\Duon\Cms\Field\Matrix::class, $matrixField);
		$matrixValue = $node->matrix;
		$this->assertInstanceOf(\Duon\Cms\Value\MatrixValue::class, $matrixValue);

		// Test matrix iteration
		$items = [];
		foreach ($matrixValue as $item) {
			$items[] = $item;
		}

		$this->assertCount(2, $items);
		$this->assertInstanceOf(\Duon\Cms\Value\MatrixItem::class, $items[0]);
		$this->assertInstanceOf(\Duon\Cms\Value\MatrixItem::class, $items[1]);

		// Test subfield access
		$firstItem = $matrixValue->first();
		$this->assertNotNull($firstItem);
		$this->assertEquals('First Item', $firstItem->title->unwrap());
		$this->assertInstanceOf(\Duon\Cms\Value\Grid::class, $firstItem->content);

		// Test matrix methods
		$this->assertEquals(2, $matrixValue->count());
		$this->assertEquals('First Item', $matrixValue->first()->title->unwrap());
		$this->assertEquals('Second Item', $matrixValue->last()->title->unwrap());
	}

	public function testMyMatrixStructure(): void
	{
		$context = $this->createContext();
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		$matrix = new TestMatrix('test_matrix', new TestNodeWithMatrix($context, $finder, ['content' => []]), new \Duon\Cms\Value\ValueContext('test_matrix', []));
		
		// Call value() to initialize subfields
		$matrixValue = $matrix->value();
		
		$structure = $matrix->structure();
		$this->assertEquals('matrix', $structure['type']);
		$this->assertIsArray($structure['value']);

		$subfields = $matrix->getSubfields();
		$this->assertArrayHasKey('title', $subfields);
		$this->assertArrayHasKey('content', $subfields);
		$this->assertInstanceOf(\Duon\Cms\Field\Text::class, $subfields['title']);
		$this->assertInstanceOf(\Duon\Cms\Field\Grid::class, $subfields['content']);
	}

	public function testMatrixSubfieldTranslateStructure(): void
	{
		$context = $this->createContext();
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		// Create matrix with one empty item
		$matrix = new TestMatrix(
			'test_matrix',
			new TestNodeWithMatrix($context, $finder, ['content' => []]),
			new \Duon\Cms\Value\ValueContext('test_matrix', [
				'type' => 'matrix',
				'value' => [
					[
						'title' => ['type' => 'text', 'value' => ''],
						'content' => ['type' => 'grid', 'columns' => 12, 'value' => []],
					],
				],
			])
		);

		$structure = $matrix->structure();

		// Subfields with #[Translate] should have locale keys in their value
		$this->assertCount(1, $structure['value']);
		$titleValue = $structure['value'][0]['title']['value'];
		
		// Should have locale structure, not empty string
		$this->assertIsArray($titleValue, 'Translatable subfield should have array value with locale keys');
		$this->assertArrayHasKey('en', $titleValue);
		$this->assertArrayHasKey('de', $titleValue);
	}
}