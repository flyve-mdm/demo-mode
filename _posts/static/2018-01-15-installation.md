---
layout: post
howtos: true
published: true
title: Installation
permalink: howtos/installation
description: Easy and simple
---
# Prerequisites

1. Install GLPI
1. Install Fusion Inventory
1. Install Flyve MDM

## Compatibility Matrix

<table>
    <tr>
        <td style="width:150px">Flyve MDM</td>
        <td style="width:100px">1.x.x</td>
        <td style="width:100px">2.0.0-dev</td>
    <tr>
        <td>Flyve MDM Demo</td>
        <td align="center">-</td>
        <td>1.0.0-dev</td>
    </tr>
</table>

## Flyve MDM Demo Installation

* Download it from the repository, there are two methods available:

  * Using ```git clone git https://github.com/flyve-mdm/demo-mode.git```
  * [Download the zip](https://github.com/flyve-mdm/demo-mode/archive/develop.zip) file

* Your plugins directory should be like this:

![Demo directory](https://github.com/Naylin15/Screenshots/blob/master/glpi/demo-mode/demo-directory-structure.png?raw=true)

* Run ```composer install --no-dev```

### Configuration of the demo mode

Open the configuration page of the plugin from the plugins page in GLPI.

Turn on the demo mode, and specify the Webapp URL the users can reach to manage their demo company.

Enable the setting Time limit to limit the lifetime of the demo accounts. After a 90 days period, users will lose ability to view and edit their fleets and devices.

![Demo configurations](https://github.com/Naylin15/Screenshots/blob/master/glpi/demo-mode/demo-settings.png?raw=true)
