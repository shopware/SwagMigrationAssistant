<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @phpstan-type EnvironmentInfo array{defaultShopLanguage: string, host: string, additionalData: array<int, mixed>, defaultCurrency: string, config: array<string, mixed>, timezone: string|null}
 */
#[Package('fundamentals@after-sales')]
class EnvironmentReader extends AbstractReader implements EnvironmentReaderInterface
{
    private const SOURCE_ROOT_KEY = 'installationRoot';
    private const TIMEZONE_KEY = 'timezone';
    private const DB_KEY = 'db';

    private const ARRAY_OPEN = '[';
    private const ARRAY_CLOSE = ']';
    private const NULL = 'null';
    private const TRIM_QUOTATION = '\'"';

    private const CONFIG_FILE_NAME = 'config.php';

    /**
     * @var array<string, EnvironmentInfo>
     */
    private array $cachedEnvironmentInformation = [];

    /**
     * @return EnvironmentInfo
     */
    public function read(MigrationContextInterface $migrationContext): array
    {
        if (isset($this->cachedEnvironmentInformation[$migrationContext->getConnection()->getId()])) {
            return $this->cachedEnvironmentInformation[$migrationContext->getConnection()->getId()];
        }

        $locale = $this->getDefaultShopLocale($migrationContext);

        $environmentInformation = [
            'defaultShopLanguage' => $locale,
            'host' => $this->getHost($migrationContext),
            'additionalData' => $this->getAdditionalData($migrationContext),
            'defaultCurrency' => $this->getDefaultCurrency($migrationContext),
            'config' => $this->getConfig($migrationContext),
            'timezone' => $this->getTimezone($migrationContext),
        ];

        $this->cachedEnvironmentInformation[$migrationContext->getConnection()->getId()] = $environmentInformation;

        return $environmentInformation;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfig(MigrationContextInterface $migrationContext): array
    {
        $connection = $this->getConnection($migrationContext);

        $configNames = [
            'esdKey',
            'installationDate',
        ];

        $query = $connection->createQueryBuilder();

        $query->select('config.name', 'config.value')
            ->from('s_core_config_elements', 'config')
            ->where('config.name IN (:configNames)')
            ->setParameter('configNames', $configNames, ArrayParameterType::STRING);

        $rows = $query->executeQuery()->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            /** @phpstan-ignore shopware.unserializeUsage */
            $result[$row['name']] = \unserialize($row['value'], ['allowed_classes' => false]);
        }

        return $result;
    }

    protected function getDefaultCurrency(MigrationContextInterface $migrationContext): string
    {
        $connection = $this->getConnection($migrationContext);
        $defaultCurrency = $connection->createQueryBuilder()
            ->select('currency')
            ->from('s_core_currencies')
            ->where('standard = 1')
            ->executeQuery()
            ->fetchOne();

        return $defaultCurrency ?: '';
    }

    private function getHost(MigrationContextInterface $migrationContext): string
    {
        $connection = $this->getConnection($migrationContext);
        $host = $connection->createQueryBuilder()
            ->select('shop.host')
            ->from('s_core_shops', 'shop')
            ->where('shop.default = 1')
            ->andWhere('shop.active = 1')
            ->executeQuery()
            ->fetchOne();

        return $host ?: '';
    }

    /**
     * @return array<int, mixed>
     */
    private function getAdditionalData(MigrationContextInterface $migrationContext): array
    {
        $connection = $this->getConnection($migrationContext);
        $query = $connection->createQueryBuilder();

        $query->from('s_core_shops', 'shop');
        $query->addSelect('shop.id as identifier');
        $this->addTableSelection($query, 's_core_shops', 'shop', $migrationContext);

        $query->leftJoin('shop', 's_core_locales', 'locale', 'shop.locale_id = locale.id');
        $this->addTableSelection($query, 's_core_locales', 'locale', $migrationContext);

        $query->orderBy('shop.main_id');

        $fetchedShops = FetchModeHelper::groupUnique($query->executeQuery()->fetchAllAssociative());

        $shops = $this->mapData($fetchedShops, [], ['shop']);

        foreach ($shops as $key => &$shop) {
            if (isset($shop['main_id']) && $shop['main_id'] !== '') {
                $shops[$shop['main_id']]['children'][] = $shop;
                unset($shops[$key]);
            }
        }

        return \array_values($shops);
    }

    private function getTimezone(MigrationContextInterface $migrationContext): ?string
    {
        $fields = $migrationContext->getConnection()->getCredentialFields();
        if (!isset($fields[self::SOURCE_ROOT_KEY]) || !\is_string($fields[self::SOURCE_ROOT_KEY]) || $fields[self::SOURCE_ROOT_KEY] === '') {
            return null;
        }

        $basePath = $fields[self::SOURCE_ROOT_KEY];
        $configFile = \rtrim($basePath, '/\\') . '/' . self::CONFIG_FILE_NAME;

        $fileSystem = new Filesystem();

        try {
            if (!$fileSystem->exists($configFile)) {
                return null;
            }

            $fileContent = $fileSystem->readFile($configFile);
            $timezone = $this->readDbTimezoneFromConfig($fileContent);

            return $timezone === '' ? null : $timezone;
        } catch (\Throwable) {
            return null;
        }
    }

    private function readDbTimezoneFromConfig(string $phpFileContent): ?string
    {
        $tokens = token_get_all($phpFileContent);
        $inDbArray = false;
        $arrayDepth = 0;

        foreach ($tokens as $index => $token) {
            $value = \is_array($token) ? \trim($token[1], self::TRIM_QUOTATION) : $token;

            if (!$inDbArray && $value === self::DB_KEY) {
                $inDbArray = true;
                continue;
            }

            if (!$inDbArray) {
                continue;
            }

            if ($token === self::ARRAY_OPEN) {
                ++$arrayDepth;
            } elseif ($token === self::ARRAY_CLOSE) {
                --$arrayDepth;
                if ($arrayDepth === 0) {
                    return null;
                }
            }

            if ($arrayDepth === 1 && $value === self::TIMEZONE_KEY) {
                for ($j = $index + 1; isset($tokens[$j]); ++$j) {
                    $next = $tokens[$j];

                    if (\is_array($next) && $next[0] === \T_CONSTANT_ENCAPSED_STRING) {
                        return \stripcslashes(\trim($next[1], self::TRIM_QUOTATION));
                    }

                    if (\is_array($next) && \strtolower($next[1]) === self::NULL) {
                        return null;
                    }
                }
            }
        }

        return null;
    }
}
