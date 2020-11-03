# REPLACE-GLOBAL-WITH-NEXT-VERSION
- MIG-107 - Verbessert die Migration von Versandarten

# 2.2.0
- MIG-75 - Verbessert das Übernehmen einer Migration
- MIG-105 - Warnung hinzufügen, wenn sich die Standardsprachen unterscheiden
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
