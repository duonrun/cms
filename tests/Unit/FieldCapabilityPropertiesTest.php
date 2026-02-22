<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Field\Grid;
use Duon\Cms\Field\Image;
use Duon\Cms\Field\Option;
use Duon\Cms\Field\Schema\Registry;
use Duon\Cms\Field\Text;
use Duon\Cms\Node\FieldOwner;
use Duon\Cms\Schema\Columns;
use Duon\Cms\Schema\Description;
use Duon\Cms\Schema\Hidden;
use Duon\Cms\Schema\Immutable;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Multiple;
use Duon\Cms\Schema\Options;
use Duon\Cms\Schema\Required;
use Duon\Cms\Schema\Rows;
use Duon\Cms\Schema\Syntax;
use Duon\Cms\Schema\Translate;
use Duon\Cms\Schema\TranslateFile;
use Duon\Cms\Schema\Validate;
use Duon\Cms\Schema\Width;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\ValueContext;

final class FieldCapabilityPropertiesTest extends TestCase
{
	private Registry $registry;

	protected function setUp(): void
	{
		parent::setUp();
		$this->registry = Registry::withDefaults();
	}

	private function createOwner(): FieldOwner
	{
		$context = new \Duon\Cms\Context(
			$this->db(),
			$this->request(),
			$this->config(),
			$this->container(),
			$this->factory(),
		);

		return new FieldOwner($context, 'test-node');
	}

	private function createTextField(string $name = 'test'): Text
	{
		return new Text($name, $this->createOwner(), new ValueContext($name, []));
	}

	private function createImageField(string $name = 'image'): Image
	{
		return new Image($name, $this->createOwner(), new ValueContext($name, []));
	}

	private function createGridField(string $name = 'grid'): Grid
	{
		return new Grid($name, $this->createOwner(), new ValueContext($name, []));
	}

	private function createOptionField(string $name = 'option'): Option
	{
		return new Option($name, $this->createOwner(), new ValueContext($name, []));
	}

	private function applyAndGetProperties(object $meta, $field): array
	{
		$handler = $this->registry->getHandler($meta);
		$handler->apply($meta, $field);

		return $handler->properties($meta, $field);
	}

	public function testLabelCapabilityReturnsLabelProperty(): void
	{
		$field = $this->createTextField();
		$meta = new Label('Test Label');

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('label', $properties);
		$this->assertEquals('Test Label', $properties['label']);
	}

	public function testDescriptionCapabilityReturnsDescriptionProperty(): void
	{
		$field = $this->createTextField();
		$meta = new Description('Test description');

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('description', $properties);
		$this->assertEquals('Test description', $properties['description']);
	}

	public function testHiddenCapabilityReturnsHiddenProperty(): void
	{
		$field = $this->createTextField();
		$meta = new Hidden();

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('hidden', $properties);
		$this->assertTrue($properties['hidden']);
	}

	public function testRequiredCapabilityReturnsRequiredProperty(): void
	{
		$field = $this->createTextField();
		$meta = new Required();

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('required', $properties);
		$this->assertTrue($properties['required']);
	}

	public function testImmutableCapabilityReturnsImmutableProperty(): void
	{
		$field = $this->createTextField();
		$meta = new Immutable();

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('immutable', $properties);
		$this->assertTrue($properties['immutable']);
	}

	public function testRowsCapabilityReturnsRowsProperty(): void
	{
		$field = $this->createTextField();
		$meta = new Rows(10);

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('rows', $properties);
		$this->assertEquals(10, $properties['rows']);
	}

	public function testWidthCapabilityReturnsWidthProperty(): void
	{
		$field = $this->createTextField();
		$meta = new Width(6);

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('width', $properties);
		$this->assertEquals(6, $properties['width']);
	}

	public function testColumnsCapabilityReturnsColumnsProperties(): void
	{
		$field = $this->createGridField();
		$meta = new Columns(12, 2);

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('columns', $properties);
		$this->assertArrayHasKey('minCellWidth', $properties);
		$this->assertEquals(12, $properties['columns']);
		$this->assertEquals(2, $properties['minCellWidth']);
	}

	public function testOptionsCapabilityReturnsOptionsProperty(): void
	{
		$field = $this->createOptionField();
		$options = ['option1', 'option2', 'option3'];
		$meta = new Options($options);

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('options', $properties);
		$this->assertEquals($options, $properties['options']);
	}

	public function testTranslateCapabilityReturnsTranslateProperty(): void
	{
		$field = $this->createTextField();
		$meta = new Translate();

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('translate', $properties);
		$this->assertTrue($properties['translate']);
	}

	public function testTranslateFileCapabilityReturnsTranslateFileProperty(): void
	{
		$field = $this->createImageField();
		$meta = new TranslateFile();

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('translateFile', $properties);
		$this->assertTrue($properties['translateFile']);
	}

	public function testMultipleCapabilityReturnsMultipleProperty(): void
	{
		$field = $this->createImageField();
		$meta = new Multiple();

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('multiple', $properties);
		$this->assertTrue($properties['multiple']);
	}

	public function testValidateCapabilityReturnsValidatorsProperty(): void
	{
		$field = $this->createTextField();
		$meta = new Validate('minLength:5', 'maxLength:100');

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('validators', $properties);
		$this->assertContains('minLength:5', $properties['validators']);
		$this->assertContains('maxLength:100', $properties['validators']);
	}

	public function testSyntaxCapabilityReturnsSyntaxesProperty(): void
	{
		$field = new class ('code', $this->createOwner(), new ValueContext('code', [])) extends Text implements \Duon\Cms\Field\Capability\Syntaxable {
			use \Duon\Cms\Field\Capability\IsSyntaxable;
		};
		$meta = new Syntax('php', 'javascript', 'php');

		$properties = $this->applyAndGetProperties($meta, $field);

		$this->assertArrayHasKey('syntaxes', $properties);
		$this->assertEquals(['php', 'javascript'], $properties['syntaxes']);
	}
}
