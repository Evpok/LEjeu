LemanDragon
===========

## Installation

First, install **composer** if you haven't already: https://getcomposer.org/doc/00-intro.md

Then open a command-line in your working directory:

```sh
git clone https://github.com/Evpok/LEjeu.git
cd LEjeu
composer install
npm install uglify-js -g
npm install stylus -g
```

Edit your **php.ini** file and add or uncomment ```extension=gettext.so``` (or ```extension=php_gettext.dll``` on Windows).

Edit your **php.ini** file and add or uncomment ```extension=sockets.so``` (or ```extension=php_sockets.dll``` on Windows).

Create a database (for example a mysql one in phpMyAdmin), then add your credentials in **app/config/parameters.yml**.

You can also add a smtp mailer such as:

```yml
mailer_transport: gmail
mailer_host: 'tls://smtp.gmail.com'
mailer_user: username@gmail.com
mailer_password: password
```

Then run:

```sh
php bin/console doctrine:schema:update --force
```

If you're not on Windows, execute ```chmod``` like this:

```sh
chmod -R +w {var,web/css,web/img,web/js}
```

## Run the app

```sh
php bin/console server:run
```

You can now test the app: http://127.0.0.1:8000

If you change some text, stop the server with CONTROL-C and re-run it.

## Translation

All the texts and the code are written in english.

To translate some text, you can use Poedit (or any gettext software) with the .po files in **src/AppBundle/Resources/translations**.

Poedit also has an extract function wich use gettext and can scroll a directory to find gettext functions: ```_()```,  ```gettext()```,  ```ngettext()```.

Set your extractor to scroll Pug (Jade) files. As gettext does not support this language, you can parse *.pug,*.jade with the language Python wich is close enough in its structure.