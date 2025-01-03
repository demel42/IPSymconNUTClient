# IPSymconNUT

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Abfrage der Daten einer USV via Network-UPS-Tool (**NUT**)

## 2. Voraussetzungen

 - IP-Symcon ab Version 6.0
 - eine USV mit eingerichtetem NUT-Server, z.B. eine an einer Synology Diskstation angeschlossene USV

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ öffnen.

Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

![Store](docs/de/img/store_icon.png?raw=true "open store")

Im Suchfeld nun _NUT-Client_ eingeben, das Modul auswählen und auf _Installieren_ drücken.

#### Alternatives Installieren über Modules Instanz (IP-Symcon < 5.1)

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ aufrufen.

Anschließend den Objektbaum _öffnen_.

![Objektbaum](docs/de/img/objektbaum.png?raw=true "Objektbaum")

Die Instanz _Modules_ unterhalb von Kerninstanzen im Objektbaum von IP-Symcon mit einem Doppelklick öffnen und das  _Plus_ Zeichen drücken.

![Modules](docs/de/img/Modules.png?raw=true "Modules")

![Plus](docs/de/img/plus.png?raw=true "Plus")

![ModulURL](docs/de/img/add_module.png?raw=true "Add Module")

Im Feld die folgende URL eintragen und mit _OK_ bestätigen:

```
https://github.com/demel42/IPSymconNUT.git
```

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_.

### b. Einrichtung des Geräte-Moduls

In IP-Symcon nun unterhalb des Wurzelverzeichnisses die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _- kein Hersteller -_ und als Gerät _NUT-Client_ auswählen.

In der Konfigurationsseite wird als _Hostname_ der des NUT-Servers eingetragen. Die Portnummer bleibt im Normalfall auf _3493_. Die Authentifikation ist nur auszufüllen, wenn das vom NUT-Server
vorgegeben ist.

Als _USV-Identifikation_ ist die von NUT-Server vorgesehenen Identifikation der USV anzugeben.
Wenn man die nicht weis, wie folgt vorgehen:
- die Konfiguration mit eintragenen _Hostnamen_ speichern
- die Schaltfläche _Zugang prüfen_ auslösen
- dann werden die verfügbaren Identifikationen ausgegeben.

In dem Panel _Variablen_ gibt es zwei Tabellen
1. _vordefinierte Datenpunkte_<br>
hier finden sich die wichtigsten Datenpunkte; für jeder Datenpunkt wird vom Modul der richtige Variablentyp und Variablenprofil bei der Anlage der Variablen sowie eine passende Bezeichnung gewählt.

2. _zusätzliche Datenpunkte_<br>
da es in dem Standard sehr viele Datenpunkte gibt und diese auch sogar variabel sind, können hierüber zusätzliche Datenpunkte angegeben werden. An dieser Stelle wird neben der Bezeichung auch der Varioablentyp definiert. Bezeichnung und Variablenprofil muss manuell dann im Objektbaum angepasst werden.

Eine Übersicht der verfügbaren Variablen ist über die Schaltfläche _Beschreibung der Varіablen_ erreichbar (siehe [hier](https://networkupstools.org/docs/user-manual.chunked/apcs01.html#_examples)). Die von dem NUT-Server bereitgestellten Variablen kann man sich mittels _Anzeige der Variablen_ anschauen.

Um Daten vor dem Speicherung zu können gibt es ein optionales Script, ein Muster findet sich in _docs/convert_script.php_.

Wichtig: nach der Defintion der Variablen sollte man mit der Schaltfläche _Zugang prüfen_ überprüfen, ob alle gewünschten Variablen auch geliefert werden.


### c. Anpassung des NUT-Servers

Hier muss ggfs der Zugriff vom IPS erlaubt werden, am Beispiel der Diskstation:
- _Systemsteuerung_ auswählen
- dann _Hardware & Energie_ öffnen
- dort den Reiter _USV_ wählen
- die Schaltfläche _Zugelassene DiskStation-Geräte_ betätigen
- in dem Dialog dann die IP des IPS-Servers eintragen

## 4. Funktionsreferenz

Mit Hilfe der Kommandos können die NUT-Funktionalitäten direkt genutzt werden, die Beschreibung der Kommandos siehe [hier](https://networkupstools.org/docs/developer-guide.chunked/ar01s09.html).

`NUTC_ExecuteList(int $InstanzID, string $subcmd, string $varname);`
führt alle Unterkommandos von Typ _LIST_ aus.

`NUTC_ExecuteGet(int $InstanzID, string $subcmd, string $varname);`
führt alle Unterkommandos von Typ _GET_ aus.

`NUTC_ExecuteSet(int $InstanzID, string $varname, string $value);`
führt alle Unterkommandos von Typ _GET_ aus.

`NUTC_ExecuteCmd(int $InstanzID, string $cmdname);`
führt alle Unterkommandos von Typ _INSTCMD_ aus.

`NUTC_ExecuteHelp(int $InstanzID);`
führt alle Unterkommandos von Typ _HELP_ aus.

`NUTC_ExecuteVersion(int $InstanzID);`
führt alle Unterkommandos von Typ _VER_ aus.

`NUTC_ExecuteLogin(int $InstanzID);`
führt alle Unterkommandos von Typ _LOGIN_ aus.

`NUTC_ExecuteLogout(int $InstanzID);`
führt alle Unterkommandos von Typ _LOGOUT_ aus.

Die Nutzung der FUnktionen hängt aber von dem NUT-Server ab und setzt auch Kenntniss der [NUT-Dokumentation](https://networkupstools.org/) voraus!

## 5. Konfiguration

#### Properties

| Eigenschaft                           | Typ      | Standardwert | Beschreibung |
| :------------------------------------ | :------  | :----------- | :----------- |
| Hostname                              | string   |              | Hostname des NUT-Servers |
| Port                                  | integer  | 3493         | Portnummer des NUT-Servers |
| USV-ID                                | string   |              | Bezeichnung der USV innterhalb des NUT-Servers |
| Aktualisierungsintervall              | integer  | 30           | Häufigkeit des Datenabrufs |
|                                       |          |              | |
| Benutzer                              | string   |              | Benutzername, sofern der NUT-Server so abgesichert ist |
| Passwort                              | string   |              | Passwort zu dem Benutzernamen |
|                                       |          |              | |
| vordefinierte Datenpunkte             | Tabelle  |              | siehe oben |
|  ... verwenden                        | bool     |              | Datenpunkt benutzen |
|                                       |          |              | |
| zusätzliche Datenpunkte               | Tabelle  |              | siehe oben |
|  ... Datenpunkt                       | string   |              | der Datenpunkt dieser Variable |
|  ... Variabentyp                      | integer  |              | der Datentyp, den die Variable haben soll |
|                                       |          |              | |
| Werte konvertieren                    | integer  |              | ein Script um enpfangene Daten anzupassen, bevor gespeichert wird |
|                                       |          |              | |

#### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Integer<br>
NUTC.Frequency,
NUTC.Percent,
NUTC.sec,
NUTC.sec,
NUTC.Status

* Float<br>
NUTC.Capacity,
NUTC.Current,
NUTC.Power,
NUTC.Temperature,
NUTC.Voltage

## 6. Anhang

GUIDs
- Modul: `{6C3F5B14-D005-EDF2-1877-0268635FDB26}`
- Instanzen:
  - NUTClient: `{D7648DB1-3D1D-F0A8-BA2D-01EA50AF6F4C}`

## 7. Versions-Historie

- 1.11 @ 02.01.2025 14:28
  - interne Änderung
  - update submodule CommonStubs

- 1.10 @ 06.02.2024 09:46
  - Verbesserung: Angleichung interner Bibliotheken anlässlich IPS 7
  - update submodule CommonStubs

- 1.9 @ 03.11.2023 11:06
  - Neu: Ermittlung von Speicherbedarf und Laufzeit (aktuell und für 31 Tage) und Anzeige im Panel "Information"
  - update submodule CommonStubs

- 1.8 @ 30.06.2023 09:28
  - Fix: bessere Absicherung ggü. fehlenden Daten
  - Vorbereitung auf IPS 7 / PHP 8.2
  - update submodule CommonStubs
    - Absicherung bei Zugriff auf Objekte und Inhalte

- 1.7.1 @ 07.10.2022 13:59
  - update submodule CommonStubs
    Fix: Update-Prüfung wieder funktionsfähig

- 1.7 @ 07.07.2022 10:04
  - Verbesserung: IPS-Status wird nur noch gesetzt, wenn er sich ändert
  - README.md korrigiert

- 1.6.1 @ 15.06.2022 11:14
  - Fix: undefinierte Variable 'msec'
    nur bei unvollständiger Konfiguration (fehlgeschlagenem 'CheckConfiguration')
  - Fix: fehlende Übersetzung (Status "CAL")
  - Fix: Angabe der Kompatibilität auf 6.2 korrigiert

- 1.6 @ 11.06.2022 18:33
  - Fix: Variablenprofile wurden nicht mehr angelegt
  - update submodule CommonStubs
    Fix: Ausgabe des nächsten Timer-Zeitpunkts
  - einige Funktionen (GetFormElements, GetFormActions) waren fehlerhafterweise "protected" und nicht "private"
  - interne Funktionen sind nun entweder private oder nur noch via IPS_RequestAction() erreichbar

- 1.5.4 @ 17.05.2022 15:38
  - update submodule CommonStubs
    Fix: Absicherung gegen fehlende Objekte

- 1.5.3 @ 10.05.2022 15:06
  - update submodule CommonStubs
  - SetLocation() -> GetConfiguratorLocation()
  - weitere Absicherung ungültiger ID's

- 1.5.2 @ 06.05.2022 11:39
  - IPS-Version ist nun minimal 6.0
  - Überlagerung von Translate und Aufteilung von locale.json in 3 translation.json (Modul, libs und CommonStubs)
  - diverse interne Änderungen
  - auto. re-aktivieren, wenn die Verbinung vom NUT-Server verloren ging (IS_NOSERVICE ist nun STATUS_RETRYABLE)

- 1.5.1 @ 01.05.2022 17:31
  - Fix zu 1.5 (Error-Codes doppelt verwendet)
  - TestAccess() setzt ggfs. Status wieder auf "aktiv"

- 1.5 @ 26.04.2022 16:34
  - Anpassungen an IPS 6.2 (Prüfung auf ungültige ID's)
  - IPS-Version ist nun minimal 6.0
  - Anzeige der Referenzen der Instanz incl. Statusvariablen und Instanz-Timer
  - Implememtierung einer Update-Logik
  - diverse interne Änderungen

- 1.4 @ 14.07.2021 17:39
  - automatische Wiederholung, wenn die Verbindung zum NUTServer nicht funktioniert
  - Schalter "Instanz ist deaktiviert" umbenannt in "Instanz deaktivieren"

- 1.3 @ 07.03.2021 14:53
  - PHP_CS_FIXER_IGNORE_ENV=1 in github/workflows/style.yml eingefügt
  - Reihenfolgde der Auswertung der Stati nach Relevanz korrigiert, zweitrangige Stati werden als im Feld 'Statuszusatz' ausgegeben
  - Test des Profils 'NUTC.Status' angepasst - bitte vor dem Update Profil löschen

- 1.2 @ 26.07.2020 14:49
  - LICENSE.md hinzugefügt
  - intere Funktionen sind nun "private"
  - define's durch statische Klassen-Variablen ersetzt
  - lokale Funktionen aus common.php in locale.php verlagert

- 1.1 @ 02.02.2020 17:52
  - verbesserte Behandlung wenn der NUT-server nicht erreichbar ist

- 1.0 @ 08.01.2020 18:09
  - Initiale Version
