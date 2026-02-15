<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Field\Meta\Capability;
use Duon\Cms\Field\Text;
use Duon\Cms\Node\NodeFieldOwner;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Value\ValueContext;

use function Duon\Cms\Field\Meta\capabilityErrorMessage;

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

		$owner = new NodeFieldOwner($context, 'test-node');

		return new Text($name, $owner, new ValueContext($name, []));
	}

	public function testCapabilityErrorMessage(): void
	{
		$field = $this->createTextField('title');
		$message = capabilityErrorMessage($field, Capability::class);

		$this->assertStringContainsString('title', $message);
		$this->assertStringContainsString(Text::class, $message);
		$this->assertStringContainsString(Capability::class, $message);
	}
}
