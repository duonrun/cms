<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Context;
use Duon\Cms\Exception\NoSuchProperty;
use Duon\Cms\Node\NodeFieldOwner;
use Duon\Cms\Tests\Fixtures\Field\TestMatrix;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\MatrixItem;
use Duon\Cms\Value\MatrixValue;
use Duon\Cms\Value\ValueContext;

/**
 * @internal
 *
 * @coversNothing
 */
final class MatrixValueTest extends TestCase
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
			$this->config(['path.prefix' => '/cms']),
			$this->registry(),
			$this->factory(),
		);
	}

	private function createOwner(Context $context): NodeFieldOwner
	{
		return new NodeFieldOwner($context, 'test-node');
	}

	private function createMatrixValue(array $data): MatrixValue
	{
		$context = $this->createContext();
		$owner = $this->createOwner($context);
		$field = new TestMatrix('matrix', $owner, new ValueContext('matrix', $data));

		return $field->value();
	}

	private function matrixData(): array
	{
		return [
			'type' => 'matrix',
			'value' => [
				[
					'title' => ['type' => 'text', 'value' => ['en' => 'First Item', 'de' => 'Erstes']],
					'content' => [
						'type' => 'grid',
						'columns' => 12,
						'value' => ['en' => [], 'de' => []],
					],
				],
				[
					'title' => ['type' => 'text', 'value' => ['en' => 'Second Item']],
					'content' => [
						'type' => 'grid',
						'columns' => 12,
						'value' => ['en' => [], 'de' => []],
					],
				],
			],
		];
	}

	public function testMatrixValueAccessorsReturnItemsAndValues(): void
	{
		$value = $this->createMatrixValue($this->matrixData());

		$this->assertSame(2, $value->count());
		$this->assertInstanceOf(MatrixItem::class, $value->first());
		$this->assertInstanceOf(MatrixItem::class, $value->last());
		$this->assertSame('First Item', $value->first()?->title->unwrap());
		$this->assertSame('Second Item', $value->last()?->title->unwrap());
		$this->assertSame('Second Item', $value->get(1)?->title->unwrap());
		$this->assertNull($value->get(2));
		$this->assertSame(12, $value->first()?->content->columns());
	}

	public function testMatrixValueIssetIsFalseWhenEmpty(): void
	{
		$value = $this->createMatrixValue(['type' => 'matrix', 'value' => []]);

		$this->assertFalse($value->isset());
		$this->assertSame(0, $value->count());
		$this->assertNull($value->first());
		$this->assertNull($value->last());
	}

	public function testMatrixValueJsonMatchesUnwrap(): void
	{
		$value = $this->createMatrixValue($this->matrixData());
		$unwrapped = $value->unwrap();

		$this->assertSame($unwrapped, $value->json());
		$this->assertCount(2, $unwrapped);
		$this->assertArrayHasKey('title', $unwrapped[0]);
		$this->assertArrayHasKey('content', $unwrapped[0]);
	}

	public function testMatrixItemThrowsOnUnknownSubfield(): void
	{
		$value = $this->createMatrixValue($this->matrixData());
		$item = $value->first();

		$this->assertInstanceOf(MatrixItem::class, $item);
		$this->throws(NoSuchProperty::class, "Matrix item doesn't have subfield 'missing'");

		$item->missing;
	}
}
