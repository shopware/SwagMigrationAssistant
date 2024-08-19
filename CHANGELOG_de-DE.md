# 13.2.0
- MIG-1035 - Die Premapping-Zuweisung im Admin wurde geändert, wodurch Probleme mit der Zuweisung auf späteren Paginierungsseiten und dem Verlust von Änderungen bei neu ausgewählten Daten behoben sein sollten
- MIG-1042 - Fügt einen Hinweises bezüglich des Löschen des Caches hinzu, wenn der Shopware 5 Connector nicht erkannt wird.
- MIG-1045 - Korrigiert den angezeigten prozentualen Status beim Lesen von Daten
- MIG-1046 - Behebung eines Problems bei der Validierung von json-Feldern für einen Migrationslauf, das den Start einer Migration in der neuesten Shopware-Version verhindern konnte

# 13.1.0
- MIG-981 - Die Medienmigration verwendet jetzt das temporäre Verzeichnis des Systems zum Herunterladen von Dateien.
- MIG-1016 - Verbessert die Warnungen für unterschiedliche Standardwährung und Standardsprache in der Datenauswahl.
- MIG-1016 - Fügt einen neuen Block `{% block swag_migration_confirm_warning_alert %}` in `swag-migration/component/card/swag-migration-confirm-warning/swag-migration-confirm-warning.html.twig` hinzu.
- MIG-1037 - Behebt ein seltenes Problem, dass in bestimmten Situationen nicht alle Entitäten migriert werden (bzw. einige übersprungen werden). Wurde bei Übersetzungen von SW5 festgestellt.

# 13.0.0
- MIG-945 - [BREAKING] Änderung des Methodennamens `getMedia` zu `setMedia` in `SwagMigrationAssistant\Profile\Shopware\Converter\PropertyGroupOptionConverter`
- MIG-945 - [BREAKING] CLI-Befehl `migration:migrate` entfernt und verwende stattdessen `migration:start`
- MIG-945 - [BREAKING] Geänderte Methode `writePremapping` von `SwagMigrationAssistant\Controller\PremappingController`
    - Rückgabetyp von `JsonResponse` auf `Response` geändert
    - Parameter `runUuid` entfernt
- MIG-945 - [BREAKING] Methode `finishMigration` von `SwagMigrationAssistant\Controller\StatusController` entfernt
- MIG-945 - [BREAKING] Typ des Feldes `Premapping` von `SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition` von `JsonField` auf das neue `PremappingField` geändert
- MIG-945 - [BREAKING] Typ des Feldes `progress` von `SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition` von `JsonField` auf das neue `MigrationProgressField` geändert
- MIG-945 - [BREAKING] Rückgabetyp von `getProgress` von `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity` von `?array` zu `?MigrationProgress` geändert
- MIG-945 - [BREAKING] Parametertyp von `setProgress` von `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity` von `array` auf `MigrationProgress` geändert
- MIG-945 - [BREAKING] Rückgabetyp von `writeData` von `SwagMigrationAssistant\Migration\DataWriter` von `void` auf `int` geändert
- MIG-945 - [BREAKING] Rückgabetyp von `writeData` von `SwagMigrationAssistant\Migration\DataWriterInterface` von `void` auf `int` geändert
- MIG-945 - [BREAKING] Parameter `fileChunkByteSize` der Methode `process` von `SwagMigrationAssistant\Migration\Media\Processor\HttpDonwloadServiceBase` entfernt
- MIG-945 - [BREAKING] Parameter `fileChunkByteSize` der Methode `process` von `SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface` entfernt
- MIG-945 - [BREAKING] Parameter `fileChunkByteSize` der Methode `processMediaFiles` von `SwagMigrationAssistant\Migration\Service\MediaFileProcessorService` entfernt
- MIG-945 - [BREAKING] Parameter `fileChunkByteSize` der Methode `processMediaFiles` von `SwagMigrationAssistant\Migration\Service\MediaFileProcessorServiceInterface` entfernt
- MIG-945 - [BREAKING] Parameter `fileChunkByteSize` der Methode `process` von `SwagMigrationAssistant\Profile\Shopware\Media\LocalMediaProcessor` entfernt
- MIG-945 - [BREAKING] Parameter `fileChunkByteSize` der Methode `process` von `SwagMigrationAssistant\Profile\Shopware\Media\LocalOrderDocumentProcessor` entfernt
- MIG-945 - [BREAKING] Parameter `fileChunkByteSize` der Methode `process` von `SwagMigrationAssistant\Profile\Shopware\Media\LocalProductDownloadProcessor` entfernt
- MIG-945 - [BREAKING] Parameter `context` in `migrationContext` der Methode `getProcessor` von `SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface` umbenannt
- MIG-945 - [BREAKING] Parameters `context` in `migrationContext` der Methode `getProcessor` von `SwagMigrationAssistant\Profile\Shopware6\Media\HttpOrderDocumentGenerationService` umbenannt
- MIG-945 - [BREAKING] Eigenschaft `fileChunkByteSize` von `SwagMigrationAssistant\Migration\MessageQueue\MessageProcessMediaMessage` entfernt
- MIG-945 - [BREAKING] Eigenschaft `runRepo` von `SwagMigrationAssistant\Migration\Service\PremappingService` entfernt
- MIG-945 - [BREAKING] Änderungen in `SwagMigrationAssistant\Migration\Service\PremappingServiceInterface` / `SwagMigrationAssistant\Migration\Service\PremappingService`
    - Parameter `run` der Methode `generatePremapping` entfernt
    - Parameter `dataSelectionIds` zur Methode `generatePremapping` hinzugefügt
- MIG-945 - [BREAKING] Konstruktorparameter `generalSettingRepository` und `migrationConnectionRepository` zu `SwagMigrationAssistant\Migration\MigrationContextFactory` hinzugefügt
- MIG-945 - [BREAKING] Methode `createBySelectedConnection` zur Schnittstelle `SwagMigrationAssistant\Migration\MigrationContextFactoryInterface` hinzugefügt
- MIG-945 - [BREAKING] Klasse/Interface/Struct entfernt:
    - `SwagMigrationAssistant\Profile\Shopware\Exception\LocalReaderNotFoundException`, stattdessen `MigrationException::readerNotFound` verwenden
    - `SwagMigrationAssistant\Profile\Shopware\Exception\PluginNotInstalledException` verwende stattdessen `MigrationShopwareProfileException::pluginNotInstalled`
    - `SwagMigrationAssistant\Controller\MigrationController`
    - `SwagMigrationAssistant\Migration\Service\MigrationProgressServiceInterface`
    - `SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenStruct`
    - `SwagMigrationAssistant\Exception\ProcessorNotFoundException` verwende stattdessen `MigrationException::processorNotFound`
    - `SwagMigrationAssistant\Exception\EntityNotExistsException` verwende stattdessen `MigrationException::entityNotExists`
    - `SwagMigrationAssistant\Exception\GatewayNotFoundException` verwende stattdessen `MigrationException::gatewayNotFound`
    - `SwagMigrationAssistant\Exception\InvalidConnectionAuthenticationException` verwenden stattdessen `MigrationException::invalidConnectionAuthentication`
    - `SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException` verwenden stattdessen `MigrationException::migrationContextPropertyMissing`
    - `SwagMigrationAssistant\Exception\MigrationIsRunningException` verwendet stattdessen `MigrationException::migrationIsAlreadyRunning`
    - `SwagMigrationAssistant\Exception\MigrationRunUndefinedStatusException` verwenden stattdessen `MigrationException::undefinedRunStatus`
    - `SwagMigrationAssistant\Exception\MigrationWorkloadPropertyMissingException` verwenden stattdessen `MigrationException::undefinedRunStatus`
    - `SwagMigrationAssistant\Exception\NoFileSystemPermissionsException` verwenden stattdessen `MigrationException::noFileSystemPermissions`
    - `SwagMigrationAssistant\Exception\ProfileNotFoundException` verwenden stattdessen `MigrationException::profileNotFound`
    - `SwagMigrationAssistant\Exception\ReaderNotFoundException` verwenden stattdessen `MigrationException::readerNotFound`
    - `SwagMigrationAssistant\Exception\ReaderNotFoundException` verwenden stattdessen `MigrationException::requestCertificateInvalid`
    - `SwagMigrationAssistant\Exception\SslRequiredException` verwenden stattdessen `MigrationException::sslRequired`
    - `SwagMigrationAssistant\Migration\Service\ProgressState`
    - `SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService`
- MIG-945 - [BREAKING] Folgende Klassen/Methoden werden intern:
    - `SwagMigrationAssistant\Migration\MessageQueue\Handler\CleanupMigrationHandler`
    - `SwagMigrationAssistant\Migration\MessageQueue\Handler\ProcessMediaHandler`
    - `SwagMigrationAssistant\Migration\Service\MigrationProgressService`
- MIG-945 - [BREAKING] Änderungen in `SwagMigrationAssistant\Migration\Run\RunService` / `SwagMigrationAssistant\Migration\Run\RunServiceInterface`
    - Eigenschaften `accessTokenService`, `migrationDataRepository`, `mediaFileRepository`, `indexer`, `cache` wurden entfernt
    - Methoden `takeoverMigration`, `calculateWriteProgress`, `calculateMediaFilesProgress`, `calculateCurrentTotals`, `finishMigration` wurden entfernt
    - Parameter `abortMigration` der Methode `abortMigration` wurde entfernt
- MIG-945 - [BREAKING] Parameter `migrationContext` der Methode `setNumberRangeSalesChannels` von `SwagMigrationAssistant\Profile\Shopware\Converter\NumberRangeConverter` entfernt
- MIG-945 - [BREAKING] Parameter `migrationContext` der Methode `setNumberRangeTranslation` von `SwagMigrationAssistant\Profile\Shopware\Converter\NumberRangeConverter` entfernt
- MIG-945 - [BREAKING] Parameter `context` und `converted` der Methode `getLineItems` von `SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter` entfernt
- MIG-962 - [BREAKING] Getter und Setter für `Premapping` bei `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity` entfernt, verwende stattdessen `\SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity`
- MIG-991 - [BREAKING] Parameter `SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface` zum Konstruktor `\SwagMigrationAssistant\Migration\Run\RunService` hinzugefügt
- MIG-991 - [BREAKING] Parameter `$context` zu `\SwagMigrationAssistant\Migration\Run\RunServiceInterface::cleanupMigrationData` und Implementationen hinzugefügt
- MIG-991 - [BREAKING] Parameter `$context` zu `\SwagMigrationAssistant\Controller\StatusController::cleanupMigrationData` hinzugefügt
- MIG-991 - [BREAKING] Eigenschaft `$status` von `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity` entfernt und das Feld in der entsprechenden Definition umbenannt, verwende stattdessen `$step`.
- MIG-991 - [BREAKING] Methode `getStatus` von `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity` entfernt, verwende stattdessen `getStep` oder `getStepValue`
- MIG-991 - [BREAKING] Methode `setStatus` von `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity` entfernt, verwende stattdessen `SwagMigrationAssistant\Migration\Run\RunTransitionService::transitionToRunStep`.
- MIG-991 - [BREAKING] Konstanten `STATUS_RUNNING`, `STATUS_FINISHED` und `STATUS_ABORTED` von `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity` entfernt, verwende stattdessen `SwagMigrationAssistant\Migration\Run\MigrationStep`
- MIG-962 - [BREAKING] Jede Admin-Komponente ist jetzt privat / intern
- MIG-994 - [BREAKING] Entfernen der Felder `user_id` und `access_token` aus `swag_migration_run` und der entsprechenden EntityDefinition und den zugehörigen Klassen
- MIG-1009 - Verhindert das migrierte Bestellungen die Anpassung des Produkt-Warenbestands auslösen
- MIG-1011 - Sicherstellung, dass Datenbankattribute (wie z. B. "stringify fetches") bei lokalen Gateway-Verbindungen immer gesetzt sind

# 12.0.0
- MIG-983 - Korrigiert einen Fehler bei der Migration von Kunden, die an einen Shop gebunden sind
- MIG-983 - [BREAKING] In der Klasse `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter` wurde der Methodenname `getAddresses` in `applyAddresses` geändert.
- MIG-983 - [BREAKING] In der Klasse `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter` wurde der Methodenname `getCountryTranslation` in `applyCountryTranslation` geändert.
- MIG-983 - [BREAKING] In der Klasse `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter` wurde der Methodenname `getCountryStateTranslation` in `applyCountryStateTranslation` geändert.
- MIG-986 - Behebt einen Fehler bei der Migration von Trackingnummern in den Bestellungen
- MIG-989 - Verbesserung der Migration von Mediendateien
- MIG-989 - [BREAKING] Neue Methode `filterUnwrittenData` zu `SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface` hinzugefügt
- MIG-990 - Verbesserung der Stabilität bei der Migration von Medien
- MIG-990 - [BREAKING] Hinzufügen eines weiteren Konstruktorparameters zum `BaseMediaService` und ändern aller betroffenden Klassen
- MIG-990 - [BREAKING] Hinzufügen der Methode `setProcessedFlag` zu dem `BaseMediaService` und ändern aller betroffenden Klassen
- MIG-990 - [BREAKING] `BaseMediaService` wurde zum `SwagMigrationAssistant\Migration\Media\Processor` Namespace verschoben
- MIG-992 - Korrigiert einen Fehler bei der Migration von Produktseodaten
- MIG-1006 - Behebt einen Fehler bei der Migration von Produkt-Coverbildern

# 11.0.1
- MIG-988 - Ein Fehler der Premapping-UI wurde behoben, um kompatible mit der neusten Platform version zu bleiben

# 11.0.0
- MIG-951 - Verbessert die Sprachmigration von Shopware 6 zu Shopware 6, damit die Standardsprache nicht mehr überschrieben wird.
- MIG-951 - [BREAKING] Ändert den Zugriffsmodifikator der Funktion `\SwagMigrationAssistant\Profile\Shopware6\Converter\LanguageConverter::convertData` von public auf protected
- MIG-943 - Korrigiert einen Fehler in der Migration der Versandkostenberechnung von Shopware 5
- MIG-943 - [BREAKING] In der Klasse `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter` wurde der Methodenname `getTransactions` in `applyTransactions` geändert
- MIG-943 - [BREAKING] In der Klasse `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter` wurde der Methodenname `getCountryTranslation` in `applyCountryTranslation` geändert
- MIG-943 - [BREAKING] In der Klasse `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter` wurde der Methodenname `getCountryStateTranslation` in `applyCountryStateTranslation` geändert
- MIG-943 - [BREAKING] Wechselt die Exception `AssociationEntityRequiredMissingException` zu `SwagMigrationAssistant\Exception\MigrationException::associationMissing` in der Methode `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter::convert`
- MIG-967, MIG-866 - Verbesserung der Migration von Bestell-Dokumenten

# 10.0.1
- MIG-971 - Korrigiert Kompatibilität mit Shopware 6.6.0.x

# 10.0.0
- NEXT-34526 - [BREAKING] Der Parameter `result` der Methode `SwagMigrationAssistant\DataProvider\Provider\Data\AbstractProvider::cleanupSearchResult` wurde um den nativen Typ erweitert.
- NEXT-34526 - [BREAKING] Die Datei `DataProvider/Exception/ProviderHasNoTableAccessException.php` wurde entfernt, stattdessen `SwagMigrationAssistant\Exception\MigrationException::providerHasNoTableAccess` verwenden.
- NEXT-34526 - [BREAKING] Die Datei `Profile/Shopware/Exception/ParentEntityForChildNotFoundException.php` wurde entfernt, stattdessen `SwagMigrationAssistant\Exception\MigrationException::parentEntityForChildNotFound` verwenden.
- NEXT-34526 - [BREAKING] In der Klasse `SwagMigrationAssistant\Profile\Shopware\Converter\CategoryConverter` wurde der Methodenname `getMediaTranslation` in `addMediaTranslation` geändert.
- NEXT-34526 - [BREAKING] In der Klasse `SwagMigrationAssistant\Profile\Shopware\Converter\TranslationConverter` wurde der Methodenname `getAttribute` in `addAttribute` geändert.
- NEXT-34526 - [BREAKING] Der Parametername `blacklist` wurde in `excludeList` in der Methode `SwagMigrationAssistant\Profile\Shopware\Converter\ShopwareConverter::getAttributes` geändert.
- MIG-868 - Verbesserung der Produkt-Migration, der Brutto-Einkaufspreis wird jetzt berechnet
- MIG-966 - [BREAKING] Die Quelldateien wurden in das `src`-Verzeichnis verschoben und das `Test`-Verzeichnis in `tests` umbenannt, ohne Namespaces zu brechen, jedoch muss das Plugin evtl. neu aktiviert bzw. installiert werden. Damit wurde ein Fehler in unserem Deployment-Prozess behoben, um das Plugin korrekt zu bauen.
- MIG-930 - Verbesserung der Cross-Selling-Migration, das Cross-Selling überschreibt nicht mehr das bestehende Cross-Selling, wenn es keine Änderungen gibt.

# 9.0.0
- MIG-848 - Verbesserung der Zuordnung von Produkten zu Verkaufskanälen über Sub- und Sprachshops
- MIG-920 - Unterstützte Shopware 6 Version auf 6.6 geändert und Anpassungen an den Profilen vorgenommen
- MIG-920 - `Migration/Gateway/HttpClientInterface` hinzugefügt, um die Verwendung von HTTP-Clients zu vereinheitlichen
- MIG-920 - `Migration/Gateway/HttpSimpleClient.php` als einfacher Wrapper um den bisher verwendeten `GuzzleHttp/Client` hinzugefügt
- MIG-920 - [BREAKING] Die Datei `Profile/Shopware6/Gateway/Verbindung/AuthClientInterface.php` wurde entfernt, verwende stattdessen `Migration/Gateway/HttpClientInterface`
- MIG-920 - [BREAKING] Die Datei `Profile/Shopware/Gateway/Api/Reader/EnvironmentReader.php` wurde geändert, um `Migration/Gateway/HttpClientInterface` anstelle von `GuzzleHttp/Client` zu verwenden
- MIG-920 - [BREAKING] Die Datei `Profile/Shopware/Gateway/Connection/ConnectionFactory.php` verwendet nun `Migration/Gateway/HttpClientInterface` anstelle von `GuzzleHttp/Client`.
- MIG-920 - [BREAKING] Die Datei `Profile/Shopware/Gateway/Verbindung/ConnectionFactoryInterface.php` wurde geändert und verwendet nun `Migration/Gateway/HttpClientInterface` anstelle von `GuzzleHttp/Client`.
- MIG-920 - [BREAKING] Die Datei `Profile/Shopware6/Gateway/Api/Reader/EnvironmentReader.php` verwendet nun `Migration/Gateway/HttpClientInterface` anstelle von `GuzzleHttp/Client`.
- MIG-920 - [BREAKING] Die Datei `Profile/Shopware6/Gateway/Verbindung/AuthClient.php` verwendet nun `Migration/Gateway/HttpClientInterface` anstelle von `GuzzleHttp/Client`.
- MIG-920 - [BREAKING] Die Datei `Profile/Shopware6/Gateway/Verbindung/ConnectionFactory.php` verwendet nun `Migration/Gateway/HttpClientInterface` anstelle von `GuzzleHttp/Client`.
- MIG-920 - [BREAKING] Die Datei `Profile/Shopware6/Gateway/Connection/ConnectionFactoryInterface.php` wurde geändert, um `Migration/Gateway/HttpClientInterface` anstelle von `GuzzleHttp/Client` zu verwenden
- MIG-920 - `Migration/Media/Processor/HttpDownloadServiceBase.php` hinzugefügt, um das Herunterladen von Mediendateien zu vereinheitlichen
- MIG-920 - [BREAKING] Übergeordnete Klasse und Implementierung von `Profile/Shopware/Media/HttpMediaDownloadService.php` zu `Migration/Media/Processor/HttpDownloadServiceBase.php` geändert
- MIG-920 - [BREAKING] Übergeordnete Klasse und Implementierung von `Profile/Shopware/Media/HttpOrderDocumentProcessor.php` zu `Migration/Media/Processor/HttpDownloadServiceBase.php` geändert
- MIG-920 - [BREAKING] Umbenennung von `Profile/Shopware/Media/HttpOrderDocumentProcessor.php` in `Profile/Shopware/Media/HttpOrderDocumentDownloadService.php`.
- MIG-920 - [BREAKING] Übergeordnete Klasse und Implementierung von `Profile/Shopware/Media/HttpProductDownloadProcessor.php` in `Migration/Media/Processor/HttpDownloadServiceBase.php` geändert
- MIG-920 - [BREAKING] Umbenennung von `Profile/Shopware/Media/HttpProductDownloadProcessor.php` in `Profile/Shopware/Media/HttpEsdFileDownloadService.php`
- MIG-920 - [BREAKING] Übergeordnete Klasse und Implementierung von `Profile/Shopware6/Media/HttpMediaDownloadService.php` zu `Migration/Media/Processor/HttpDownloadServiceBase.php` geändert
- MIG-920 - [BREAKING] Geänderte übergeordnete Klasse und Implementierung von `Profile/Shopware6/Media/HttpOrderDocumentService.php` zu `Migration/Media/Processor/HttpDownloadServiceBase.php`.
- MIG-920 - [BREAKING] Umbenennung von `Profile/Shopware6/Media/HttpOrderDocumentService.php` in `Profile/Shopware/Media/HttpOrderDocumentDownloadService.php`
- MIG-934 - Neue Route `/api/_action/data-provider/download-private-file/{file}` zum Abrufen digitaler Produktdateien hinzugefügt
- MIG-934 - Die Datei `DataProvider/Provider/Data/ProductProvider.php` wurde geändert, um Download-Medien für digitale Produkte einzubeziehen
- MIG-934 - Die Datei `Profile/Shopware6/Converter/ProductConverter.php` wurde geändert, um Download-Medien für digitale Produkte einzubeziehen
- MIG-934 - Datei `Profile/Shopware6/DataSelection/DataSet/ProductDownloadDataSet.php` hinzugefügt
- MIG-934 - Datei `Profile/Shopware6/Media/HttpProductDownloadService.php` hinzugefügt
- MIG-934 - [BREAKING] Die Datei `Controller/DataProviderController.php` wurde in final geändert.

# 8.0.0
- MIG-274 - Behebt einen Fehler in der Migration von Cross-Selling Produkten
- MIG-825 - Verbesserung der Performance bei der Migration von Bestellungen
- MIG-825 - Hinzufügen der Optionen `step-size` zum Cli-Befehl `migration:migrate` von `Command/MigrationCommand.php`
- MIG-825 - [BREAKING] Hinzufügen des Parameters `where` zu `fetchIdentifiers` von `Profile/Shopware/Gateway/Local/Reader/AbstractReader.php`
- MIG-825 - [BREAKING] Ändern der Funktionen von `Profile/Shopware/Gateway/Local/Reader/AbstractReader.php` auf final:
    - `setConnection`
    - `addTableSelection`
    - `buildArrayFromChunks`
    - `cleanupResultSet`
    - `fetchIdentifiers`
    - `getDefaultShopLocale`
    - `mapData`
    - `getDataSetEntity`
- MIG-838 - Meta-Informationsfeldern zu der Migration von Kategorieübersetzungen hinzugefügt
- MIG-839 - Zustatzfelder zu der Migration von Kategorieübersetzungen hinzugefügt
- MIG-899 - Geändertes Verhalten der Migration von SEO URLs. Die URL Groß-/Kleinschreibung von Shopware 5 wird nun berücksichtigt
- MIG-931 - [BREAKING] `Migration/MessageQueue/Handler/ProcessMediaHandler.php` zu final geändert
- MIG-931 - [BREAKING] `AsyncMessageInterface` zu `Migration/MessageQueue/Message/CleanupMigrationMessage.php` hinzugefügt
- MIG-931 - [BREAKING] `AsyncMessageInterface` zu `Migration/MessageQueue/Message/ProcessMediaMessage.php` hinzugefügt
- MIG-931 - [BREAKING] Methoden in `Migration/MessageQueue/Message/ProcessMediaMessage.php` entfernt:
    - `readContext`
    - `withContext`
    - `getDataSet`
    - `setDataSet`
- MIG-931 - [BREAKING] Rückgabeparameter von `getContext` von `string` auf `Shopware\Core\Framework\Context` in `Migration/MessageQueue/Message/ProcessMediaMessage.php` geändert
- MIG-931 - [BREAKING] Parameter von `setContext` von `string` auf `Shopware\Core\Framework\Context` in `Migration/MessageQueue/Message/ProcessMediaMessage.php` geändert
- MIG-931 - Methode `getEntityName` und `setEntityName` zu `Migration/MessageQueue/Message/ProcessMediaMessage.php` hinzugefügt
- MIG-937 - Zeigt von jetzt an immer die aktuelle Shopware 6 Version als kompatibel an, anstatt ältere Versionen
- MIG-938 - Behebt Berechnungsfehler bei der Migration von Versandkostenpreisen

# 7.0.2
- MIG-908 - Behebung der Shopware 6 Migration von `system_config` Entitäten, die nicht zwischen verschiedenen Shops migriert werden sollten

# 7.0.1
- MIG-907 - Shopware 6 Profilenamen in Verbindungen korrigiert

# 7.0.0
- NEXT-31367 - Verbesserung der ConnectionFactory, damit sie stabiler funktioniert
- MIG-881 - Behebt einen Fehler bei der Konvertierung von Versandarten und Versandkosten und migriert auch Versandarten mit unbekanntem Berechnungstyp.
- MIG-878 - Korrektur der Migration von SW6.5 zu SW6.5. Nur gleiche Major-Versionen werden unterstützt.
- MIG-905 - Hotfix / bekanntes Problem für SW6->SW6: `canonicalProductId` von `product` wird nicht migriert, verhindert aber vorerst nicht die Migration von Produkten.
- MIG-905 - Hotfix / bekanntes Problem für SW6->SW6: `cmsPageId` von `product` wird nicht migriert, verhindert aber vorerst nicht die Migration von Produkten.
- MIG-905 - Hotfix / bekanntes Problem für SW6->SW6: `promotionId` von Einzelposten einer Bestellung wird nicht migriert, verhindert aber nicht die Migration von Bestellungen.
- MIG-881 - [BREAKING] Methode `getDefaultAvailabilityRule` vom `Migration/Mapping/MappingServiceInterface.php` wurde entfernt und alle Implementierung angepasst. Nutze stattdessen das Premapping von `default_shipping_availability_rule`.
- MIG-881 - [BREAKING] Parameter `customerRepository` von `Migration/MessageQueue/OrderCountIndexer.php` wurde entfernt.
- MIG-878 - [BREAKING] Alle Klassen unter `Profile/Shopware63` wurden entfernt. Verwenden Sie stattdessen die Klassen unter `Profile/Shopware6`.
- MIG-878 - [BREAKING] Alle Konverter unter `Profile/Shopware6/Converter` wurden so geändert, dass sie nicht `abstrakt` sind und die entsprechenden `supports` Methoden implementieren. Diese ersetzen nun die alten Konverter unter `Profile/Shopware63/Converter`.
- MIG-878 - [BREAKING] Umbenennung von `Profile/Shopware63/Shopware63Profile.php` in `Profile/Shopware6/Shopware6MajorProfile`.
- MIG-878 - [BREAKING] `Profile/Shopware6/Shopware6MajorProfile` wurde geändert, um nur noch die aktuelle SW6 Major-Version zu unterstützen.
- MIG-878 - [BREAKING] `Profile/Shopware6/Shopware6MajorProfile` liefert nun `shopware6major` bei `getName`.
- MIG-878 - [BREAKING] Umbenennung der Vue-Komponente `swag-migration-profile-shopware6-api-credential-form` in `swag-migration-profile-shopware6major-api-credential-form`.
- MIG-878 - [BREAKING] Umbenennung der Vue-Komponente `swag-migration-profile-shopware6-api-page-information` in `swag-migration-profile-shopware6major-api-page-information`.
- MIG-878 - [BREAKING] Die Vue-Komponente `swag-migrationsprofil-shopware63-api-credential-form` wurde entfernt.
- MIG-878 - [BREAKING] Die Vue-Komponente `swag-migration-profile-shopware63-api-page-information` wurde entfernt.
- MIG-878 - [BREAKING] Die Datei `Profile/Shopware6/DataSelection/DataSet/ProductMainVariantRelationDataSet.php` wurde entfernt, da sie bereits mit der Entität `Product` in SW6 migriert wurde.
- MIG-878 - [BREAKING] Die Datei `DataProvider/Provider/Data/ProductMainVariantRelationProvider.php` wurde entfernt, da sie bereits mit der Entität `Product` in SW6 migriert wurde.
- MIG-878 - [BREAKING] Die Datei `Profile/Shopware6/Gateway/Api/Reader/ProductMainVariantRelationReader.php` wurde entfernt, da sie bereits mit der Entität `Product` in SW6 migriert wurde.
- MIG-878 - [BREAKING] Die Datei `Profile/Shopware6/Converter/ProductMainVariantRelationConverter.php` wurde entfernt, da sie bereits mit der Entität `Product` in SW6 migriert wurde.

# 6.0.1
- MIG-887 - Verbesserung der Performance des Endpunktes, welcher alle Daten erfasst, die anschließend geschrieben werden sollen

# 6.0.0
- MIG-879 - Fehler beim Migrieren von steuerfreien Bestellungen aus SW5 behoben
- MIG-859 - [BREAKING] Methode `pushMapping` vom `Migration/Mapping/MappingServiceInterface.php` wurde entfernt und alle Implementierung angepasst. Nutze stattdessen `getOrCreateMapping`.
- MIG-859 - [BREAKING] Methode `pushValueMapping` vom `Migration/Mapping/MappingServiceInterface.php` wurde entfernt und alle Implementierung angepasst. Nutze stattdessen `getOrCreateMapping`.
- MIG-859 - [BREAKING] Methode `bulkDeleteMapping` vom `Migration/Mapping/MappingServiceInterface.php` wurde entfernt und alle Implementierung angepasst.
- MIG-859 - [BREAKING] Default Parameter `$entityValue` zu `getOrCreateMapping` vom `Migration/Mapping/MappingServiceInterface.php` hinzugefügt und alle Implementierungen angepasst. Passe Implementierungen an.
- MIG-859 - [BREAKING] Default Parameter `$entityValue` zu `createMapping` vom `Migration/Mapping/MappingServiceInterface.php` hinzugefügt und alle Implementierungen angepasst. Passe Implementierungen an.

# 5.1.2
- MIG-871 - Fehler beim Migrieren von steuerfreien Bestellungen behoben
- MIG-869 - Zusätzliche Informationen zur SW6 Profilseite hinzugefügt

# 5.1.1
- MIG-870 - Fehler bei der Produktmigration behoben

# 5.1.0
- NEXT-22545 - Migration von digitalen Produkten hinzugefügt

# 5.0.0
- MIG-847 - Kompatibilität für Shopware 6.5
- MIG-827 - Migration von Versandarten mit Zeitkonfiguration behoben
- MIG-829 - Fortschrittsbalken, der in einem bestimmten Ansichtsfenster falsch angezeigt wird, ist behoben
- NTR - Migration von Custom Products

# 4.2.5
- MIG-293 - Performance der Migration von Bestelldokumenten verbessert

# 4.2.4
- MIG-246 - Zurücksetzen der Verbindungseinstellungen korrigiert
- MIG-262 - Kundenmigrationen werden nicht mehr nach E-Mail-Adressen gruppiert
- MIG-279 - Vorabzuordnung korrigiert

# 4.2.3
- MIG-269 - Fehlende Daten in der Bestellung korrigiert, für Systeme auf denen MySQL-Trigger nicht funktionieren

# 4.2.2
- MIG-263 - Behebt ein Problem, bei dem Bestelladressen fälschlicherweise identisch oder vertauscht sein konnten
- MIG-260 - Behebt ein Problem bei der Migration von Verkaufskanälen mit dem Shopware-Sprachpacket

# 4.2.1
- MIG-252 - Behebt ein Problem auf der Profil-Installationsseite

# 4.2.0
- MIG-100 - Migrationsprofil für Shopware 5.7 hinzugefügt
- MIG-243 - Behebt ein Problem bei der Migration von Bestellungen mit dem Shopware-6-Profil
- MIG-247 - Behebt ein Problem mit Schreibschutz-Fehlern bei Shopware-6-Migrationen

# 4.1.1
- MIG-206 - Migration des Erstelldatums der Produkte
- MIG-237 - Behebt ein Problem bei der Migration der UStId
- MIG-240 - Optimiert den Customer Indexer zum Zählen der Bestellungen

# 4.1.0
- MIG-126 - Migrationsprofil für Shopware 6 hinzugefügt
- MIG-221 - Behebt die Migration von Rechnungen
- MIG-224 - Optimierung der Attributwertmigration aus SW5
- MIG-233 - Migration der Merkzettel / Wunschlisten hinzugefügt

# 4.0.0
- MIG-203 - Kompatibilität für Shopware 6.4
- MIG-220 - Optimiert die Migration von Produkt-Vorschaubildern

# 3.0.2
- MIG-218 - Verhindert den Abbruch der Migration, wenn ein Kunde eine ungültige E-Mail-Adresse hat
- MIG-219 - Behebt ein Problem bei der Migration von Übersetzungen der Freitextfelder

# 3.0.1
- MIG-213 - Verhindert den Abbruch der Migration beim Schreiben von fehlerhaften Produktvarianten
- MIG-214 - Verbessert die Fortschrittsdarstellung in der CLI
- MIG-216 - Behebt ein Problem mit Kunden-E-Mail-Adressen, die länger als 64 Zeichen sind

# 3.0.0
- MIG-125 - Verbessert die Migration von Bestellungen, sodass die Anzahl der Kundenbestellungen indexiert wird
- MIG-181 - Migration der Hauptvarianten-Informationen ermöglichen für Shopware 5.4 / 5.6
- MIG-182 - Migration der Gutscheine hinzugefügt
- MIG-187 - Verbessert die Migration von Medien ohne Dateinamen
- MIG-188 - Verbessert die Stabilität des Mediendownloads
- MIG-189 - Korrigiert die Migration der Produkt-Bestellpositionen
- MIG-194 - Optimiert die Migration von Verkaufskanälen
- MIG-196 - Verbessert die Erweiterbarkeit des Plugins

# 2.2.2
- MIG-110 - Verbessert die Migration der Medien
- MIG-114 - Migration der Hauptvarianten-Information ermöglichen
- MIG-118 - Korrigiert die Migration der Kredit-Bestellpositionen
- MIG-120 - Behebt ein Problem beim Laden des Premappings
- MIG-162 - Behebt ein Problem bei der Migration von Produkten mit leeren Freitextfeldern 
- MIG-167 - Behebt ein Problem bei der Migration von Freitextfeldwerten
- MIG-168 - Optimiertes Request Handling

# 2.2.1
- MIG-105 - Warnung hinzufügen, wenn sich die Standardsprachen unterscheiden
- MIG-107 - Verbessert die Migration von Versandarten
- MIG-109 - Verbessern der Migration der Bestellungen

# 2.2.0
- MIG-75 - Verbessert das Übernehmen einer Migration
- MIG-106 - Verbessert die Migration von Bestellpositionen
- MIG-124 - ACL-Privilegien hinzugefügt

# 2.1.2
- MIG-85 - Berücksichtigt die Kundenkommentare bei Bestellungen
- MIG-90 - Behebt einen Fehler bei der Variantenmigration
- MIG-92 - Behebt ein Problem beim Download der Historie
- MIG-98 - Behebt ein Problem wenn die Premapping-Datensätze keine Beschreibung enthalten
- MIG-103 - Verbessert die Migration von Variantenübersetzungen

# 2.1.1
- MIG-39 - Optimierung im Basiskonverter
- MIG-72 - Berücksichtigt korrekten Kategorietypen wenn es einen externen Link gibt
- MIG-73 - Berücksichtigt die Attributübersetzungen bei Variantenartikeln von SW5
- MIG-74 - Optimierte Attributmigration von Shopware 5

# 2.1.0
- MIG-13 - Migration von Produktbewertungen ohne Kunden ermöglichen
- MIG-28 - Optmierte Neugenerierung des Containers bei Aktivierung und Deaktivierung

# 2.0.0
- MIG-3 - Korrigiert ein Problem bei der Migration von Bestelldokumenten
- MIG-5 - Verbessertes Laden von Snippets der DataSets
- MIG-14 - Löschen des Protokolls eines Durchlaufes ist jetzt in der Historie möglich
- MIG-22 - Behebt ein Problem bei der Migration von Bestellungen, das durch abgebrochene Bestellungen verursacht wurde
- MIG-23 - Korrigiert ein Problem beim Herunterladen der Logdatei

# 1.7.1
- MIG-6 - Neue Funktion zum Speichern des Premappings ohne das Starten einer Migration für den CLI-Support

# 1.7.0
- PT-11910 - Migration von CrossSelling hinzugefügt
- PT-11922 - Kompatibilität für Shopware 6.3
- PT-11955 - Behebt ein Problem beim Speichern der Medien

# 1.6.0
- PT-11692 - Neue Funktion zum Abschluss des Migrationsprozesses die nicht mehr benötigte Daten entfernt
- PT-11864 - Verarbeitung der Medien verbessert
- PT-11942 - Verbessern der Migration von Produkt-Übersetzungen

# 1.5.3
- PT-11845 - Verbessern der Migration von Kunden
- PT-11855 - Verbessern der Migration von Medien

# 1.5.2
- PT-11788 - Migration von Pseudo-Preisen aus SW5 integriert

# 1.5.1
- PT-11819 - Optimiert die Produkt-Varianten Migration für das Shopware 5 Profil

# 1.5.0
- PT-11692 - Dashboard-Karte des Migrations-Assistenten ist nun eigene Komponente
- PT-11747 - Behebt ein Problem, wenn SEO urls keine Typ Id haben
- PT-11764 - Werte im Datencheck werden jetzt sortiert ausgegeben

# 1.4.2
- PT-11689 - Fügt einen Umfrage-Link zur Qualität des Produktes hinzu

# 1.4.1
- NTR - Behebt ein Problem beim Injekten des Cache-Services

# 1.4.0
- PT-11497 - Behebt ein Problem mit falschem Verbindungsstatus
- PT-11601 - Shopware 6.2 Kompatibilität
- PT-11462 - Behebt ein Problem bei der Migration von Bestellungen

# 1.3.0
- PT-11586 - Optimierte Produktmigration von Shopware 5
- PT-11617 - Behebt ein Problem mit zu vielen offenen Datenbankverbindungen per CLI

# 1.2.2
- NTR - Behebt ein Problem mit dem Layout beim Zurücksetzen der Checksummen

# 1.2.1
- NTR - Behebt ein Problem bei der Migration der Medienordner-Einstellungen aus Shopware 5

# 1.2.0
- PT-11450 - Es ist jetzt möglich die Prüfsummen über einen Button im Dropdown für die Verbindungsverwaltung zurückzusetzen.
- PT-11525 - Optimiert den Migrationsprozess für Medien

# 1.1.0
- PT-10832 - Verhindern eines unerwünschten Zustands beim Anlegen neuer Verbindungen
- PT-10983 - Technisches Konzept der Oberflächen auf Vuex geändert
- PT-11331 - Zeitüberschreitung bei Anfragen für größere Migrationen behoben
- PT-11394 - Behebt ein Problem mit der Produktsichtbarkeit in verschachtelten Shop-Strukturen
- PT-11400 - Migrationsfehler bei falsch definierten Thumbnail Größen behoben

# 1.0.3
- PT-11329 - Metadaten für Produkte und Kategorien migrieren
- NTR - Behebt ein Problem mit Differenz der Datenbank Feldlänge zwischen SW5 und SW6

# 1.0.2
- NTR - Verbessert die Performanz beim Berechnen des Fortschritts

# 1.0.1
- NTR - Behebt ein Problem mit den Checksummen der Delta-Migration beim Abbruch der Migration

# 1.0.0
- PT-11113 - Anpassung der Plugin icons
- PT-11111 - Anpassung des Profilicons für externe Profile
- NTR - Behebt ein Problem nach dem Installieren von extern Profilen 
- NTR - Snippet renaming
- PT-11252 - Nummernkreise werden jetzt in den Basisdaten migriert

# 0.40.0
- PT-11014- Magento-Onboarding zum Migration-Wizard hinzufügen
- PT-11016 - Anpassen der ersten Migration-Wizard-Seite
- PT-11017 - Hinzufügen einer Migration-Card zum Dashboard
- PT-11033 - Fixen der Migration der Kategorien
- PT-11020 - Implementieren von measurement calls
- NTR - Anpassung an die neue Plugin-Struktur
- NTR - Stabilisieren des Migration-Writers
- NTR - Überarbeitung der Datasets
- NTR - Überarbeitung der Abfrage der Anzahlen per API
- NTR - Überarbeitung der Reader-Interfaces und Klassen
- NTR - Fixen des Produktcovers bei nur einem Produktbild

# 0.30.1
- PT-10925 - Indexing-Controller nach jeder Migration aufrufen
- PT-10948 - Doppelte Dokumententypen verhindern
- PT-10946 - Migrieren der Kundensprache

# 0.30.0
- PT-10629 - Erhöhung der Testabdeckung
- PT-10761 - Neues Frontend data handling
- PT-10783 - Migration von Attributen ohne Label optimiert
- PT-10797 - Behebt ein Problem bei der Produktmigration (Sichtbarkeit)
- NTR - Partielles Indexing über die Message Queue implementiert
- PT-10800 - Behebt ein Problem mit dem Anlegen der Mappings
- PT-10818 - Behebt ein Problem bei der Attributmigration
- PT-10819 - Behebt ein Problem bei der Migration von Newsletter-Empfängern
- PT-10835 - Behebt ein Problem bei der Migration von versandkostenfreien Produkten
- PT-10844 - Migration der Lieferzeit von Produkten
- PT-10769 - Behebt ein Problem im Logging
- PT-10846 - Migration von Produktbewertungen
- PT-10847 - Behebt ein Problem bei der Migration der Shopstruktur von SW5
- NTR - Behebt ein Problem beim Premapping der Bestellstati
- PT-10793 - Nutzen einer Daten Checksumme für wiederholte Migration (Deltas)
- PT-10861 - Migration von Seo Urls
- PT-10718 - Entfernen von nicht behandelten Media Einträgen
- PT-10875 - Cleanup der nicht geschriebenen Migrationsdaten wenn ein neuer Run startet

# 0.20.0
- Refaktorierung von imports zu global object
- Refaktorierung von abgelaufenen data handling Importen

# 0.10.1
- Standard-Theme zu den Verkaufskanälen hinzugefügt
- Indexierung nach der Migration verbessert

# 0.10.0
- Shopware 5.4 & Shopware 5.6 Profile implementiert
- Converter- und Reader-Struktur überarbeitet

# 0.9.0
- Erste Version des Shopware Migrationassistent für Shopware 6
