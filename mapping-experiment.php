<?php declare(strict_types=1);

// Todo: do not merge this experiment

/**
 * A task that holds a reference to a string and the source id of the mapping
 *
 * It makes it possible to update the referenced string at a later point
 */
class MappingTask
{
    public function __construct(
        public string &$reference,
        public string $sourceId
    ) {
    }
}

class MappingService
{
    public const PLACEHOLDER = 'placeholder-mapping-uuid';

    /**
     * @var list<MappingTask>
     */
    private array $pendingTasks = [];

    /**
     * Important: this method returns a reference to a string which is also internally stored.
     * It must be called like this:
     *
     * $data['someId'] = &$mappingService->getMapping('sourceId');
     *
     * The use of '&' is very important, otherwise it will only copy the placeholder string.
     * More info on this can be found in the PHP docs:
     * https://www.php.net/manual/en/language.references.return.php
     */
    public function &getMapping(string $sourceId): string
    {
        // previously we would fetch the mapping from the DB here,
        // but reaching out to the DB every time is expensive

        // so instead we just return a placeholder string
        // and update it later, when we know all mappings that are needed

        // and then use the string reference to update the placeholders

        $pointer = self::PLACEHOLDER;

        $task = new MappingTask($pointer, $sourceId);
        $this->pendingTasks[] = $task;

        return $pointer;
    }

    public function resolvePendingMappings(): void
    {
        // in here we can fetch all requested mappings from the DB
        // in a single query and update all corresponding strings
        foreach ($this->pendingTasks as $task) {
            $task->reference = 'changed-' . $task->sourceId;
            unset($task->reference);
        }
    }
}

// a stripped-down example of a converter
function convertData(MappingService $mappingService): array
{
    $data['name'] = 'John Doe';
    $data['manufacturerId'] = &$mappingService->getMapping('manufacturer01');
    $data['price'] = [];
    for ($i = 0; $i < 3; ++$i) {
        $data['price'][] = convertCurrency($i, $mappingService);
    }

    return $data;
}

// a stripped-down example of internal logic in a converter
function convertCurrency(int $i, MappingService $mappingService): array
{
    $price = [
        'id' => $i,
        // unfortunately, this does not work, but there is an easy workaround below
        // 'currencyId' => &$mappingService->getMapping('currency0' . $i),
    ];

    // this works fine
    $price['currencyId'] = &$mappingService->getMapping('currency0' . $i);

    return $price;
}

// === Main logic below ===

$mappingService = new MappingService();
$convertedData = convertData($mappingService);
echo '============== Initial data after converter run ==============' . \PHP_EOL;
var_dump($convertedData);

echo '============== data after mapping tasks got resolved ==============' . \PHP_EOL;
$mappingService->resolvePendingMappings();
var_dump($convertedData);

// === Output looks like this ===
/*
============== Initial data after converter run ==============
array(3) {
  ["name"]=>
  string(8) "John Doe"
  ["manufacturerId"]=>
  &string(24) "placeholder-mapping-uuid"
  ["price"]=>
  array(3) {
    [0]=>
    array(2) {
      ["id"]=>
      int(0)
      ["currencyId"]=>
      &string(24) "placeholder-mapping-uuid"
    }
    [1]=>
    array(2) {
      ["id"]=>
      int(1)
      ["currencyId"]=>
      &string(24) "placeholder-mapping-uuid"
    }
    [2]=>
    array(2) {
      ["id"]=>
      int(2)
      ["currencyId"]=>
      &string(24) "placeholder-mapping-uuid"
    }
  }
}
============== data after mapping tasks got resolved ==============
array(3) {
  ["name"]=>
  string(8) "John Doe"
  ["manufacturerId"]=>
  string(22) "changed-manufacturer01"
  ["price"]=>
  array(3) {
    [0]=>
    array(2) {
      ["id"]=>
      int(0)
      ["currencyId"]=>
      string(18) "changed-currency00"
    }
    [1]=>
    array(2) {
      ["id"]=>
      int(1)
      ["currencyId"]=>
      string(18) "changed-currency01"
    }
    [2]=>
    array(2) {
      ["id"]=>
      int(2)
      ["currencyId"]=>
      string(18) "changed-currency02"
    }
  }
}
 */
