<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Field\Date;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\ValueContext;
use IntlDateFormatter;

final class DateValueTest extends TestCase
{
	private function createContext(): \Duon\Cms\Context
	{
		$psrRequest = $this->psrRequest();
		$locales = new \Duon\Cms\Locales();
		$locales->add('en', title: 'English', domains: ['www.example.com']);

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

	private function createNode(\Duon\Cms\Context $context): \Duon\Cms\Node\Document
	{
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		return new class ($context, $finder, ['content' => []]) extends \Duon\Cms\Node\Document {
			public function title(): string
			{
				return 'Test';
			}
		};
	}

	public function testDateValueHasCorrectFormat(): void
	{
		$this->assertSame('Y-m-d', \Duon\Cms\Value\Date::FORMAT);
	}

	public function testDateValueFormatsToExpectedString(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new Date('birthdate', $node, new ValueContext('birthdate', [
			'value' => '2025-01-31',
		]));

		$value = $field->value();
		$this->assertSame('2025-01-31', $value->format(\Duon\Cms\Value\Date::FORMAT));
		$this->assertSame('2025-01-31', (string) $value);
		$this->assertTrue($value->isset());
	}

	public function testDateValueLocalizeWithDefaultParams(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new Date('date', $node, new ValueContext('date', [
			'value' => '2025-01-31',
		]));

		$value = $field->value();
		// Default: MEDIUM date, NONE time
		$formatted = $value->localize();

		// Should contain the date in some form (actual format depends on locale)
		$this->assertNotEmpty($formatted);
		$this->assertStringContainsString('2025', $formatted);
	}

	public function testDateValueLocalizeWithLongFormat(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new Date('date', $node, new ValueContext('date', [
			'value' => '2025-01-31',
		]));

		$value = $field->value();
		$formatted = $value->localize(IntlDateFormatter::LONG, IntlDateFormatter::NONE);

		$this->assertNotEmpty($formatted);
		$this->assertStringContainsString('2025', $formatted);
	}

	public function testDateValueEmptyWhenNull(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new Date('date', $node, new ValueContext('date', [
			'value' => null,
		]));

		$value = $field->value();
		$this->assertNull($value->unwrap());
		$this->assertSame('', (string) $value);
		$this->assertFalse($value->isset());
	}

	public function testDateValueJsonReturnsString(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new Date('date', $node, new ValueContext('date', [
			'value' => '2025-06-15',
		]));

		$value = $field->value();
		$this->assertSame('2025-06-15', $value->json());
	}

	public function testDateValueWithDifferentDates(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);

		$testDates = [
			'2024-02-29', // Leap year
			'2023-12-25', // Christmas
			'2025-01-01', // New Year
		];

		foreach ($testDates as $dateStr) {
			$field = new Date('date', $node, new ValueContext('date', ['value' => $dateStr]));
			$value = $field->value();
			$this->assertSame($dateStr, $value->format('Y-m-d'));
		}
	}
}
