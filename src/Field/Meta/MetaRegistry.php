<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta;

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

class MetaRegistry
{
	/** @var array<class-string, MetaHandler> */
	private array $handlers = [];

	/** @param class-string $metaClass */
	public function register(string $metaClass, MetaHandler $handler): void
	{
		$this->handlers[$metaClass] = $handler;
	}

	public function getHandler(object $meta): ?MetaHandler
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
