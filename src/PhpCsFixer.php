<?php

declare(strict_types=1);

namespace Kloostermanw\CodingStandards;

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

/**
 * Shared php-cs-fixer configuration for Laravel apps.
 *
 * Centralizes the finder scope and rule set so every repo formats identically;
 * repos call configure() from their local .php-cs-fixer.php. The $options argument
 * lets a repo reproduce a divergent config (rules, finder scope, parallelism) while
 * still sourcing the tool version and boilerplate from the package.
 */
final class PhpCsFixer
{
    /**
     * Build the shared php-cs-fixer configuration for an app.
     *
     * @param string               $rootPath      Absolute path to the app root (pass __DIR__).
     * @param array<string, mixed> $options       Optional overrides: rules, in, excludes, name, notName, parallel.
     */
    public static function configure(string $rootPath, array $options = []): Config
    {
        $dirNames = $options['in'] ?? ['app', 'config', 'database', 'resources', 'routes', 'tests'];

        $directories = array_values(array_filter(
            array_map(static fn (string $dir): string => $rootPath . '/' . $dir, $dirNames),
            is_dir(...),
        ));

        $finder = Finder::create()->in($directories);

        $excludes = array_key_exists('excludes', $options)
            ? $options['excludes']
            : [];

        if ($excludes !== []) {
            $finder->exclude($excludes);
        }

        $name = array_key_exists('name', $options) ? $options['name'] : '*.php';

        if ($name !== null) {
            $finder->name($name);
        }

        $notName = array_key_exists('notName', $options) ? $options['notName'] : '*.blade.php';

        if ($notName !== null) {
            $finder->notName($notName);
        }

        $finder->ignoreDotFiles(true)->ignoreVCS(true);

        $config = new Config()
            ->setRules($options['rules'] ?? self::rules())
            ->setFinder($finder)
            ->setUsingCache(true)
            ->setCacheFile($rootPath . '/.php-cs-fixer.cache');

        if ($options['parallel'] ?? false) {
            $config->setParallelConfig(ParallelConfigFactory::detect());
        }

        return $config;
    }

    /**
     * The rules shared by every genotool app; a starting point for composing a repo's rule set.
     *
     * @return array<string, mixed>
     */
    public static function commonRules(): array
    {
        return [
            '@PSR12' => true,
            'array_syntax' => ['syntax' => 'short'],
            'concat_space' => ['spacing' => 'one'],
            'no_unused_imports' => true,
        ];
    }

    /**
     * The single_space_around_construct value with `implements` removed from the defaults.
     *
     * @return array<string, list<string>>
     */
    public static function singleSpaceAroundConstructNoImplements(): array
    {
        return [
            'constructs_followed_by_a_single_space' => [
                'abstract', 'as', 'attribute', 'break', 'case', 'catch', 'class', 'clone', 'comment', 'const',
                'const_import', 'continue', 'do', 'echo', 'else', 'elseif', 'enum', 'extends', 'final', 'finally',
                'for', 'foreach', 'function', 'function_import', 'global', 'goto', 'if', 'include', 'include_once',
                'instanceof', 'insteadof', 'interface', 'match', 'named_argument', 'namespace', 'new',
                'open_tag_with_echo', 'php_doc', 'php_open', 'print', 'private', 'private_set', 'protected',
                'protected_set', 'public', 'public_set', 'readonly', 'require', 'require_once', 'return', 'static',
                'switch', 'throw', 'trait', 'try', 'type_colon', 'use', 'use_lambda', 'use_trait', 'var', 'while',
                'yield', 'yield_from',
            ],
        ];
    }

    /**
     * The wider blank_line_before_statement value used by repos that keep more separation.
     *
     * @return array<string, list<string>>
     */
    public static function blankLineBeforeExtended(): array
    {
        return [
            'statements' => [
                'break', 'continue', 'declare', 'for', 'foreach', 'if', 'phpdoc', 'return', 'switch', 'throw', 'try',
            ],
        ];
    }

    /**
     * The default rule set applied when a repo does not pass its own via the `rules` option.
     *
     * @return array<string, mixed>
     */
    protected static function rules(): array
    {
        return [
            '@PSR12' => true,
            'binary_operator_spaces' => ['default' => 'single_space'],
            'ordered_class_elements' => false,
            'class_definition' => false,
            'ordered_imports' => false,
            'single_space_around_construct' => self::singleSpaceAroundConstructNoImplements(),
            # Above this point, are rules that update PSR-12 rules
            # Below this point, additional rules
            'concat_space' => ['spacing' => 'one'],
            'no_unused_imports' => true,
            'no_extra_blank_lines' => [
                'tokens' => [
                    'attribute', 'break', 'case', 'continue', 'curly_brace_block', 'default', 'extra',
                    'parenthesis_brace_block', 'return', 'square_brace_block', 'switch', 'throw', 'use'
                ]
            ],
            'blank_line_before_statement' => self::blankLineBeforeExtended(),
        ];
    }
}
