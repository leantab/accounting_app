<?php

use App\Enums\TimeTrackerItemTypeEnum;

function langPath(string $path = ''): string
{
    return dirname(__DIR__, 2).'/lang'.($path === '' ? '' : '/'.$path);
}

/**
 * @return list<string>
 */
function langGroups(): array
{
    return array_map(fn (string $file): string => basename($file, '.php'), glob(langPath('es/*.php')));
}

/**
 * @return list<string>
 */
function sourceFiles(): array
{
    $files = [];
    $roots = [dirname(__DIR__, 2).'/app', dirname(__DIR__, 2).'/resources/views'];

    foreach ($roots as $root) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (str_ends_with($file->getFilename(), '.php')) {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

it('has an English file for every Spanish file and the other way around', function (): void {
    $es = array_map('basename', glob(langPath('es/*.php')));
    $en = array_map('basename', glob(langPath('en/*.php')));

    expect($es)->not->toBeEmpty()
        ->and($en)->toBe($es);
});

it('has the same keys and placeholders in Spanish and English', function (string $group): void {
    $es = require langPath("es/{$group}.php");
    $en = require langPath("en/{$group}.php");

    expect(array_keys($en))->toBe(array_keys($es));

    foreach ($es as $key => $text) {
        expect($text)->toBeString()->not->toBeEmpty()
            ->and($en[$key])->toBeString()->not->toBeEmpty();

        preg_match_all('/:[a-z_]+/', $text, $esPlaceholders);
        preg_match_all('/:[a-z_]+/', $en[$key], $enPlaceholders);

        expect($enPlaceholders[0])->toEqualCanonicalizing($esPlaceholders[0], "placeholders of {$group}.{$key}");
    }
})->with(fn (): array => array_combine(langGroups(), array_map(fn (string $group): array => [$group], langGroups())));

it('defines every translation key used in the code in both languages', function (): void {
    $groups = langGroups();
    $missing = [];

    foreach (sourceFiles() as $file) {
        preg_match_all("/__\(\s*'([a-z_]+)\.([a-z_.]+)'/", file_get_contents($file), $matches, PREG_SET_ORDER);

        foreach ($matches as [, $group, $key]) {
            if (! in_array($group, $groups, true)) {
                continue;
            }

            foreach (['es', 'en'] as $locale) {
                $texts = require langPath("{$locale}/{$group}.php");

                if (! array_key_exists($key, $texts)) {
                    $missing[] = "{$locale}: {$group}.{$key} (".basename($file).')';
                }
            }
        }
    }

    expect($missing)->toBe([]);
});

it('translates every English text used in the views into Spanish', function (): void {
    $spanish = json_decode(file_get_contents(langPath('es.json')), true, flags: JSON_THROW_ON_ERROR);
    $missing = [];

    foreach (sourceFiles() as $file) {
        preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*[,)]/", file_get_contents($file), $matches);

        foreach ($matches[1] as $text) {
            $text = str_replace("\\'", "'", $text);

            if (preg_match('/^[a-z_]+\.[a-z_.]+$/', $text) || str_ends_with($text, '.') && preg_match('/^[a-z_]+\./', $text)) {
                continue;
            }

            if (! array_key_exists($text, $spanish)) {
                $missing[] = $text.' ('.basename($file).')';
            }
        }
    }

    expect($missing)->toBe([]);
});

it('keeps no Spanish texts hard coded in the Filament classes', function (): void {
    $offenders = [];
    $pattern = "/->(label|title|heading|description|placeholder|helperText|modalHeading|modalDescription|body)\(\s*'[^']*[áéíóúñÁÉÍÓÚÑ¿¡][^']*'/u";

    foreach (sourceFiles() as $file) {
        if (! str_contains(str_replace('\\', '/', $file), '/app/Filament/')) {
            continue;
        }

        if (preg_match($pattern, file_get_contents($file))) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([]);
});

it('has a display name in both languages for every time tracker item type', function (): void {
    $es = require langPath('es/item_types.php');
    $en = require langPath('en/item_types.php');

    foreach (TimeTrackerItemTypeEnum::cases() as $type) {
        $key = strtolower($type->name);

        expect($es)->toHaveKey($key)
            ->and($en)->toHaveKey($key)
            ->and($es[$key])->toBe($type->label());
    }
});

it('keeps no Spanish texts hard coded in the landing page', function (): void {
    $landing = file_get_contents(dirname(__DIR__, 2).'/resources/views/landing.blade.php');

    expect(preg_match('/[áéíóúñÁÉÍÓÚÑ¿¡]/u', $landing))->toBe(0);
});
