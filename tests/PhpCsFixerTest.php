<?php

declare(strict_types=1);

namespace Kloostermanw\CodingStandards\Tests;

use Kloostermanw\CodingStandards\PhpCsFixer;
use PhpCsFixer\Config;
use PHPUnit\Framework\TestCase;

final class PhpCsFixerTest extends TestCase
{
    public function testConfigureReturnsConfigWithSharedRules(): void
    {
        $config = PhpCsFixer::configure(__DIR__);

        $this->assertInstanceOf(Config::class, $config);

        $rules = $config->getRules();
        $this->assertSame(true, $rules['@PSR12']);
        $this->assertSame(['default' => 'single_space'], $rules['binary_operator_spaces']);
        $this->assertSame(false, $rules['ordered_imports']);
        $this->assertSame(['spacing' => 'one'], $rules['concat_space']);
        $this->assertSame(true, $rules['no_unused_imports']);
        $this->assertSame(
            PhpCsFixer::singleSpaceAroundConstructNoImplements(),
            $rules['single_space_around_construct'],
        );
        $this->assertArrayHasKey('blank_line_before_statement', $rules);
        $this->assertContains('return', $rules['blank_line_before_statement']['statements']);
    }

    public function testConfigureCacheFileIsUnderRootPath(): void
    {
        $config = PhpCsFixer::configure('/tmp/example-root');

        $this->assertSame('/tmp/example-root/.php-cs-fixer.cache', $config->getCacheFile());
    }

    public function testRulesOptionReplacesRuleSet(): void
    {
        $config = PhpCsFixer::configure('/tmp/example-root', ['rules' => ['@PSR12' => true]]);

        $this->assertSame(['@PSR12' => true], $config->getRules());
    }

    public function testCommonRulesReturnsSharedCore(): void
    {
        $this->assertSame([
            '@PSR12' => true,
            'array_syntax' => ['syntax' => 'short'],
            'concat_space' => ['spacing' => 'one'],
            'no_unused_imports' => true,
        ], PhpCsFixer::commonRules());
    }

    public function testSingleSpaceAroundConstructOmitsImplements(): void
    {
        $rule = PhpCsFixer::singleSpaceAroundConstructNoImplements();

        $this->assertArrayHasKey('constructs_followed_by_a_single_space', $rule);
        $this->assertNotContains('implements', $rule['constructs_followed_by_a_single_space']);
        $this->assertContains('interface', $rule['constructs_followed_by_a_single_space']);
    }

    public function testBlankLineBeforeExtendedListsControlStatements(): void
    {
        $rule = PhpCsFixer::blankLineBeforeExtended();

        $this->assertSame(
            ['break', 'continue', 'declare', 'for', 'foreach', 'if', 'phpdoc', 'return', 'switch', 'throw', 'try'],
            $rule['statements'],
        );
    }

    public function testFinderOptionsControlDirsExcludesAndNames(): void
    {
        $root = sys_get_temp_dir() . '/csfixer-fixture-' . uniqid();
        mkdir($root . '/app/Models', 0o777, true);
        mkdir($root . '/config', 0o777, true);
        mkdir($root . '/resources', 0o777, true);
        file_put_contents($root . '/app/Good.php', "<?php\n");
        file_put_contents($root . '/app/Models/Skip.php', "<?php\n");
        file_put_contents($root . '/config/Conf.php', "<?php\n");
        file_put_contents($root . '/resources/View.blade.php', '');
        file_put_contents($root . '/resources/Other.php', "<?php\n");

        try {
            // Defaults: all six dirs, no excludes, *.php only, no *.blade.php.
            $defaults = $this->finderFiles(PhpCsFixer::configure($root));
            $this->assertContains('Good.php', $defaults);
            $this->assertContains('Conf.php', $defaults);
            $this->assertContains('Other.php', $defaults);
            $this->assertContains('Models/Skip.php', $defaults);
            $this->assertNotContains('View.blade.php', $defaults);

            // `in` restricts the dirs; `excludes` drops a subdirectory.
            $appOnly = $this->finderFiles(PhpCsFixer::configure($root, ['in' => ['app'], 'excludes' => ['Models']]));
            $this->assertContains('Good.php', $appOnly);
            $this->assertNotContains('Models/Skip.php', $appOnly);
            $this->assertNotContains('Conf.php', $appOnly);

            // `name`/`notName` null removes the filters, pulling in non-php and blade files.
            $noFilters = $this->finderFiles(PhpCsFixer::configure($root, [
                'in' => ['resources'],
                'name' => null,
                'notName' => null,
            ]));
            $this->assertContains('Other.php', $noFilters);
            $this->assertContains('View.blade.php', $noFilters);
        } finally {
            exec('rm -rf ' . escapeshellarg($root));
        }
    }

    /**
     * @return list<string> paths relative to each finder search directory
     */
    private function finderFiles(Config $config): array
    {
        $files = [];

        foreach ($config->getFinder() as $file) {
            if ($file instanceof \Symfony\Component\Finder\SplFileInfo) {
                $files[] = $file->getRelativePathname();
            }
        }

        return $files;
    }
}
