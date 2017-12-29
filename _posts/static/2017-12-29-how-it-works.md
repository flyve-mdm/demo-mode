---
layout: post
howtos: true
published: true
title: How it works
permalink: howtos/how-it-works
description: What you should know
---
Flyve MDM Plugin for GLPI integrates the outstanding features of Flyve MDM into the GLPI platform providing you security functionalities for your IT infrastructure.

Flyve MDM secures all your devices, allowing you to manage your mobile fleet security policy with precision in order to protect sensitive company data stored on mobile devices.

For a period of 90 days, you will be able to implement the main functions of the plugin in your IT infrastructure.

## General Architecture of Flyve MDM plugin for GLPI

The Architecture of the GLPI Plugin is the following, the Mobile Device Management is composed of:

* an user interface server for the administrator
* a backend server
* a M2M server
* an agent installed in the managed device

The M2M protocol provides features to handle loss of connectivity and guarantee delivery of important messages in both directions. The agent takes control of the device to maintain a minimal connectivity with the backend server via the M2M protocol and execute requests from the backend.

The certificate delivery server needs a private key to complete its role. It must communicate only with the backend and no communication is allowed from internet or any other untrusted network. It must run on a distinct server from the backend, the M2M server and the web User Interface.

All communications must be TLS encrypted.

The M2M server is a gateway between the devices and the backend, providing some helpful features to handle the unstable connectivity with devices. These features are available in a messenging queue protocol.
