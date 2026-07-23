<?php

declare(strict_types=1);

namespace Kloostermanw\CodingStandards\Tests;

use Kloostermanw\CodingStandards\Rector;
use PHPUnit\Framework\TestCase;
use Rector\Configuration\RectorConfigBuilder;
use Rector\Php53\Rector\Ternary\TernaryToElvisRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;

final class RectorTest extends TestCase
{
    public function testConfigureReturnsBuilderThatChains(): void
    {
        $builder = Rector::configure();

        $this->assertInstanceOf(RectorConfigBuilder::class, $builder);

        $chained = $builder
            ->withPaths([__DIR__])
            ->withSkip([]);

        $this->assertInstanceOf(RectorConfigBuilder::class, $chained);
    }

    public function testCommonSkipsReturnsSharedSkips(): void
    {
        $this->assertSame([
            TernaryToElvisRector::class,
            ChangeSwitchToMatchRector::class,
        ], Rector::commonSkips());
    }

    public function testConfigureAcceptsOptionsAndChains(): void
    {
        $builder = Rector::configure([
            'phpSet' => 'php84',
            'skips' => Rector::commonSkips(),
        ]);

        $this->assertInstanceOf(RectorConfigBuilder::class, $builder);
        $this->assertInstanceOf(RectorConfigBuilder::class, $builder->withPaths([__DIR__]));
    }

    public function testConfigureAcceptsAnyPhpSetRectorSupports(): void
    {
        $builder = Rector::configure(['phpSet' => 'php85']);

        $this->assertInstanceOf(RectorConfigBuilder::class, $builder);
    }

    public function testConfigureThrowsOnUnsupportedPhpSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported phpSet "php99"');

        Rector::configure(['phpSet' => 'php99']);
    }

    /**
     * @return array<string, array{string, list<class-string>}>
     */
    public static function phpSetSkipProvider(): array
    {
        return [
            'below lowest tier clamps to common' => ['php74', Rector::commonSkips()],
            'php83 uses common' => ['php83', Rector::commonSkips()],
            'php84 uses its own tier' => ['php84', Rector::common84Skips()],
            'php85 reuses highest tier' => ['php85', Rector::common84Skips()],
            'php86 reuses highest tier' => ['php86', Rector::common84Skips()],
        ];
    }

    /**
     * @param list<class-string> $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('phpSetSkipProvider')]
    public function testSkipsForPhpSetFallsBackByTier(string $phpSet, array $expected): void
    {
        $this->assertSame($expected, Rector::skipsForPhpSet($phpSet));
    }
}
