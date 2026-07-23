<?php

declare(strict_types=1);

namespace Kloostermanw\CodingStandards;

use InvalidArgumentException;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Configuration\RectorConfigBuilder;
use Rector\Exception\Configuration\InvalidConfigurationException;
use Rector\Php53\Rector\Ternary\TernaryToElvisRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php84\Rector\Class_\DeprecatedAnnotationToDeprecatedAttributeRector;
use Rector\Php84\Rector\Foreach_\ForeachToArrayAllRector;
use Rector\Php84\Rector\Foreach_\ForeachToArrayAnyRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;

/**
 * Shared Rector configuration for php apps.
 *
 * Provides the common cache location, php rule set, and framework-level skips.
 * Repos call configure() from their local rector.php and chain withPaths()/withSkip()
 * for their own paths and file-scoped skips. The $options argument lets a repo select a
 * different php set or base skip list while still sourcing the tool version and cache setup
 * from the package.
 */
final class Rector
{
    /**
     * Build the shared Rector config builder; callers chain withPaths()/withSkip().
     *
     * The phpSet is passed straight through to RectorConfig's withPhpSets(), so any set
     * that version of Rector supports is valid ('php83', 'php84', 'php85', ...). An
     * unsupported value throws instead of silently falling back to an older set.
     *
     * @param array<string, mixed> $options Optional overrides: phpSet (e.g. 'php84', 'php85'), skips (list<class-string>).
     *
     * @throws InvalidArgumentException|InvalidConfigurationException When phpSet is not supported by the installed Rector version.
     */
    public static function configure(array $options = []): RectorConfigBuilder
    {
        $phpSet = $options['phpSet'] ?? 'php84';

        $supported = self::supportedPhpSets();

        if (!in_array($phpSet, $supported, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported phpSet "%s". Supported values: %s.',
                is_string($phpSet) ? $phpSet : get_debug_type($phpSet),
                implode(', ', $supported),
            ));
        }

        $skips = $options['skips'] ?? self::skipsForPhpSet($phpSet);

        $builder = RectorConfig::configure()
            ->withCache(
                cacheDirectory: '/tmp/rector',
                cacheClass: FileCacheStorage::class,
            )
            ->withPhpSets(...[$phpSet => true]);

        return $builder->withSkip($skips);
    }

    /**
     * The php sets accepted by phpSet, derived from the installed Rector version's
     * withPhpSets() signature so the list never drifts from what Rector actually supports.
     *
     * @return list<string>
     */
    private static function supportedPhpSets(): array
    {
        $parameters = new \ReflectionMethod(RectorConfigBuilder::class, 'withPhpSets')->getParameters();

        return array_map(
            static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
            $parameters,
        );
    }

    /**
     * The skip list for a php set, chosen by tier.
     *
     * Tiers are keyed by the php set version at which a new skip list is introduced,
     * ascending. A requested set uses the highest tier at or below it. A set below the
     * lowest tier clamps to commonSkips(); a set above the highest reuses the highest
     * (so php85/php86 reuse php84's skips until a higher tier is added here).
     *
     * @return list<class-string>
     */
    public static function skipsForPhpSet(string $phpSet): array
    {
        // Only versions that introduce new skips need an entry; the rest fall back.
        $tiers = [
            84 => self::common84Skips(),
        ];
        ksort($tiers);

        $version = (int) substr($phpSet, 3);

        $skips = self::commonSkips();

        foreach ($tiers as $threshold => $tierSkips) {
            if ($version >= $threshold) {
                $skips = $tierSkips;
            }
        }

        return $skips;
    }

    /**
     * The framework-level skips shared by every php app; a starting point for composing skips.
     *
     * @return list<class-string>
     */
    public static function commonSkips(): array
    {
        return [
            TernaryToElvisRector::class,
            ChangeSwitchToMatchRector::class,
        ];
    }

    /**
     * The skips for the php84 tier: commonSkips() plus the rules php84 introduces that
     * we don't want applied automatically. Used for php84 and any higher set without its
     * own tier.
     *
     * @return list<class-string>
     */
    public static function common84Skips(): array
    {
        return [
            TernaryToElvisRector::class, // Was part of the PHP5.3 ruleset
            RemoveExtraParametersRector::class, // Was part of the PHP7.1 ruleset
            // `match` has different semantics than `switch` (strict equality, no fall-through,
            // throws UnhandledMatchError on unmatched input). Conversion requires case-by-case review.
            ChangeSwitchToMatchRector::class,
            DeprecatedAnnotationToDeprecatedAttributeRector::class,
            ForeachToArrayAllRector::class,
            ForeachToArrayAnyRector::class,
            ReturnNeverTypeRector::class // Return type never was added in PHP 8.4
        ];
    }
}
