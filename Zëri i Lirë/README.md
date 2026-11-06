# Zëri i Lirë

**Anonymous corruption reporting on Tor.**
*Raportim anonim i korrupsionit në rrjetin Tor.*

Zëri i Lirë ("Free Voice") is a small, dependency-free whistleblowing site that runs as a Tor hidden service. Anyone can report corrupt people or businesses by sending text, documents or photos, without giving a name, email or account.

[English](#english) | [Shqip](#shqip)

---

## English

### Features

- Runs as a Tor onion service (v3). No clearnet exposure.
- Report form with a text box and a file upload. A report can contain text, files, or both.
- No JavaScript, no external fonts, no CDNs, no analytics, no cookies.
- Uploads are checked by real content type (JPG, PNG, WEBP, PDF, TXT), renamed to random names, and limited to 10 files of 10 MB each. Original file names are discarded.
- Strict security headers (CSP, `nosniff`, `no-referrer`) and no access logging.
- Bilingual user guide (English and Albanian, light and dark) explaining how to open the site with Tor Browser.

### How it works

1. A visitor opens the `.onion` address in Tor Browser and submits a report.
2. `submit.php` validates the input and saves it:
   - text goes to `/var/www/html/reports/<id>.txt`
   - documents and photos go to `/var/www/html/<id>-<n>.<ext>`
3. The operator reviews every report, strips metadata, and only then publishes it on a separate public page (not part of this repository yet).

### Project structure

```
faqja.html                  Report form (the site's home page)
submit.php                  Upload and text handler
how-to-open.html            Guide: how to open the site with Tor Browser (EN/SQ, light/dark)
konfigurimi-apache-tor.txt  Example Apache, PHP and Tor configuration
```

### Requirements

- Linux server (tested on Kali / Debian)
- Apache 2.4 with `mod_php` (`mpm_prefork`) and the `headers` module
- PHP 8.x with the `mbstring` and `fileinfo` extensions
- Tor

### Setup

```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-mbstring tor
sudo a2enmod headers

# Site files
sudo mkdir -p /var/www/zeri
sudo cp faqja.html submit.php /var/www/zeri/
sudo chmod 755 /var/www/zeri
sudo chmod 644 /var/www/zeri/faqja.html /var/www/zeri/submit.php

# Upload destinations (only Apache needs write access)
sudo mkdir -p /var/www/html/reports
sudo chown www-data:www-data /var/www/html /var/www/html/reports
```

Apache must listen **only** on localhost. In `/etc/apache2/ports.conf`:

```apache
Listen 127.0.0.1:8080
```

Create `/etc/apache2/sites-available/zeri.conf` (see `konfigurimi-apache-tor.txt` for the full version with security headers):

```apache
<VirtualHost 127.0.0.1:8080>
    DocumentRoot /var/www/zeri
    DirectoryIndex faqja.html

    <Directory /var/www/zeri>
        Options -Indexes -ExecCGI
        AllowOverride None
        Require all granted
    </Directory>

    ErrorLog /dev/null
    LogLevel emerg
    CustomLog /dev/null combined
</VirtualHost>
```

Keep uploaded content from executing code, and keep unreviewed text private:

```apache
# /etc/apache2/conf-available/zeri-html-safe.conf
<Directory /var/www/html>
    Options -Indexes -ExecCGI
    AllowOverride None
    <FilesMatch "\.(?i:php[0-9]?|phtml|phar|cgi|pl|py|sh)$">
        Require all denied
    </FilesMatch>
</Directory>

# /etc/apache2/conf-available/zeri-reports.conf
<Directory /var/www/html/reports>
    Require all denied
</Directory>
```

```bash
sudo a2dissite 000-default
sudo a2ensite zeri
sudo a2enconf zeri-html-safe zeri-reports
sudo apache2ctl configtest && sudo systemctl restart apache2
```

PHP limits (`php.ini` for Apache):

```ini
upload_max_filesize = 10M
post_max_size = 110M
max_file_uploads = 10
expose_php = Off
```

Tor (`/etc/tor/torrc`):

```
HiddenServiceDir /var/lib/tor/zeri/
HiddenServiceVersion 3
HiddenServicePort 80 127.0.0.1:8080
```

```bash
sudo systemctl restart tor@default
sudo cat /var/lib/tor/zeri/hostname    # your .onion address
```

### Security notes

- **Never commit or share `/var/lib/tor/zeri/`.** It holds the private key of your onion address.
- **Files in `/var/www/html` become public the moment a web server serves that folder.** Do not enable a public site on it without a review step. Treat everything uploaded as untrusted.
- **Strip metadata before publishing.** Photos and documents can reveal GPS location, author name and device:
  ```bash
  exiftool -all= -overwrite_original *.jpg
  mat2 --inplace document.pdf
  ```
- Keep the server on an encrypted disk, keep it updated, and expose no ports except Tor and (if needed) SSH.
- Turn access and error logging off in production. Turn it on only temporarily when debugging.
- You are responsible for what your server hosts. Review submissions for illegal content, personal data of innocent people, and unverified accusations before publishing anything.
- Tor protects the network path, not mistakes. Reporters should read the guide in `how-to-open.html`.

### Disclaimer

This project is independent and is not affiliated with any state institution. It is provided as is, with no guarantee of anonymity. Use it at your own risk and follow the laws that apply to you.

### License

Choose a license and add a `LICENSE` file (for example MIT).

---

## Shqip

### Çfarë është

Zëri i Lirë është një faqe e vogël, pa varësi të jashtme, për raportimin e korrupsionit, që funksionon si shërbim i fshehur i Tor. Çdokush mund të raportojë persona ose biznese të korruptuara me tekst, dokumente ose foto, pa dhënë emër, email apo llogari.

### Veçoritë

- Funksionon si shërbim onion (v3), pa dalje në internetin e hapur.
- Formular me kuti teksti dhe ngarkim skedarësh. Raporti mund të ketë tekst, skedarë, ose të dyja.
- Pa JavaScript, pa fonte të jashtme, pa CDN, pa analitikë, pa cookies.
- Skedarët kontrollohen sipas përmbajtjes reale (JPG, PNG, WEBP, PDF, TXT), riemërtohen me emra të rastësishëm, dhe kufizohen në 10 skedarë me 10 MB secili. Emrat origjinalë hidhen poshtë.
- Header-a sigurie strikte dhe pa regjistrim vizitorësh.
- Udhëzues dygjuhësh (anglisht dhe shqip, dritë dhe errësirë) për hapjen e faqes me Tor Browser.

### Si funksionon

1. Vizitori hap adresën `.onion` në Tor Browser dhe dërgon një raport.
2. `submit.php` e validon dhe e ruan:
   - teksti ruhet te `/var/www/html/reports/<id>.txt`
   - dokumentet dhe fotot ruhen te `/var/www/html/<id>-<n>.<ext>`
3. Operatori shqyrton çdo raport, heq metadatat, dhe vetëm pastaj e publikon në një faqe publike të veçantë (që nuk është ende pjesë e këtij repository).

### Instalimi

Hapat janë të njëjtë me seksionin "Setup" më sipër: instalo Apache, PHP (me `mbstring` dhe `fileinfo`) dhe Tor, kopjo `faqja.html` dhe `submit.php` te `/var/www/zeri`, bëje Apache të dëgjojë vetëm në `127.0.0.1:8080`, dhe shto `HiddenServiceDir` / `HiddenServicePort` në `torrc`. Konfigurimi i plotë ndodhet te `konfigurimi-apache-tor.txt`.

### Shënime sigurie

- **Mos e publiko kurrë `/var/lib/tor/zeri/`.** Aty ruhet çelësi privat i adresës onion.
- **Skedarët te `/var/www/html` bëhen publikë sapo një server uebi e shërben atë dosje.** Mos aktivizo faqe publike mbi të pa hapin e shqyrtimit.
- **Hiq metadatat para publikimit** me `exiftool -all=` dhe `mat2`.
- Mbaje serverin në disk të enkriptuar, të përditësuar, dhe pa porta të hapura përveç Tor (dhe SSH nëse duhet).
- Çaktivizo regjistrimin në prodhim.
- Ti je përgjegjës për çka strehon serveri yt. Shqyrto raportet për përmbajtje të paligjshme, të dhëna personale të njerëzve të pafajshëm dhe akuza të paverifikuara para publikimit.

### Mohim përgjegjësie

Projekti është i pavarur dhe nuk është i lidhur me asnjë institucion shtetëror. Ofrohet siç është, pa garanci për anonimitet. Përdore me përgjegjësinë tënde dhe respekto ligjet që të vlejnë.
