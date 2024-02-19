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
