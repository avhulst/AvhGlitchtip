## AvhGlitchtip

**Kurzbeschreibung:** Integriert GlitchTip (Sentry-kompatibel) als Error-Tracking-Backend in Shopware 6.6 und 6.7. Fehler werden automatisch erfasst und an eine selbst gehostete GlitchTip-Instanz gesendet.

**Zielgruppe:** Entwickler, Projektmanager

### Setup

1. **Voraussetzungen:**
   - Shopware 6.6 oder 6.7
   - PHP 8.2+
   - Zugang zu einer GlitchTip-Instanz mit gültigem DSN

2. **Installation:**

```bash
composer require avh/glitchtip
php bin/console plugin:refresh
php bin/console plugin:install --activate AvhGlitchtip
php bin/console cache:clear
```

### Konfiguration

Das Plugin unterstützt zwei Konfigurationswege. Admin-Einstellungen haben immer Vorrang.

| Priorität | Quelle | Beschreibung |
|---|---|---|
| 1 (höchste) | **Admin-Panel** | Einstellungen > Erweiterungen > AvhGlitchtip |
| 2 | **Environment-Variable** | `GLITCHTIP_DSN` in `.env` oder `.env.local` |
| 3 | **Defaults** | Umgebung und Release werden automatisch erkannt |

#### Variante A: Environment-Variable

In `.env.local` eintragen:

```dotenv
GLITCHTIP_DSN=https://key@glitchtip.example.com/1
```

Das Plugin verwendet den DSN automatisch. Keine weitere Konfiguration nötig.

#### Variante B: Admin-Panel

1. **Einstellungen > Erweiterungen > AvhGlitchtip** öffnen.
2. **Enable Error Tracking** aktivieren.
3. **DSN** aus dem GlitchTip-Projekt eintragen.
4. Optional: Environment, Release und Sampling-Raten anpassen.

**Hinweis:** Wird das Plugin im Admin aktiviert (`enabled = true`), überschreibt der dort hinterlegte DSN die Environment-Variable vollständig.

#### Einstellungen im Überblick

**GlitchTip Connection:**

| Feld | Typ | Default | Beschreibung |
|---|---|---|---|
| Enable Error Tracking | Bool | `false` | Aktiviert die Admin-gesteuerte Konfiguration |
| DSN | Passwort | — | DSN aus dem GlitchTip-Projekt |
| Environment | Text | *auto* | Überschreibt `APP_ENV` |
| Release | Text | *auto* | Überschreibt `shopware@{version}` |

**Sampling:**

| Feld | Typ | Default | Beschreibung |
|---|---|---|---|
| Error Sample Rate | Float | `1.0` | Anteil erfasster Fehler (0.0–1.0) |
| Traces Sample Rate | Float | `0.0` | Performance-Traces (0.0–1.0) |
| Send PII | Bool | `false` | Personenbezogene Daten senden (DSGVO beachten) |

### Struktur

```
AvhGlitchtip/
├── composer.json
└── src/
    ├── AvhGlitchtip.php                  # Plugin-Klasse, registriert SentryBundle
    ├── Subscriber/
    │   └── SentryConfigSubscriber.php     # Runtime-Konfiguration aus Admin-Einstellungen
    └── Resources/config/
        ├── services.xml                   # DI-Container
        ├── config.xml                     # Admin-Panel-Felder
        └── packages/
            └── sentry.yaml               # Statische Sentry-Bundle-Konfiguration
```

### Wichtige Hinweise

- **DSGVO:** `Send PII` ist standardmäßig deaktiviert. Aktivierung nur nach Rücksprache mit dem Datenschutzbeauftragten.
- **Gefilterte Exceptions:** Das Plugin unterdrückt typische Bot- und Shopware-Fehler (404, nicht eingeloggt, ungültige Credentials, Produkt nicht gefunden), um Rauschen in GlitchTip zu vermeiden.
- **Performance-Tracing** ist standardmäßig deaktiviert (`0.0`). Aktivierung erhöht die Datenmenge in GlitchTip erheblich — schrittweise hochsetzen (z. B. `0.1`).
- **Deaktivierung:** Wird `enabled` im Admin auf `false` gesetzt, wird Sentry vollständig deaktiviert — auch wenn eine `GLITCHTIP_DSN` Environment-Variable existiert.
