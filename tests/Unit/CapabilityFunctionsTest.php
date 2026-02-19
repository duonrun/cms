<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Field\Schema\Handler;
use Duon\Cms\Field\Text;
use Duon\Cms\Node\FieldOwner;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\ValueContext;

final class CapabilityFunctionsTest extends TestCase
{
	private function createTextField(string $name = 'test'): Text
	{
		$context = new \Duon\Cms\Context(
			$this->db(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);

		$owner = new FieldOwner($context, 'test-node');

		return new Text($name, $owner, new ValueContext($name, []));
	}

	public function testCapabilityErrorMessage(): void
	{
		$field = $this->createTextField('title');

		// Create a concrete handler to test the protected method
		$handler = new class extends Handler {
			public function apply(object $meta, $field): void {}

			public function properties(object $meta, $field): array
			{
				return [];
			}

			public function testErrorMessage($field, string $capability): string
			{
				return $this->capabilityErrorMessage($field, $capability);
			}
		};

		$message = $handler->testErrorMessage($field, Handler::class);

		$this->assertStringContainsString('title', $message);
		$this->assertStringContainsString(Text::class, $message);
		$this->assertStringContainsString(Handler::class, $message);
	}
}
