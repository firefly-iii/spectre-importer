[![Packagist][packagist-shield]][packagist-uri]
[![License][license-shield]][license-uri]
[![Stargazers][stars-shield]][stars-url]
[![Donate][donate-shield]][donate-uri]

<!-- PROJECT LOGO -->
<br />
<br />
<p align="center">
      <img src="https://fireflyiiiwebsite.z6.web.core.windows.net/assets/logo/small.png" alt="Firefly III" width="120" height="178">
  </a>
</p>
  <h1 align="center">Firefly III Salt Edge / Spectre importer</h1>

  <p align="center">
    A tool to import from Salt Edge / Spectre into Firefly III.
    <br />
    <a href="https://docs.firefly-iii.org/other-data-importers/"><strong>Explore the docs »</strong></a>
    <br />
    <br />
    <a href="https://demo.firefly-iii.org/">View Firefly III demo</a>
    ·
    <a href="https://github.com/firefly-iii/firefly-iii/issues">Report Bug</a>
    ·
    <a href="https://github.com/firefly-iii/firefly-iii/issues">Request Feature</a>
  </p>

<!-- MarkdownTOC autolink="true" -->

- [About the Firefly III Salt Edge / Spectre importer](#about-the-firefly-iii-salt-edge--spectre-importer)
  - [Purpose](#purpose)
  - [Features](#features)
  - [Who's it for?](#whos-it-for)
- [Getting Started](#getting-started)
  - [Using the web interface](#using-the-web-interface)
  - [Using the auto-import](#using-the-auto-import)
- [Known import challenges](#known-import-challenges)
- [Other stuff](#other-stuff)
  - [Contribute](#contribute)
  - [Support the development of Firefly III and the Spectre API import tool](#support-the-development-of-firefly-iii-and-the-spectre-api-import-tool)
  - [Contact](#contact)

<!-- /MarkdownTOC -->

## About the Firefly III Salt Edge / Spectre importer
This is a tool to import from Salt Edge / Spectre files into [Firefly III](https://github.com/firefly-iii/firefly-iii). It works by using a personal access token to access your Firefly III installation's API. It will then create transactions downloaded from your bank through the Spectre API.

The [Spectre API](https://www.saltedge.com/products/spectre) is a product by fintech company [Salt Edge](https://www.saltedge.com/). It is one of the few API's that I know of with a (semi) free tier that can connect to a lot of banks. Technically speaking, the Spectre API isn't actually meant for this use case: it's a business service designed for people who build financial services like Mint.com or YNAB. Or maybe, you're building an iPhone app? Then Salt Edge is something for you.

For us mortals however, we can use Spectre's free tier to connect to our bank since it's inside of the limit that Salt Edge puts on free accounts. The last I heard though, Spectre has a time limit of **90 days**.

### Purpose

Use this tool to (automatically) import from your bank into Firefly III using the Spectre API. If you're a bit of a developer, feel free to use this code to generate
 your own import tool.

### Features

* This tool will let you download or generate a configuration file, so the next import will go faster.

### Who's it for?

Anybody who uses Firefly III and wants to automatically import from their bank using the Spectre API.

## Getting Started

You can use this tool in several ways.

1. [Install it on your server using composer](https://docs.firefly-iii.org/other-data-importers/install/self_hosted/).
2. [Use the Docker-image](https://docs.firefly-iii.org/other-data-importers/install/docker/).

Generally speaking, it's easiest to use and install this tool the same way as you use Firefly III. And although it features an excellent web-interface, you can also use the command line to import your data. There are [upgrade instructions](https://docs.firefly-iii.org/other-data-importers/upgrade/) for both methods of installation.

The [full usage instructions](https://docs.firefly-iii.org/other-data-importers/) can be found in the documentation. Basically, this is the workflow:

### Using the web interface

1. [Set up and configure your Personal Access Token and Firefly III URL](https://docs.firefly-iii.org/other-data-importers/install/configure/).
2. [Create a new connection to your bank or select an existing connection](https://docs.firefly-iii.org/other-data-importers/usage/filter_import/).
3. [Configure the import parameters](https://docs.firefly-iii.org/other-data-importers/usage/configure/).
4. [Map values in the spectre file to existing values in your database](https://docs.firefly-iii.org/other-data-importers/usage/map/).
5. [Enjoy the result in Firefly III](https://github.com/firefly-iii/firefly-iii).

### Using the auto-import

1. Import at least once, so you'll have a configuration file.
2. [Run the Docker inline import command](https://docs.firefly-iii.org/other-data-importers/usage/command_line/)

## Known import challenges

Most people run into the same problems when importing data into Firefly III. Read more about those on the following pages:

1. [Issues with your Personal Access Token](https://docs.firefly-iii.org/other-data-importers/errors/token_errors/)
2. [Often seen errors and issues](https://docs.firefly-iii.org/other-data-importers/errors/freq_errors/).
3. [Frequently asked questions](https://docs.firefly-iii.org/other-data-importers/errors/freq_questions/).

## Other stuff

### Contribute

Your help is always welcome! Feel free to open issues, ask questions, talk about it and discuss this tool. You can also join [reddit](https://www.reddit.com/r/FireflyIII/) or follow me on [Twitter](https://twitter.com/Firefly_III).

Of course, there are some [contributing guidelines](https://github.com/firefly-iii/spectre-importer/blob/main/.github/contributing.md) and a [code of conduct](https://github.com/firefly-iii/spectre-importer/blob/main/.github/code_of_conduct.md), which I invite you to check out.

For all other contributions, see below.

### Support the development of Firefly III and the Spectre API import tool

If you like this tool and if it helps you save lots of money, why not send me a dime for every dollar saved!

OK that was a joke. You can donate using [PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=L62W7DVD5ETPC&source=url), [Patreon](https://www.patreon.com/jc5) or the [GitHub Sponsors Program](https://github.com/sponsors/JC5).

This work [is licensed](https://github.com/firefly-iii/spectre-importer/blob/master/LICENSE) under the [GNU Affero General Public License v3](https://www.gnu.org/licenses/agpl-3.0.html).

### Contact

You can contact me at [james@firefly-iii.org](mailto:james@firefly-iii.org), you may open an issue or contact me through the various social media pages there are: [reddit](https://www.reddit.com/r/FireflyIII/) and [Twitter](https://twitter.com/Firefly_III).

[![Scrutinizer][scrutinizer-shield]][scrutinizer-uri]
[![Requires PHP7.4][php-shield]][php-uri]
[![Quality Gate Status](gate-shield)](gate-uri)

[scrutinizer-shield]: https://img.shields.io/scrutinizer/g/firefly-iii/spectre-importer.svg?style=flat-square
[scrutinizer-uri]: https://scrutinizer-ci.com/g/firefly-iii/spectre-importer/
[php-shield]: https://img.shields.io/badge/php-7.4-red.svg?style=flat-square
[php-uri]: https://secure.php.net/downloads.php
[packagist-shield]: https://img.shields.io/packagist/v/firefly-iii/spectre-importer.svg?style=flat-square
[packagist-uri]: https://packagist.org/packages/firefly-iii/spectre-importer
[license-shield]: https://img.shields.io/github/license/firefly-iii/spectre-importer.svg?style=flat-square
[license-uri]: https://www.gnu.org/licenses/agpl-3.0.html
[stars-shield]: https://img.shields.io/github/stars/firefly-iii/spectre-importer.svg?style=flat-square
[stars-url]: https://github.com/firefly-iii/spectre-importer/stargazers
[donate-shield]: https://img.shields.io/badge/donate-%24%20%E2%82%AC-brightgreen?style=flat-square
[donate-uri]: #support
[gate-shield]: https://sonarcloud.io/api/project_badges/measure?project=firefly-iii_spectre-importer&metric=alert_status
[gate-uri]: https://sonarcloud.io/dashboard?id=firefly-iii_spectre-importer
