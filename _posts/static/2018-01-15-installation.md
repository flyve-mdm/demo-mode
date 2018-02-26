---
layout: post
howtos: true
published: true
title: Installation
permalink: howtos/installation
description: Easy and simple
---
# Prerequisites

Please make sure you already have installed  and set up the following:

1. GLPI
1. Fusion Inventory for GLPI
1. Flyve MDM plugin

You can see more specific information about how to install and configure the Prerequisites in [Flyve MDM plugin Installation article](http://flyve.org/glpi-plugin/howtos/installation-wizard)

### Compatibility Matrix

<br>

<table>
    <tr>
        <td style="width:150px">GLPI</td>
        <td style="width:100px" align="center">9.1.x</td>
        <td style="width:100px">9.2.x</td>
    </tr>
    <tr>
        <td style="width:150px">Flyve MDM</td>
        <td style="width:100px" align="center">1.x.x</td>
        <td style="width:100px">2.0.0-dev</td>
    </tr>
    <tr>
        <td>Flyve MDM Demo</td>
        <td align="center">-</td>
        <td>1.0.0-dev</td>
    </tr>
    <tr>
        <td style="width:150px">Web MDM Dashboard</td>
        <td style="width:100px" align="center">-</td>
        <td style="width:100px">1.0.0-dev</td>
    </tr>
</table>

## Installing Demo plugin

* Download it from the repository, the methods available are:

    <!--* [Release section](https://github.com/flyve-mdm/demo-mode/releases) on GitHub. -->
  * Using Git, clone the repo: ```git clone https://github.com/flyve-mdm/demo-mode.git```
  * [Download the zip](https://github.com/flyve-mdm/demo-mode/archive/develop.zip) file (develop branch)

* Rename the folder to ```flyvemdmdemo```
* Your plugins directory should be like this:

![Demo directory](https://github.com/Naylin15/Screenshots/blob/master/glpi/demo-mode/demo-directory-structure.png?raw=true)

* Go to glpi/plugins/flyvemdmdemo
* Run ```composer install --no-dev```

### Configuration of the demo mode

Open the configuration page of the plugin from Setup>Plugins>Flyve MDM Demo.

Turn on the demo mode, and specify the Webapp URL the users can reach to manage their demo company.

Enable Time limit to limit the lifetime of the demo accounts. After a 90 days period, users will lose ability to view and edit their fleets and devices.

Service's API Token, to be added in the config.json of the App.

![Demo configurations](https://github.com/Naylin15/Screenshots/blob/master/glpi/demo-mode/demo-settings.png?raw=true)

## Working together

The [Web MDM Dashboard](http://flyve.org/web-mdm-dashboard/) works with the Demo plugin, since it enables the User Registration feature, after setting up the demo plugin, you can employ the Dashboard with the Demo Service's API Token.