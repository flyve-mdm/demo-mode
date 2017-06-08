<img style="width:100%;" src="https://user-images.githubusercontent.com/663460/26935464-54267e9c-4c6c-11e7-86df-8cfa6658133e.png">

[![Build Status](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi-demo.svg?branch=master)](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi-demo)

# Abstract

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

This plugin for GLPI provides features required to setup a demo instance of FlyveMDM.

# Installation

Install the plugin as a regular plugin for GLPI, and enable it. 

## Configuration of the demo mode

Open the configuration page of the plugin from the plugins page in GLPI. 

Turn on the demo mode, and specify the Webapp URL the users can reach to manage their demo company.

Enable the setting Time limit to limit the lifetime of the demo accounts. After a 90 days period, users will lose ability view and edit their fleets and devices. 
