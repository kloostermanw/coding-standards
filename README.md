# kloostermanw/coding-standards

Shared php-cs-fixer, phpstan/larastan and rector configuration for the genotool Laravel apps.

## Install (consuming app)

Add the VCS repository to the app's `composer.json`:

    "repositories": [
        { "type": "vcs", "url": "https://github.com/kloostermanw/coding-standards" }
    ]

Then require it (this also pulls in php-cs-fixer, larastan and rector as dev tools):

    composer require --dev kloostermanw/coding-standards:^1.0

## Usage

`.php-cs-fixer.php`:

    <?php
    return \Kloostermanw\CodingStandards\PhpCsFixer::configure(__DIR__);

`rector.php`:

    <?php
    declare(strict_types=1);
    return \Kloostermanw\CodingStandards\Rector::configure()
        ->withPaths([__DIR__ . '/app', __DIR__ . '/tests']);

`phpstan.neon.dist`:

    includes:
        - vendor/kloostermanw/coding-standards/phpstan.neon
        - phpstan-baseline.neon
    parameters:
        paths:
            - app/
        excludePaths:
            - app/Models

Keep each app's `phpstan-baseline.neon` local.

## Configurable options

Repos whose style differs from the default can reproduce their own config while
sourcing the tool versions and boilerplate from the package.

### php-cs-fixer

`PhpCsFixer::configure(string $rootPath, array $options = [])` accepts these `$options` keys:

* `rules`: replace the entire rule set. Defaults to the shared rule set.
* `in`: directory names (relative to `$rootPath`) to scan. Defaults to `['app', 'config', 'database', 'resources', 'routes', 'tests']`. Names that do not exist on disk are dropped.
* `excludes`: subdirectory names to exclude. Defaults to `[]` (nothing excluded).
* `name`: filename pattern to include. Defaults to `'*.php'`. Pass `null` to remove the filter.
* `notName`: filename pattern to exclude. Defaults to `'*.blade.php'`. Pass `null` to remove the filter.
* `parallel`: pass `true` to enable parallel runs. Defaults to `false`.

Compose a rule set from the shared fragments:

    use Kloostermanw\CodingStandards\PhpCsFixer;

    return PhpCsFixer::configure(__DIR__, [
        'in' => ['app', 'tests'],
        'rules' => PhpCsFixer::commonRules() + [
            'single_space_around_construct' => PhpCsFixer::singleSpaceAroundConstructNoImplements(),
            'blank_line_before_statement' => PhpCsFixer::blankLineBeforeExtended(),
        ],
    ]);

### Rector

`Rector::configure(array $options = [])` accepts these `$options` keys:

* `phpSet`: the php set to apply. Defaults to `'php84'`. Any set the installed Rector version supports is valid (`'php83'`, `'php84'`, `'php85'`, and so on). An unsupported value throws instead of silently falling back.
* `skips`: replace the base skip list. Defaults to the tier chosen from `phpSet`.

The base skip list is selected by tier. `Rector::commonSkips()` returns the skips shared by
every set (`TernaryToElvisRector`, `ChangeSwitchToMatchRector`). `Rector::common84Skips()`
returns the wider list applied for `'php84'` and higher sets. When you pass `skips` you
replace the whole base list, so compose from `commonSkips()` or `common84Skips()` and add
your own.
