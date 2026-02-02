<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\ValueContext;

/**
 * @internal
 *
 * @coversNothing
 */
final class ValueContextTest extends TestCase
{
	public function testValueContextStoresFieldNameAndData(): void
	{
		$context = new ValueContext('title', ['value' => 'Hello']);

		$this->assertSame('title', $context->fieldName);
		$this->assertSame(['value' => 'Hello'], $context->data);
	}
}
