# Wartungsmodus

Zur Verwendung dieses Moduls als Privatperson, Einrichter oder Integrator wenden Sie sich bitte zunächst an den Autor.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.


### Inhaltsverzeichnis

1. [Modulbeschreibung](#1-modulbeschreibung)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Schaubild](#3-schaubild)
4. [Externe Aktion](#5-externe-aktion)
5. [PHP-Befehlsreferenz](#5-php-befehlsreferenz)
    1. [Wartungsmodus schalten](#51-Wartungsmodus-schalten)


### 1. Modulbeschreibung

Dieses Modul schaltet einen Wartungsmodus in [IP-Symcon](https://www.symcon.de).

### 2. Voraussetzungen

- IP-Symcon ab Version 6.1

### 3. Schaubild

```
                       +-----------------------+
                       | Wartungsmodus (Modul) |<------------- externe Aktion
                       |                       |
                       | Wartungsmodus Aus/An  |
                       |                       |
                       +-----------+-----------+
                                   |  
                                   |  
                                   |                          
                                   |                    
                                   v                    
                              +----------+               
                              | Variable |
                              +----------+
```

### 4. Externe Aktion

Das Modul Wartungsmodus kann über eine externe Aktion geschaltet werden.  
Nachfolgendes Beispiel schaltet den Wartungsmodus an.

> WM_ToggleMaintenanceMode(12345, true);

### 5. PHP-Befehlsreferenz

#### 5.1 Wartungsmodus schalten

```
boolean WM_ToggleMaintenanceMode(integer INSTANCE_ID, boolean STATE);
```

Konnte der Befehl erfolgreich ausgeführt werden, liefert er als Ergebnis **TRUE**, andernfalls **FALSE**.

| Parameter     | Wert  | Bezeichnung    |
|---------------|-------|----------------|
| `INSTANCE_ID` |       | ID der Instanz |
| `STATE`       | false | Aus            |
|               | true  | An             |

Beispiel:
> WM_ToggleMaintenanceMode(12345, false);