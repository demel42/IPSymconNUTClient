# IPSymconNUT

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.3+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Module-Version](https://img.shields.io/badge/Modul_Version-1.0-blue.svg)
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

 - IP-Symcon ab Version 5.3
 - eine USV mit eingerichtetem NUT-Server, z.B. eine an einer Synology Diskstation angeschlossene USV

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ öffnen.

Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

![Store](docs/de/img/store_icon.png?raw=true "open store")

Im Suchfeld nun _NUT-Client eingeben, das Modul auswählen und auf _Installieren_ drücken.

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
- dann die Aktion _Zugang prüfen_ auslösen.<br>
Hier werden dann die verfügbaren Identifikationen nausgegeben.

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

`NUTC_function ExecuteList(int $InstanzID, string $subcmd, string $varname);`
führt alle Unterkommandos von Typ _LIST_ aus.

`NUTC_function ExecuteGet(int $InstanzID, string $subcmd, string $varname);`
führt alle Unterkommandos von Typ _GET_ aus.

`NUTC_function ExecuteSet(int $InstanzID, string $varname, string $value);`
führt alle Unterkommandos von Typ _GET_ aus.

`NUTC_function ExecuteCmd(int $InstanzID, string $cmdname);`
führt alle Unterkommandos von Typ _INSTCMD_ aus.

`NUTC_function ExecuteHelp(int $InstanzID);`
führt alle Unterkommandos von Typ _HELP_ aus.

`NUTC_function ExecuteVersion(int $InstanzID);`
führt alle Unterkommandos von Typ _VER_ aus.

`NUTC_function ExecuteLogin(int $InstanzID);`
führt alle Unterkommandos von Typ _LOGIN_ aus.

`NUTC_function ExecuteLogout(int $InstanzID);`
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
| Passwort                              | string   |              | Passをort zu dem Benutzernamen |
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
NUTC.Frequency, NUTC.Percent, NUTC.sec, NUTC.sec, NUTC.Status

* Float<br>
NUTC.Capacity, NUTC.Current, NUTC.Power, NUTC.Temperature, NUTC.Voltage

## 6. Anhang

GUIDs
- Modul: `{6C3F5B14-D005-EDF2-1877-0268635FDB26}`
- Instanzen:
  - NUTClient: `{D7648DB1-3D1D-F0A8-BA2D-01EA50AF6F4C}`

## 7. Versions-Historie

- 1.0 @ 08.01.2020 18:09
  - Initiale Version
