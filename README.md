# Demo mode for Flyve MDM

![Flyve MDM banner](https://user-images.githubusercontent.com/663460/26935464-54267e9c-4c6c-11e7-86df-8cfa6658133e.png)

[![License](https://img.shields.io/github/license/flyve-mdm/flyve-mdm-glpi-demo.svg?&label=License)](https://github.com/flyve-mdm/flyve-mdm-glpi-demo/blob/master/LICENSE.md)
[![Follow twitter](https://img.shields.io/twitter/follow/FlyveMDM.svg?style=social&label=Twitter&style=flat-square)](https://twitter.com/FlyveMDM)
[![Telegram Group](https://img.shields.io/badge/Telegram-Group-blue.svg)](https://t.me/flyvemdm)
[![Conventional Commits](https://img.shields.io/badge/Conventional%20Commits-1.0.0-yellow.svg)](https://conventionalcommits.org)
[![GitHub release](https://img.shields.io/github/release/flyve-mdm/flyve-mdm-glpi-demo.svg)](https://github.com/flyve-mdm/flyve-mdm-glpi-demo/releases)

Flyve MDM is a Mobile device management software that enables you to secure and manage all the mobile devices of your business or family via a web-based console.

To get started, check out <https://flyve-mdm.com/>!

## Table of contents

* [Synopsis](#synopsis)
* [Build Status](#build-status)
* [Installation](#installation)
* [Documentation](#documentation)
* [Versioning](#versioning)
* [Contribute](#contribute)
* [Contact](#contact)
* [Copying](#copying)

## Synopsis

This plugin for GLPI provides features required to setup a demo instance of FlyveMDM.

## Build Status

| **Release channel** | **Beta channel** |
|:---:|:---:|
| [![Build Status](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi-demo.svg?branch=master)](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi-demo) | [![Build Status](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi-demo.svg?branch=develop)](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi-demo) |

## Installation

Install the plugin as a regular plugin for GLPI, and enable it.

### Configuration of the demo mode

Open the configuration page of the plugin from the plugins page in GLPI. 

Turn on the demo mode, and specify the Webapp URL the users can reach to manage their demo company.

Enable the setting Time limit to limit the lifetime of the demo accounts. After a 90 days period, users will lose ability view and edit their fleets and devices.

## Documentation

We share long-form content about the project in the [wiki](https://github.com/flyve-mdm/flyve-mdm-glpi-demo/wiki).

## Versioning

In order to provide transparency on our release cycle and to maintain backward compatibility, Flyve MDM is maintained under [the Semantic Versioning guidelines](http://semver.org/). We are committed to following and complying with the rules, the best we can.

See [the tags section of our GitHub project](http://github.com/flyve-mdm/flyve-mdm-glpi-demo/tags) for changelogs for each release version of Flyve MDM. Release announcement posts on [the official Teclib' blog](http://www.teclib-edition.com/en/communities/blog-posts/) contain summaries of the most noteworthy changes made in each release.

## Contribute

Want to file a bug, contribute some code, or improve documentation? Excellent! Read up on our
guidelines for [contributing](./CONTRIBUTING.md) and then check out one of our issues in the [Issues Dashboard](https://github.com/flyve-mdm/flyve-mdm-glpi-demo/issues).

## Contact

For notices about major changes and general discussion of Flyve MDM development, subscribe to the [/r/FlyveMDM](http://www.reddit.com/r/FlyveMDM) subreddit.
You can also chat with us via IRC in [#flyve-mdm on freenode](http://webchat.freenode.net/?channels=flyve-mdm]).
Ping me @hectorerb in the IRC chatroom if you get stuck.

## Copying

* **Name**: [Flyve MDM](https://flyve-mdm.com/) is a registered trademark of [Teclib'](http://www.teclib-edition.com/en/).
* **Code**: you can redistribute it and/or modify
    it under the terms of the GNU General Public License ([GPLv3](https://www.gnu.org/licenses/gpl-3.0.en.html)).
* **Documentation**: released under Attribution 4.0 International ([CC BY 4.0](https://creativecommons.org/licenses/by/4.0/)).
