<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Meta\Handler\ColumnsHandler;
use Duon\Cms\Field\Meta\Handler\DefaultValueHandler;
use Duon\Cms\Field\Meta\Handler\DescriptionHandler;
use Duon\Cms\Field\Meta\Handler\FulltextHandler;
use Duon\Cms\Field\Meta\Handler\HiddenHandler;
use Duon\Cms\Field\Meta\Handler\ImmutableHandler;
use Duon\Cms\Field\Meta\Handler\LabelHandler;
use Duon\Cms\Field\Meta\Handler\MultipleHandler;
use Duon\Cms\Field\Meta\Handler\OptionsHandler;
use Duon\Cms\Field\Meta\Handler\RequiredHandler;
use Duon\Cms\Field\Meta\Handler\RowsHandler;
use Duon\Cms\Field\Meta\Handler\TranslateFileHandler;
use Duon\Cms\Field\Meta\Handler\TranslateHandler;
use Duon\Cms\Field\Meta\Handler\ValidateHandler;
use Duon\Cms\Field\Meta\Handler\WidthHandler;
use Duon\Cms\Schema\Columns;
use Duon\Cms\Schema\DefaultValue;
use Duon\Cms\Schema\Description;
use Duon\Cms\Schema\Fulltext;
use Duon\Cms\Schema\Hidden;
use Duon\Cms\Schema\Immutable;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Multiple;
use Duon\Cms\Schema\Options;
use Duon\Cms\Schema\Required;
use Duon\Cms\Schema\Rows;
use Duon\Cms\Schema\Translate;
use Duon\Cms\Schema\TranslateFile;
use Duon\Cms\Schema\Validate;
use Duon\Cms\Schema\Width;

class SchemaRegistry
{
	/** @var array<class-string, SchemaHandler> */
	private array $handlers = [];

	/** @param class-string $metaClass */
	public function register(string $metaClass, SchemaHandler $handler): void
	{
		$this->handlers[$metaClass] = $handler;
	}

	public function getHandler(object $meta): ?SchemaHandler
	{
		return $this->handlers[$meta::class] ?? null;
	}

	public static function withDefaults(): self
	{
		$registry = new self();
		$registry->register(Label::class, new LabelHandler());
		$registry->register(Description::class, new DescriptionHandler());
		$registry->register(Translate::class, new TranslateHandler());
		$registry->register(TranslateFile::class, new TranslateFileHandler());
		$registry->register(Required::class, new RequiredHandler());
		$registry->register(Validate::class, new ValidateHandler());
		$registry->register(DefaultValue::class, new DefaultValueHandler());
		$registry->register(Width::class, new WidthHandler());
		$registry->register(Rows::class, new RowsHandler());
		$registry->register(Columns::class, new ColumnsHandler());
		$registry->register(Hidden::class, new HiddenHandler());
		$registry->register(Immutable::class, new ImmutableHandler());
		$registry->register(Options::class, new OptionsHandler());
		$registry->register(Multiple::class, new MultipleHandler());
		$registry->register(Fulltext::class, new FulltextHandler());

		return $registry;
	}
}
