<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Context;
use Duon\Cms\Tests\Fixtures\Field\TestCheckbox;
use Duon\Cms\Tests\Fixtures\Field\TestHtml;
use Duon\Cms\Tests\Fixtures\Field\TestNumber;
use Duon\Cms\Tests\Fixtures\Field\TestText;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\ValueContext;

/**
 * @internal
 *
 * @coversNothing
 */
final class PrimitiveValueTest extends TestCase
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

	private function createNode(Context $context): \Duon\Cms\Node\Document
	{
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		return new class ($context, $finder, ['content' => []]) extends \Duon\Cms\Node\Document {
			public function title(): string
			{
				return 'Test';
			}
		};
	}

	public function testTextValueFallsBackToDefaultLocale(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new TestText('title', $node, new ValueContext('title', [
			'value' => ['en' => 'Hello', 'de' => null],
		]));
		$field->translate();

		$context->request->set('locale', $context->locales()->get('de'));
		$value = $field->value();

		$this->assertSame('Hello', $value->unwrap());
		$this->assertSame('Hello', (string) $value);
		$this->assertTrue($value->isset());
	}

	public function testTextValueReturnsEmptyWhenMissing(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new TestText('title', $node, new ValueContext('title', [
			'value' => ['en' => null, 'de' => null],
		]));
		$field->translate();

		$context->request->set('locale', $context->locales()->get('de'));
		$value = $field->value();

		$this->assertSame('', $value->unwrap());
		$this->assertSame('', (string) $value);
		$this->assertFalse($value->isset());
	}

	public function testHtmlValueUsesExcerptAndSanitizedOutput(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new TestHtml('body', $node, new ValueContext('body', [
			'value' => ['en' => '<p>Hello <strong>World</strong></p>'],
		]));
		$field->translate();

		$value = $field->value();
		$this->assertSame('Hello World', $value->excerpt(2));
		$this->assertStringContainsString('Hello', $value->clean());
	}

	public function testNumberValueCastsNumeric(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new TestNumber('count', $node, new ValueContext('count', [
			'value' => '42',
		]));

		$value = $field->value();
		$this->assertSame(42, $value->unwrap());
		$this->assertTrue($value->isset());
		$this->assertSame('42', (string) $value);
	}

	public function testNumberValueIsNullWhenInvalid(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new TestNumber('count', $node, new ValueContext('count', [
			'value' => 'not-a-number',
		]));

		$value = $field->value();
		$this->assertNull($value->unwrap());
		$this->assertFalse($value->isset());
		$this->assertSame('', (string) $value);
	}

	public function testCheckboxValueDefaultsFalse(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new TestCheckbox('flag', $node, new ValueContext('flag', [
			'value' => null,
		]));

		$value = $field->value();
		$this->assertFalse($value->unwrap());
		$this->assertSame('', (string) $value);
		$this->assertTrue($value->isset());
	}

	public function testOptionValueUsesProvidedValue(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Option('status', $node, new ValueContext('status', [
			'value' => 'draft',
		]));

		$value = $field->value();
		$this->assertSame('draft', $value->unwrap());
		$this->assertSame(['value' => 'draft'], $value->json());
		$this->assertTrue($value->isset());
	}

	public function testRadioValueUsesStringValue(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Radio('choice', $node, new ValueContext('choice', [
			'value' => 'yes',
		]));

		$value = $field->value();
		$this->assertSame('yes', $value->unwrap());
		$this->assertSame('yes', $value->json());
		$this->assertTrue($value->isset());
	}

	public function testDateTimeValueFormatsToExpectedString(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\DateTime('timestamp', $node, new ValueContext('timestamp', [
			'value' => '2025-01-31 13:45:10',
			'timezone' => 'UTC',
		]));

		$value = $field->value();
		$this->assertSame('2025-01-31 13:45:10', $value->format(\Duon\Cms\Value\DateTime::FORMAT));
		$this->assertSame('2025-01-31 13:45:10', (string) $value);
		$this->assertTrue($value->isset());
	}

	public function testDateValueFormatsToExpectedString(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Date('date', $node, new ValueContext('date', [
			'value' => '2025-01-31',
		]));

		$value = $field->value();
		$this->assertSame('2025-01-31', $value->format(\Duon\Cms\Value\Date::FORMAT));
		$this->assertSame('2025-01-31', (string) $value);
		$this->assertTrue($value->isset());
	}

	public function testTimeValueFormatsToExpectedString(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Time('time', $node, new ValueContext('time', [
			'value' => '13:45',
		]));

		$value = $field->value();
		$this->assertSame('13:45', $value->format(\Duon\Cms\Value\Time::FORMAT));
		$this->assertSame('13:45', (string) $value);
		$this->assertTrue($value->isset());
	}

	public function testIframeValueFallsBackToDefaultLocale(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Iframe('embed', $node, new ValueContext('embed', []));
		$field->translate();

		$context->request->set('locale', $context->locales()->get('de'));
		$value = new \Duon\Cms\Value\Iframe($node, $field, new ValueContext('embed', [
			'value' => ['en' => '<iframe></iframe>', 'de' => null],
		]));

		$this->assertSame('<iframe></iframe>', $value->unwrap());
		$this->assertSame('&lt;iframe&gt;&lt;/iframe&gt;', (string) $value);
		$this->assertTrue($value->isset());
	}

	public function testIframeValueIsEmptyWhenMissing(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Iframe('embed', $node, new ValueContext('embed', []));
		$field->translate();

		$context->request->set('locale', $context->locales()->get('de'));
		$value = new \Duon\Cms\Value\Iframe($node, $field, new ValueContext('embed', [
			'value' => ['en' => null, 'de' => null],
		]));

		$this->assertSame('', $value->unwrap());
		$this->assertSame('', (string) $value);
		$this->assertFalse($value->isset());
	}

	public function testYoutubeValueUsesAspectRatio(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Youtube('video', $node, new ValueContext('video', [
			'value' => 'abc123',
			'id' => 'abc123',
			'aspectRatioX' => 16,
			'aspectRatioY' => 9,
		]));

		$value = $field->value();
		$this->assertSame('abc123', $value->unwrap());
		$this->assertSame('abc123', $value->json());
		$this->assertTrue($value->isset());
		$this->assertStringContainsString('padding-top: 56.25%', (string) $value);
	}
}
