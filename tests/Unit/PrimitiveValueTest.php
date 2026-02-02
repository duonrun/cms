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
			$this->config(['path.prefix' => '/cms']),
			$this->registry(),
			$this->factory(),
		);
	}

	private function createNode(Context $context): \Duon\Cms\Node\Document
	{
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		return new class ($context, $finder, ['uid' => 'test-node', 'content' => []]) extends \Duon\Cms\Node\Document {
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

	public function testValueBaseExposesCustomAttributes(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new TestText('title', $node, new ValueContext('title', [
			'value' => ['en' => 'Hello'],
			'class' => 'hero',
			'id' => 'section',
			'custom' => 'custom-value',
		]));

		$value = $field->value();

		$this->assertSame('hero', $value->styleClass());
		$this->assertSame('section', $value->elementId());
		$this->assertSame('custom-value', $value->custom);

		$this->throws(
			\Duon\Cms\Exception\NoSuchProperty::class,
			"The field 'title' doesn't have the property 'missing'",
		);
		$value->missing;
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

	public function testDecimalValueFormatsAndLocalizes(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$valueContext = new ValueContext('price', ['value' => '12.5']);
		$field = new TestNumber('price', $node, $valueContext);
		$value = new \Duon\Cms\Value\Decimal($node, $field, $valueContext);
		$this->assertSame(12.5, $value->unwrap());
		$this->assertTrue($value->isset());
		$this->assertSame('12.5', (string) $value->unwrap());
		$this->assertSame('12.50', $value->localize(2, 'en'));
		$this->assertStringContainsString('12.50', $value->currency('USD', 2, 'en'));
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

	public function testFilesValueIteratesAndCounts(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$valueContext = new ValueContext('attachments', [
			'files' => [
				['file' => 'one.pdf'],
				['file' => 'two.pdf'],
			],
		]);
		$field = new \Duon\Cms\Field\File('attachments', $node, $valueContext);
		$field->multiple();
		$value = $field->value();

		$this->assertSame(2, $value->count());
		$this->assertTrue($value->isset());
		$this->assertSame('Files: count(0)', (string) $value, 'Value unwrap uses locale data, not files.');
		$this->assertInstanceOf(\Duon\Cms\Value\File::class, $value->first());

		$files = [];
		foreach ($value as $file) {
			$files[] = $file;
		}

		$this->assertCount(2, $files);
	}

	public function testTranslatedFileFallsBackToDefaultLocale(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\File('attachment', $node, new ValueContext('attachment', [
			'files' => [
				'en' => [
					['file' => 'manual.pdf', 'title' => 'Manual'],
				],
				'de' => [
					['file' => null, 'title' => null],
				],
			],
		]));
		$field->translateFile();
		$context->request->set('locale', $context->locales()->get('de'));

		$value = $field->value();

		$this->assertTrue($value->isset());
		$this->assertSame('Manual', $value->title());
	}

	public function testTranslatedFileIsEmptyWhenMissing(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\File('attachment', $node, new ValueContext('attachment', [
			'files' => [
				'en' => [
					['file' => null, 'title' => null],
				],
				'de' => [
					['file' => null, 'title' => null],
				],
			],
		]));
		$field->translateFile();
		$context->request->set('locale', $context->locales()->get('de'));

		$value = $field->value();

		$this->assertFalse($value->isset());
		$this->assertSame('', $value->title());
	}

	public function testTranslatedFilesReturnsTranslatedFileInstances(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\File('attachments', $node, new ValueContext('attachments', [
			'files' => [
				'en' => [
					['file' => 'spec.pdf', 'title' => 'Spec'],
				],
				'de' => [
					['file' => null, 'title' => null],
				],
			],
		]));
		$field->multiple();
		$field->translateFile();
		$context->request->set('locale', $context->locales()->get('de'));

		$value = $field->value();

		$this->assertInstanceOf(\Duon\Cms\Value\TranslatedFiles::class, $value);
		$this->assertInstanceOf(\Duon\Cms\Value\TranslatedFile::class, $value->current());
		$this->assertSame('Spec', $value->current()->title());
	}

	public function testImageValueBuildsMediaPaths(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Image('hero', $node, new ValueContext('hero', [
			'files' => [
				['file' => 'hero.jpg', 'alt' => ['en' => 'Hero']],
			],
		]));

		/** @var \Duon\Cms\Value\Image $value */
		$value = $field->value();

		$this->assertStringContainsString('/cms/media/image/node/test-node/hero.jpg', $value->publicPath());
		$this->assertStringContainsString('http://www.example.com', $value->url());
		$this->assertSame('Hero', $value->alt());
	}

	public function testImageTagUsesMediaUrlAndAlt(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Image('hero', $node, new ValueContext('hero', [
			'files' => [
				[
					'file' => 'hero.jpg',
					'alt' => ['en' => 'Hero'],
					'title' => ['en' => 'Hero Title'],
				],
			],
		]));

		/** @var \Duon\Cms\Value\Image $value */
		$value = $field->value();
		$tag = $value->tag(true, 'hero-image');

		$this->assertStringContainsString('class="hero-image"', $tag);
		$this->assertStringContainsString(
			'src="http://www.example.com/cms/media/image/node/test-node/hero.jpg"',
			$tag,
		);
		$this->assertStringContainsString('alt="Hero"', $tag);
		$this->assertStringContainsString(
			'data-path-original="/cms/media/image/node/test-node/hero.jpg"',
			$tag,
		);
	}

	public function testTranslatedImageFallsBackToDefaultLocale(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Image('hero', $node, new ValueContext('hero', [
			'files' => [
				'en' => [
					['file' => 'hero.jpg', 'alt' => 'Hero'],
				],
				'de' => [
					['file' => null, 'alt' => null],
				],
			],
		]));
		$field->translateFile();
		$context->request->set('locale', $context->locales()->get('de'));

		/** @var \Duon\Cms\Value\TranslatedImage $value */
		$value = $field->value();

		$this->assertTrue($value->isset());
		$this->assertSame('Hero', $value->alt());
		$this->assertStringContainsString('/cms/media/image/node/test-node/hero.jpg', $value->publicPath());
	}

	public function testFileValueTitleFallsBackToDefaultLocale(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\File('document', $node, new ValueContext('document', [
			'files' => [
				[
					'file' => 'manual.pdf',
					'title' => [
						'en' => 'Manual',
						'de' => null,
					],
				],
			],
		]));
		$field->translate();
		$context->request->set('locale', $context->locales()->get('de'));

		$value = $field->value();

		$this->assertSame('manual.pdf', $value->filename());
		$this->assertSame('Manual', $value->title());
	}

	public function testPictureValueUsesTranslatedAltAndTitle(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Picture('hero', $node, new ValueContext('hero', [
			'files' => [
				[
					'file' => 'hero.jpg',
					'alt' => [
						'en' => 'Hero',
						'de' => null,
					],
					'title' => [
						'en' => 'Hero Image',
						'de' => null,
					],
				],
			],
		]));
		$field->translate();
		$context->request->set('locale', $context->locales()->get('de'));

		$value = $field->value();

		$this->assertSame('Hero', $value->alt());
		$this->assertSame('Hero Image', $value->title());
	}

	public function testPictureTagRendersSourcesAndFallback(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$valueContext = new ValueContext('hero', [
			'files' => [
				[
					'file' => 'hero.webp',
					'media' => '(min-width: 600px)',
					'alt' => ['en' => 'Hero Alt'],
					'title' => ['en' => 'Hero Title'],
				],
				[
					'file' => 'hero.jpg',
					'alt' => ['en' => 'Hero Alt'],
					'title' => ['en' => 'Hero Title'],
				],
			],
		]);
		$field = new \Duon\Cms\Field\Picture('hero', $node, $valueContext);
		$field->translate();

		$value = new class ($node, $field, $valueContext) extends \Duon\Cms\Value\Picture {
			public function url(bool $bust = true, int $index = 0): string
			{
				return "https://cdn.example.com/hero-{$index}.jpg";
			}
		};

		$tag = $value->tag(false, 'hero-picture');

		$this->assertStringContainsString('<picture', $tag);
		$this->assertStringContainsString('class="hero-picture"', $tag);
		$this->assertStringContainsString('media="(min-width: 600px)"', $tag);
		$this->assertStringContainsString('type="image/jpeg"', $tag);
		$this->assertStringContainsString('alt="Hero Alt"', $tag);
		$this->assertStringContainsString('src="https://cdn.example.com/hero-1.jpg"', $tag);
	}

	public function testTranslatedPictureFallsBackToDefaultLocale(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Picture('hero', $node, new ValueContext('hero', [
			'files' => [
				[
					'en' => [
						'file' => 'hero.jpg',
						'alt' => 'Hero',
						'title' => 'Hero Image',
						'link' => '/hero',
					],
					'de' => [
						'file' => null,
						'alt' => null,
						'title' => null,
						'link' => null,
					],
				],
			],
		]));
		$field->translateFile();
		$context->request->set('locale', $context->locales()->get('de'));

		$value = $field->value();

		$this->assertSame('Hero', $value->alt());
		$this->assertSame('Hero Image', $value->title());
		$this->assertSame('/hero', $value->link());
	}

	public function testVideoValueRendersSourceTag(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$valueContext = new ValueContext('clip', [
			'files' => [
				['file' => 'clip.mp4', 'title' => 'Clip'],
			],
		]);
		$field = new \Duon\Cms\Field\Video('clip', $node, $valueContext);

		$value = new class ($node, $field, $valueContext) extends \Duon\Cms\Value\Video {
			public function url(bool $bust = false): string
			{
				return 'http://www.example.com/assets/clip.mp4';
			}

			public function mimeType(): string
			{
				return 'video/mp4';
			}
		};

		$this->assertSame(
			'<video controls><source src="http://www.example.com/assets/clip.mp4" type="video/mp4"/></video>',
			(string) $value,
		);
	}

	public function testTranslatedImagesReturnsTranslatedImageItems(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Image('gallery', $node, new ValueContext('gallery', [
			'files' => [
				'en' => [
					['file' => 'hero.jpg', 'alt' => 'Hero'],
				],
				'de' => [
					['file' => null, 'alt' => null],
				],
			],
		]));
		$field->multiple();
		$field->translateFile();
		$context->request->set('locale', $context->locales()->get('de'));

		$value = $field->value();

		$this->assertInstanceOf(\Duon\Cms\Value\TranslatedImages::class, $value);
		$this->assertInstanceOf(\Duon\Cms\Value\TranslatedImage::class, $value->current());
		$this->assertSame('Hero', $value->current()->alt());
	}

	public function testImageValueResizeAddsQueryString(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Image('hero', $node, new ValueContext('hero', [
			'files' => [
				['file' => 'hero.jpg', 'alt' => ['en' => 'Hero']],
			],
		]));

		/** @var \Duon\Cms\Value\Image $value */
		$value = $field->value()->width(320, true)->quality(80);

		$this->assertStringContainsString('resize=width', $value->publicPath());
		$this->assertStringContainsString('w=320', $value->publicPath());
		$this->assertStringContainsString('enlarge=true', $value->publicPath());
		$this->assertStringContainsString('quality=80', $value->publicPath());
	}

	public function testImagesValueIteratesOverImages(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Image('gallery', $node, new ValueContext('gallery', [
			'files' => [
				['file' => 'one.jpg', 'alt' => ['en' => 'One']],
				['file' => 'two.jpg', 'alt' => ['en' => 'Two']],
			],
		]));
		$field->multiple();

		/** @var \Duon\Cms\Value\Images $value */
		$value = $field->value();

		$this->assertSame(2, $value->count());
		$this->assertInstanceOf(\Duon\Cms\Value\Image::class, $value->first());
		$this->assertInstanceOf(\Duon\Cms\Value\Image::class, $value->current());
		$this->assertInstanceOf(\Duon\Cms\Value\Image::class, $value->get(1));
		$this->assertSame('One', $value->first()->alt());
		$this->assertSame('Two', $value->get(1)->alt());
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

	public function testStrValueEscapesHtml(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Radio('choice', $node, new ValueContext('choice', [
			'value' => '<strong>Yes</strong>',
		]));

		$value = $field->value();

		$this->assertSame('<strong>Yes</strong>', $value->unwrap());
		$this->assertSame('<strong>Yes</strong>', $value->json());
		$this->assertSame('&lt;strong&gt;Yes&lt;/strong&gt;', (string) $value);
		$this->assertTrue($value->isset());
	}

	public function testStrValueIsEmptyWhenMissing(): void
	{
		$context = $this->createContext();
		$node = $this->createNode($context);
		$field = new \Duon\Cms\Field\Radio('choice', $node, new ValueContext('choice', [
			'value' => '',
		]));

		$value = $field->value();

		$this->assertSame('', $value->unwrap());
		$this->assertSame('', $value->json());
		$this->assertSame('', (string) $value);
		$this->assertFalse($value->isset());
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
