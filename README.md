<div align="center">

# Webshare.cz for Synology Download Station

An unofficial Download Station plugin for downloading files from Webshare.cz.

[![CI](https://github.com/bckp/synology-webshare/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/bckp/synology-webshare/actions/workflows/ci.yml)
[![Latest release](https://img.shields.io/github/v/release/bckp/synology-webshare?display_name=tag)](https://github.com/bckp/synology-webshare/releases/latest)
[![PHP compatibility](https://img.shields.io/badge/PHP-5.6%20%E2%80%93%208.5-777bb4)](https://github.com/bckp/synology-webshare/actions/workflows/ci.yml)

[Installation](#installation) · [Development](#development) · [Česky](#česky)

</div>

The plugin resolves Webshare share links for Download Station. Public files work
without an account; Webshare credentials are used only when a file requires
authentication.

## Compatibility

| Component | Supported version |
| --- | --- |
| Synology Download Station | 3.9.5 |
| Webshare.cz API | 2026 API |
| PHP runtime | 5.6, 7.4, 8.2 and 8.5 tested in CI |

## Installation

1. Download the latest [`webshare.host`](https://github.com/bckp/synology-webshare/releases/latest/download/webshare.host) release asset.
2. In Synology DSM, open **Download Station → Settings → File Hosting**.
3. Select **Add**, choose the downloaded file and confirm the installation.

### Optional account

In **File Hosting**, select **Webshare.cz**, choose **Edit** and enter your
Webshare username and password. Credentials are not required for public files.

## Development

The test suite is deterministic and does not need an account or an Internet
connection:

```fish
php tests/unit.php
```

An optional live test accepts credentials only through environment variables,
keeping the password out of command arguments and shell history:

```fish
read --silent --prompt-str='Webshare password: ' webshare_password
begin
    set -lx WEBSHARE_USERNAME 'user@example.com'
    set -lx WEBSHARE_PASSWORD $webshare_password
    php test.php 'https://webshare.cz/#/file/example/'
end
set -e webshare_password
```

Build the same reproducible archive that CI and the release workflow produce:

```fish
mkdir -p dist
git archive --format=tar.gz --output=dist/webshare.host HEAD INFO webshare.php
```

## Releases

Each release tag must exactly match `INFO.version`. The release workflow tests
the plugin, verifies the archive contents and reproducibility, and publishes
`webshare.host` together with `SHA256SUMS`.

## Česky

Neoficiální doplněk pro Synology Download Station ke stahování souborů ze
serveru Webshare.cz. Veřejné soubory fungují bez účtu; přihlašovací údaje se
použijí pouze pro soubory, které je vyžadují.

### Instalace

1. Stáhněte nejnovější [`webshare.host`](https://github.com/bckp/synology-webshare/releases/latest/download/webshare.host) z vydané verze.
2. V Synology DSM otevřete **Download Station → Nastavení → Hostování souborů**.
3. Zvolte **Přidat**, vyberte stažený soubor a potvrďte instalaci.

### Volitelný účet

V seznamu **Hostování souborů** vyberte **Webshare.cz**, zvolte **Upravit** a
zadejte uživatelské jméno a heslo k Webshare. Pro veřejně dostupné soubory účet
není potřeba.
