<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Field\Meta\Capability;
use Duon\Cms\Field\Text;
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

		$finder = $this->createStub(\Duon\Cms\Finder\Finder::class);

		$nodeClass = new class ($context, $finder, ['content' => []]) extends \Duon\Cms\Node\Document {
			public function title(): string
			{
				return 'Test';
			}
		};

		return new Text($name, $nodeClass, new ValueContext($name, []));
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
