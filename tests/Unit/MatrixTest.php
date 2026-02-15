<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Field\Matrix;
use Duon\Cms\Node\NodeFieldOwner;
use Duon\Cms\Tests\Fixtures\Field\TestMatrix;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\MatrixValue;

class MatrixTest extends TestCase
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

	public function testMatrixFieldCreation(): void
	{
		$context = $this->createContext();
		$owner = new NodeFieldOwner($context, 'test-node');

		$matrix = new TestMatrix('test_matrix', $owner, new \Duon\Cms\Value\ValueContext('test_matrix', []));

		$this->assertInstanceOf(Matrix::class, $matrix);
		$this->assertInstanceOf(MatrixValue::class, $matrix->value());
		$this->assertIsArray($matrix->getSubfields());
		$this->assertArrayHasKey('title', $matrix->getSubfields());
		$this->assertArrayHasKey('content', $matrix->getSubfields());
	}

	public function testMatrixStructure(): void
	{
		$context = $this->createContext();
		$owner = new NodeFieldOwner($context, 'test-node');

		$matrix = new TestMatrix('test_matrix', $owner, new \Duon\Cms\Value\ValueContext('test_matrix', []));
		$structure = $matrix->structure();

		$this->assertEquals('matrix', $structure['type']);
		$this->assertIsArray($structure['value']);
	}

	public function testMatrixSchema(): void
	{
		$context = $this->createContext();
		$owner = new NodeFieldOwner($context, 'test-node');

		$matrix = new TestMatrix('test_matrix', $owner, new \Duon\Cms\Value\ValueContext('test_matrix', []));
		$schema = $matrix->schema();

		$this->assertInstanceOf(\Duon\Sire\Schema::class, $schema);
	}

	public function testMatrixSubfieldsHaveTranslateCapability(): void
	{
		$context = $this->createContext();
		$owner = new NodeFieldOwner($context, 'test-node');

		$matrix = new TestMatrix('test_matrix', $owner, new \Duon\Cms\Value\ValueContext('test_matrix', []));
		$subfields = $matrix->getSubfields();

		// Check that title subfield has translate capability set
		$titleField = $subfields['title'];
		$this->assertTrue($titleField->isTranslatable(), 'Title subfield should be translatable');

		// Check that the structure for an empty item has locale keys
		$structure = $matrix->structure([
			['title' => ['type' => 'text', 'value' => ''], 'content' => ['type' => 'grid', 'value' => []]],
		]);

		$titleValue = $structure['value'][0]['title']['value'];
		$this->assertIsArray($titleValue, 'Title value should be array with locale keys');
		$this->assertArrayHasKey('en', $titleValue);
		$this->assertArrayHasKey('de', $titleValue);
	}

	public function testMatrixStructureFromValueContext(): void
	{
		$context = $this->createContext();
		$owner = new NodeFieldOwner($context, 'test-node');

		// Simulate data as it comes from the database (stored format)
		$storedData = [
			'type' => 'matrix',
			'value' => [
				[
					'title' => ['type' => 'text', 'value' => ''],
					'content' => ['type' => 'grid', 'value' => []],
				],
			],
		];

		$matrix = new TestMatrix('test_matrix', $owner, new \Duon\Cms\Value\ValueContext('test_matrix', $storedData));

		// Call structure() without arguments - this is how Node::content() calls it
		$structure = $matrix->structure();

		// The output should have locale keys even though input had empty string
		$titleValue = $structure['value'][0]['title']['value'];
		$this->assertIsArray($titleValue, 'Title value should be array with locale keys, got: ' . var_export($titleValue, true));
		$this->assertArrayHasKey('en', $titleValue);
		$this->assertArrayHasKey('de', $titleValue);
	}
}
