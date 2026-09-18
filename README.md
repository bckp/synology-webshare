# synology-webshare

Plugin for Synology Download Station that downloads public Webshare links. An
account is used only when Webshare requires one.

Updated for Webshare.cz's 2026 APIs and Synology Download Station 3.9.5.

## Installation

1. Download the latest [`webshare.host`](https://github.com/bckp/synology-webshare/releases/latest/download/webshare.host) release asset.
2. Sign in to Synology DSM and open **Download Station > Settings > File Hosting**.
3. Click **Add** and select the downloaded host file.

To use an account, select **Webshare.cz** in the File Hosting list, click
**Edit**, and enter your Webshare username and password. Credentials are only
needed for files that are not available publicly.

## Tests

Run the deterministic tests without a Webshare account or an Internet
connection:

```fish
php tests/unit.php
```

For an optional live account test, keep the password out of command arguments
and shell history:

```fish
read --silent --prompt-str='Webshare password: ' webshare_password
begin
    set -lx WEBSHARE_USERNAME 'user@example.com'
    set -lx WEBSHARE_PASSWORD $webshare_password
    php test.php 'https://webshare.cz/#/file/example/'
end
set -e webshare_password
```

Build the same host archive that CI and the release workflow produce:

```fish
mkdir -p dist
git archive --format=tar.gz --output=dist/webshare.host HEAD INFO webshare.php
```

Every tag must exactly match `INFO.version`. A successful tag workflow tests the
plugin, builds `webshare.host`, verifies its contents and reproducibility, and
publishes it together with `SHA256SUMS` in a GitHub Release.

## Česky

Neoficiální doplněk pro Synology Download Station ke stahování souborů ze
serveru Webshare.cz.

Aktualizováno pro API Webshare.cz z roku 2026 a Synology Download Station 3.9.5.

## Instalace

1. Stáhněte nejnovější release asset [`webshare.host`](https://github.com/bckp/synology-webshare/releases/latest/download/webshare.host).
2. Přihlaste se do Synology DSM a otevřete **Download Station > Nastavení > Hostování souborů**.
3. Klikněte na **Přidat** a vyberte stažený host soubor.

Chcete-li použít účet, v seznamu Hostování souborů vyberte **Webshare.cz**,
klikněte na **Upravit** a zadejte uživatelské jméno a heslo k Webshare.
Přihlašovací údaje jsou potřeba pouze pro soubory, které nejsou veřejně
dostupné.
