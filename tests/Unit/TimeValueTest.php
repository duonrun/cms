<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Field\Time;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\ValueContext;
use IntlDateFormatter;

final class TimeValueTest extends TestCase
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

	public function testTimeValueHasCorrectFormat(): void
	{
		$this->assertSame('H:i', \Duon\Cms\Value\Time::FORMAT);
	}

	public function testTimeValueFormatsToExpectedString(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new Time('starttime', $node, new ValueContext('starttime', [
			'value' => '13:45',
		]));

		$value = $field->value();
		$this->assertSame('13:45', $value->format(\Duon\Cms\Value\Time::FORMAT));
		$this->assertSame('13:45', (string) $value);
		$this->assertTrue($value->isset());
	}

	public function testTimeValueLocalizeWithDefaultParams(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new Time('time', $node, new ValueContext('time', [
			'value' => '09:30',
		]));

		$value = $field->value();
		// Default: NONE date, SHORT time
		$formatted = $value->localize();

		// Should contain the time in some form (actual format depends on locale)
		$this->assertNotEmpty($formatted);
	}

	public function testTimeValueLocalizeWithMediumTime(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new Time('time', $node, new ValueContext('time', [
			'value' => '14:30',
		]));

		$value = $field->value();
		$formatted = $value->localize(IntlDateFormatter::NONE, IntlDateFormatter::MEDIUM);

		$this->assertNotEmpty($formatted);
	}

	public function testTimeValueEmptyWhenNull(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new Time('time', $node, new ValueContext('time', [
			'value' => null,
		]));

		$value = $field->value();
		$this->assertNull($value->unwrap());
		$this->assertSame('', (string) $value);
		$this->assertFalse($value->isset());
	}

	public function testTimeValueJsonReturnsString(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new Time('time', $node, new ValueContext('time', [
			'value' => '08:00',
		]));

		$value = $field->value();
		$this->assertSame('08:00', $value->json());
	}

	public function testTimeValueWithDifferentTimes(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);

		$testTimes = [
			'00:00', // Midnight
			'12:00', // Noon
			'23:59', // Just before midnight
			'09:30', // Morning
			'18:45', // Evening
		];

		foreach ($testTimes as $timeStr) {
			$field = new Time('time', $node, new ValueContext('time', ['value' => $timeStr]));
			$value = $field->value();
			$this->assertSame($timeStr, $value->format('H:i'));
		}
	}
}
