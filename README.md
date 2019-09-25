# CardDAV contacts import for AVM FRITZ!Box

[![Build Status](https://travis-ci.org/andig/carddav2fb.svg?branch=master)](https://travis-ci.org/andig/carddav2fb) [![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BB3W3WH7GVSNW)

Purpose of the software is the (automatic) uploading of contact data from CardDAV servers as a phone book into an AVM Fritz!Box.

This is an extendeded version of https://github.com/andig/carddav2fb

## Features

* download from any number of CardDAV servers
* read from any local *.vcf files (optional)
* selection (include/exclude) by categories or groups (e.g. iCloud)
* upload of contact pictures to display them on the FRITZ!Fon (handling see below)
* automatically preserves quickDial and vanity attributes of phone numbers
  set in FRITZ!Box Web GUI. Works without config. (Hint: If you used the
  old way of configuring your CardDav server with X-FB-QUICKDIAL /X-FB-VANITY, then your old config is respected and this new automatic feature is skipped).
* automatically preserves internal numbers (e.g. if you use [Gruppenruf](https://avm.de/service/fritzbox/fritzbox-7490/wissensdatenbank/publication/show/1148_Interne-Rufgruppe-in-FRITZ-Box-einrichten-Gruppenruf/))
* if more than nine phone numbers are included, the contact will be divided into a corresponding number of phonebook entries (any existing email addresses are assigned to the first set [there is no quantity limit!])
* phone numbers are sorted by type. The order of the conversion values ('phoneTypes') determines the order in the phone book entry
* the contact's UID of the CardDAV server is added to the phonebook entry (not visible in the FRITZ! Box GUI)
* automatically preserves QuickDial and Vanity attributes of phone numbers set in FRITZ!Box Web GUI. Works without config. These data are saved separately in the internal FRITZ!Box memory under `../FRITZ/mediabox/Atrributes.csv` from loss. The legacy way of configuring your CardDav server with X-FB-QUICKDIAL/X-FB-VANITY is no longer supported.
* generates an image with keypad and designated quickdial numbers (2-9), which can be uploaded to designated handhelds (see details below)

Additonal with this version (fork):

* specify with `forcedupload` whether the phone book should be overwritten, or if phone numbers that are not included in the upload are to be saved as vcf by e-mail (see wiki for handling details).
* specify with `fritzadr` if fax numbers should be extracted from the phonebook and stored as FRITZ!Fax (fax4box) adressbook (FritzAdr.dbf)

  **Have a look in the [wiki](https://github.com/BlackSenator/carddav2fb/wiki) for further information!**

## Requirements

* PHP >7.1 (`apt-get install php php-curl php-mbstring php-xml`)
* Composer (follow the installation guide at https://getcomposer.org/download/)

## Installation

Install carddav2fb:

```console
git clone https://github.com/andig/carddav2fb.git
cd carddav2fb
composer install --no-dev
```

Install composer (see https://getcomposer.org/download/ for newer instructions):

```console
composer install --no-dev --no-suggest
```

Edit `config.example.php` and save as `config.php` or use an other name of your choice (but than keep in mind to use the -c option to define your renamed file)

## Usage

### List all commands

```console
./carddav2fb list
```

### Complete processing

```console
./carddav2fb run
```

### Get help for a command

```console
./carddav2fb run -h
```

### Upload contact pictures

Uploading can also be included in uploading phonebook:

```console
./carddav2fb run -i
```

#### Settings

* memory (USB stick) is indexed [Heimnetz -> Speicher (NAS) -> Speicher an der FRITZ!Box]
* ftp access is active [Heimnetz -> Speicher (NAS) -> Heimnetzfreigabe]

#### Preconditions

* requires FRITZ!Fon C4 or C5 handhelds
* you use an standalone user (NOT! dslf-config) which has explicit permissions for FRITZ!Box settings, access to NAS content and read/write permission to all available memory [System -> FRITZ!Box-Benutzer -> [user] -> Berechtigungen]

<img align="right" src="assets/fritzfon.png"/>

### Upload Fritz!FON background image

The background image will be uploaded during

```console
./carddav2fb run
```

Alternativly using the `background-image` command it is possible to upload only the background image to FRITZ!Fon (nothing else!)

```console
./carddav2fb background-image
```

#### FRITZ!Fon settings

* FRITZ!Fon: Einstellungen -> Anzeige -> Startbildschirme -> Klassisch -> Optionen -> Hintergrundbild

#### Image upload preconditions

* requires FRITZ!Fon C4 or C5 handhelds
* quickdial numbers are set between 2 to 9
* settings in FRITZ!Fon: Einstellungen -> Anzeige -> Startbildschirme -> Klassisch -> Optionen -> Hintergrundbild
* assignment is made via the internal number(s) of the handheld(s) in the 'fritzfons'-array in config.php
* internal number have to be between '610' and '615', no '**'-prefix

## Debugging

For debugging please set your config.php to

```php
'http' => 'debug' => true
```

## Docker image

The Docker image contains the tool and all its dependencies. A volume
`/data` contains the configuration files. If the configuration is
missing, the Docker entrypoint will abort with an error message and copy
an example file to the volume.

There are two ways to use the image:

```console
docker run --rm -v ./carddav2fb-config:/data andig/carddav2fb command...
```

will execute a single command (and remove the created container
afterwards).

Without a command, the container entrypoint will enter an endless loop,
repeatedly executing `carddav2fb run` in given intervals. This allows
automatic, regular updates of your FRITZ!Box's phonebook.

## License

This script is released under Public Domain, some parts under GNU AGPL or MIT license. Make sure you understand which parts are which.

## Authors

Copyright© 2012-2021 Andreas Götz, Volker Püschel, Karl Glatz, Christian Putzke, Martin Rost, Jens Maus, Johannes Freiburger
