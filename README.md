## AvhGlitchtip

**Description:** Integrates GlitchTip (Sentry-compatible) as an error tracking backend into Shopware 6.6 and 6.7. Errors are automatically captured and sent to a self-hosted GlitchTip instance.

**Audience:** Developers, Project Managers

### Setup

1. **Prerequisites:**
   - Shopware 6.6 or 6.7
   - PHP 8.2+
   - Access to a GlitchTip instance with a valid DSN

2. **Installation:**

```bash
composer require avh/glitchtip:
php bin/console plugin:refresh
php bin/console plugin:install --activate AvhGlitchtip
php bin/console cache:clear
```

### Configuration

The plugin supports two configuration paths. Admin settings always take precedence.

| Priority | Source | Description |
|---|---|---|
| 1 (highest) | **Admin Panel** | Settings > Extensions > AvhGlitchtip |
| 2 | **Environment Variable** | `GLITCHTIP_DSN` in `.env` or `.env.local` |
| 3 | **Defaults** | Environment and release are auto-detected |

#### Option A: Environment Variable

Add to `.env.local`:

```dotenv
GLITCHTIP_DSN=https://key@glitchtip.example.com/1
```

The plugin picks up the DSN automatically. No further configuration required.

#### Option B: Admin Panel

1. Open **Settings > Extensions > AvhGlitchtip**.
2. Enable **Enable Error Tracking**.
3. Enter the **DSN** from your GlitchTip project.
4. Optional: adjust environment, release and sampling rates.

**Note:** When the plugin is enabled via Admin (`enabled = true`), the DSN configured there fully overrides the environment variable.

#### Settings Overview

**GlitchTip Connection:**

| Field | Type | Default | Description |
|---|---|---|---|
| Enable Error Tracking | Bool | `false` | Activates admin-driven configuration |
| DSN | Password | — | DSN from your GlitchTip project |
| Environment | Text | *auto* | Overrides `APP_ENV` |
| Release | Text | *auto* | Overrides `shopware@{version}` |

**Sampling:**

| Field | Type | Default | Description |
|---|---|---|---|
| Error Sample Rate | Float | `1.0` | Proportion of errors captured (0.0–1.0) |
| Traces Sample Rate | Float | `0.0` | Performance traces (0.0–1.0) |
| Send PII | Bool | `false` | Send personally identifiable information (consider GDPR) |

### Structure

```
AvhGlitchtip/
├── composer.json
└── src/
    ├── AvhGlitchtip.php                  # Plugin class, registers SentryBundle
    ├── Subscriber/
    │   └── SentryConfigSubscriber.php     # Runtime configuration from admin settings
    └── Resources/config/
        ├── services.xml                   # DI container
        ├── config.xml                     # Admin panel fields
        └── packages/
            └── sentry.yaml               # Static Sentry bundle configuration
```

### Important Notes

- **GDPR:** `Send PII` is disabled by default. Only enable after consulting your data protection officer.
- **Filtered Exceptions:** The plugin suppresses common bot and Shopware errors (404, not logged in, bad credentials, product not found) to reduce noise in GlitchTip.
- **Performance Tracing** is disabled by default (`0.0`). Enabling it significantly increases data volume in GlitchTip — increase gradually (e.g. `0.1`).
- **Deactivation:** Setting `enabled` to `false` in the Admin disables Sentry completely — even if a `GLITCHTIP_DSN` environment variable exists.
