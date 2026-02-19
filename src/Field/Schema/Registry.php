<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

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

class Registry
{
	/** @var array<class-string, Handler> */
	private array $handlers = [];

	/** @param class-string $schema */
	public function register(string $schema, Handler $handler): void
	{
		$this->handlers[$schema] = $handler;
	}

	public function getHandler(object $schema): ?Handler
	{
		return $this->handlers[$schema::class] ?? null;
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
