# Winter CMS Web Installer

[![Discord](https://img.shields.io/discord/816852513684193281?label=discord&style=flat-square)](https://discord.gg/D5MFSPH6Ux)

A web-based installation script that will install a fresh copy of Winter CMS. This is the recommended method of installation for **non-technical users**. It is simpler than the command-line installation or Composer methods and does not require any special skills.

For developers, we recommend the following methods:

- [Composer installation](https://wintercms.com/docs/console/commands#console-install-composer)
- [Command-line installation](https://github.com/wintercms/cli)

## Installation

1. Prepare an empty directory on the web server that will host your Winter CMS installation. It can be a main domain, sub-domain or subfolder.
2. [Download the "install.zip" file](https://github.com/wintercms/web-installer/releases/latest) from the latest release of the Winter CMS Web Installer into this folder.
3. Unzip the downloaded ZIP file.
4. Grant write permissions to all files and folders that were extracted.
5. In your web browser, navigate to the URL pointing to that folder, and include `/install.html` at the end of the URL.
6. Follow the instructions given in the installer.

> **Note:** The web installer will install a production version of Winter CMS that does not contain files necessary for development of Winter CMS itself, like unit tests or Composer files. Should you require these, please use one of the other installation methods.

## Links

For further information and requirements for Winter CMS, [consult the documentation](https://wintercms.com/docs). To review the files that will be installed, visit the [main Winter CMS repository](https://github.com/wintercms/winter).

## Development

The Web installer is built on [VueJS](https://vuejs.org) and uses a primarily NodeJS-driven development pipeline, but does
contain a PHP API element to run checks on the system and perform the installation of Winter CMS. You will need the following:

- `node` v13 or above.
- `npm` v5 or above.
- `php` v7.2.9 or above.

To install all necessary dependencies, run the following:

```
npm install
```

You will also need to install the PHP dependencies located in the `public/install/api` directory. Run the following from the root directory to do so:

```
composer --working-dir=./public/install/api install
```


### Development server (hot-reloading)

The included dependencies include a simple web-server which performs hot-reloading of the app when changes are made. To start this server, run the following:

```
npm run serve
```

You will be given a URL in which you can review the application in your browser.

When developing in this format, you will need to provide a URL in order to access the PHP API file located in `public/install/api.php`. You will need to create a file called `.env.local` inside the root folder of your development copy, and add the following line:

```
VUE_APP_INSTALL_URL="<path>"
```

Substituting `<path>` with the URL to the `public/install` directory as it would be located on your web server. You can run the in-built PHP server to serve this, by navigating to this folder and running the following command:

```
php -S 127.0.0.1:8081
```

In following the above, the `.env.local` file should contain the following:

```
VUE_APP_INSTALL_URL="http://127.0.0.1:8081"
```

### Building the application

Building the application is a piece of cake! Simply run the following:

```
npm run build
```

The build will then be made available in the `dist` subfolder.

### Linting

This application is built on a strict code formatting and quality standard, and will be checked on commit. If you want to fix common issues and check that your code meets the standard, you can run the following:

```
npm run lint
```
