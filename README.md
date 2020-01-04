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
Die Konfiguration mit eintrågenen _Hostnamen_ speichern, dann die Aktion _Zugang prüfen_ auslösen. Hier werden dann die verfügbaren Identifikationen nausgegeben.

In der Tabelle _Variablen_ kann man auswählen, welche Variablen man übernehmen möchte, die wichtigsten Variablen stehen hier zur Verfügung…


### c. Anpassung des NUT-Servers

## 4. Funktionsreferenz

## 5. Konfiguration

#### Properties

| Eigenschaft                           | Typ      | Standardwert | Beschreibung |
| :------------------------------------ | :------  | :----------- | :----------- |
|                                       |          |              | |

#### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Boolean<br>

* Integer<br>

* Float<br>

* String<br>

## 6. Anhang

GUIDs
- Modul: `{6C3F5B14-D005-EDF2-1877-0268635FDB26}`
- Instanzen:
  - NUTClient: `{D7648DB1-3D1D-F0A8-BA2D-01EA50AF6F4C}`

## 7. Versions-Historie

- 1.0 @ xx.xx.xxxx xx:xx
  - Initiale Version
