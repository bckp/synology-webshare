# synology-webshare
Plugin for Download Station to download public Webshare links. An account is used only when Webshare requires one.

# install
Download prepacked webshare.host file (raw download or as complete repo in zip).

Login to Your Synology (http://YOUR_SYNOLOGY_IP:5000/webman/index.cgi)
Open Download Station > Settings > File hosting
Click Add and locate host file

To use an account, select **Webshare.cz** in the File hosting list, click
**Edit**, and enter your Webshare username and password. Credentials are only
needed for files that are not available publicly.

# tests
Run deterministic tests without a Webshare account or an Internet connection:

```sh
php tests.php
```

# česky
Neoficiální doplněk ke stahování pouze ze serveru webshare.cz

# instalace
Stáhněte webshare.host soubor.

Přihlaste se do Synology (http://IP_VAŠEHO_SYNOLOGY:5000/webman/index.cgi)
Otevřete Download Station > Nastavení > Hostování souborů
Klikněte na Přidat a vyberte host soubor.

Chcete-li použít účet, v seznamu Hostování souborů vyberte **Webshare.cz**,
klikněte na **Upravit** a zadejte uživatelské jméno a heslo k Webshare.
Přihlašovací údaje jsou potřeba pouze pro soubory, které nejsou veřejně dostupné.
