<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Field\Grid;
use Duon\Cms\Field\Image;
use Duon\Cms\Field\Meta\Columns;
use Duon\Cms\Field\Meta\Description;
use Duon\Cms\Field\Meta\Hidden;
use Duon\Cms\Field\Meta\Immutable;
use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\Multiple;
use Duon\Cms\Field\Meta\Options;
use Duon\Cms\Field\Meta\Required;
use Duon\Cms\Field\Meta\Rows;
use Duon\Cms\Field\Meta\Translate;
use Duon\Cms\Field\Meta\TranslateFile;
use Duon\Cms\Field\Meta\Validate;
use Duon\Cms\Field\Meta\Width;
use Duon\Cms\Field\Option;
use Duon\Cms\Field\Text;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\ValueContext;

final class FieldCapabilityPropertiesTest extends TestCase
{
	private function createContext(): \Duon\Cms\Context
	{
		return new \Duon\Cms\Context(
			$this->db(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
	}

	private function createTextField(string $name = 'test'): Text
	{
		$context = $this->createContext();
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		$nodeClass = new class ($context, $finder, ['content' => []]) extends \Duon\Cms\Node\Document {
			public function title(): string
			{
				return 'Test';
			}
		};

		return new Text($name, $nodeClass, new ValueContext($name, []));
	}

	private function createImageField(string $name = 'image'): Image
	{
		$context = $this->createContext();
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		$nodeClass = new class ($context, $finder, ['content' => []]) extends \Duon\Cms\Node\Document {
			public function title(): string
			{
				return 'Test';
			}
		};

		return new Image($name, $nodeClass, new ValueContext($name, []));
	}

	private function createGridField(string $name = 'grid'): Grid
	{
		$context = $this->createContext();
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		$nodeClass = new class ($context, $finder, ['content' => []]) extends \Duon\Cms\Node\Document {
			public function title(): string
			{
				return 'Test';
			}
		};

		return new Grid($name, $nodeClass, new ValueContext($name, []));
	}

	private function createOptionField(string $name = 'option'): Option
	{
		$context = $this->createContext();
		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		$nodeClass = new class ($context, $finder, ['content' => []]) extends \Duon\Cms\Node\Document {
			public function title(): string
			{
				return 'Test';
			}
		};

		return new Option($name, $nodeClass, new ValueContext($name, []));
	}

	public function testLabelCapabilityReturnsLabelProperty(): void
	{
		$field = $this->createTextField();
		$capability = new Label('Test Label');
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('label', $properties);
		$this->assertEquals('Test Label', $properties['label']);
	}

	public function testDescriptionCapabilityReturnsDescriptionProperty(): void
	{
		$field = $this->createTextField();
		$capability = new Description('Test description');
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('description', $properties);
		$this->assertEquals('Test description', $properties['description']);
	}

	public function testHiddenCapabilityReturnsHiddenProperty(): void
	{
		$field = $this->createTextField();
		$capability = new Hidden();
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('hidden', $properties);
		$this->assertTrue($properties['hidden']);
	}

	public function testRequiredCapabilityReturnsRequiredProperty(): void
	{
		$field = $this->createTextField();
		$capability = new Required();
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('required', $properties);
		$this->assertTrue($properties['required']);
	}

	public function testImmutableCapabilityReturnsImmutableProperty(): void
	{
		$field = $this->createTextField();
		$capability = new Immutable();
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('immutable', $properties);
		$this->assertTrue($properties['immutable']);
	}

	public function testRowsCapabilityReturnsRowsProperty(): void
	{
		$field = $this->createTextField();
		$capability = new Rows(10);
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('rows', $properties);
		$this->assertEquals(10, $properties['rows']);
	}

	public function testWidthCapabilityReturnsWidthProperty(): void
	{
		$field = $this->createTextField();
		$capability = new Width(6);
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('width', $properties);
		$this->assertEquals(6, $properties['width']);
	}

	public function testColumnsCapabilityReturnsColumnsProperties(): void
	{
		$field = $this->createGridField();
		$capability = new Columns(12, 2);
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('columns', $properties);
		$this->assertArrayHasKey('minCellWidth', $properties);
		$this->assertEquals(12, $properties['columns']);
		$this->assertEquals(2, $properties['minCellWidth']);
	}

	public function testOptionsCapabilityReturnsOptionsProperty(): void
	{
		$field = $this->createOptionField();
		$options = ['option1', 'option2', 'option3'];
		$capability = new Options($options);
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('options', $properties);
		$this->assertEquals($options, $properties['options']);
	}

	public function testTranslateCapabilityReturnsTranslateProperty(): void
	{
		$field = $this->createTextField();
		$capability = new Translate();
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('translate', $properties);
		$this->assertTrue($properties['translate']);
	}

	public function testTranslateFileCapabilityReturnsTranslateFileProperty(): void
	{
		$field = $this->createImageField();
		$capability = new TranslateFile();
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('translateFile', $properties);
		$this->assertTrue($properties['translateFile']);
	}

	public function testMultipleCapabilityReturnsMultipleProperty(): void
	{
		$field = $this->createImageField();
		$capability = new Multiple();
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('multiple', $properties);
		$this->assertTrue($properties['multiple']);
	}

	public function testValidateCapabilityReturnsValidatorsProperty(): void
	{
		$field = $this->createTextField();
		$capability = new Validate('minLength:5', 'maxLength:100');
		$capability->set($field);

		$properties = $capability->properties($field);

		$this->assertArrayHasKey('validators', $properties);
		$this->assertContains('minLength:5', $properties['validators']);
		$this->assertContains('maxLength:100', $properties['validators']);
	}
}
