<?php declare(strict_types=1);

use Danger\Config;
use Danger\Context;
use Danger\Platform\Gitlab\Gitlab;
use Danger\Rule\Condition;
use Danger\Struct\File;
use Danger\Struct\Gitlab\File as GitlabFile;

return (new Config())
    ->useThreadOn(Config::REPORT_LEVEL_WARNING)
    ->useRule(new Condition(
        function (Context $context) {
            $labels = array_map('strtolower', $context->platform->pullRequest->labels);

            return $context->platform instanceof Gitlab && !\in_array('skip-danger', $labels, true);
        },
        [
            function (Context $context): void {
                $files = $context->platform->pullRequest->getFiles();

                /** @var Gitlab $gitlab */
                $gitlab = $context->platform;

                $phpstanBaseline = new GitlabFile(
                    $gitlab->client,
                    $_SERVER['CI_PROJECT_ID'],
                    'phpstan-baseline.neon',
                    $gitlab->raw['sha']
                );

                $fileNames = $files->map(fn(File $f) => $f->name);

                $filesWithIgnoredErrors = [];
                foreach ($fileNames as $fileName) {
                    if (str_contains($phpstanBaseline->getContent(), 'path: ' . $fileName)) {
                        $filesWithIgnoredErrors[] = $fileName;
                    }
                }

                if ($filesWithIgnoredErrors) {
                    $context->failure(
                        'Some files you touched in your MR contain ignored phpstan errors. Please be nice and fix all ignored errors for the following files:<br>'
                        . implode('<br>', $filesWithIgnoredErrors)
                    );
                }
            },
        ]
    ));
