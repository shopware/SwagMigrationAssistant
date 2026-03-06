<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

/**
 * A promise that holds a reference to a string and the data for looking up a mapping.
 * It makes it possible to update the referenced string at a later point and fulfill the promise to
 * replace it with an actual UUID.
 *
 * If you are familiar with the JS world, this is a bit similar to a JS Promise, but
 * there is no automatic executor in the background and these need to be manually fulfilled.
 *
 * @see MappingServiceV2
 *
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MappingPromise
{
    public function __construct(
        /**
         * points to the placeholder string that needs to be replaced with an actual UUID
         */
        public string &$reference,
        /**
         * entity used for mapping lookup
         */
        public string $entity,
        /**
         * source id used for mapping lookup
         */
        public string $sourceId,
        /**
         * if true, the mapping will be created in DB if it does not exist
         */
        public bool $shouldCreate = false,
        /**
         * if creating, use this Uuid to map to
         */
        public ?string $createWith = null,
    ) {
    }

    public function resolve(string $uuid): void
    {
        $this->reference = $uuid;
        unset($this->reference);
    }
}
