---
layout: post
howtos: true
published: true
title: How it works
permalink: howtos/how-it-works
description: What you should know
---

# Flyve MDM Demo

The Demo allows the self creation of accounts, since GLPI currently doesn't count with this feature, the endpoint required is one provided by the same demo, PluginFlyvemdmddemoUsers.

## Captcha

At the moment of registering the user information a captcha must be provided, otherwise it won't allow the user registration.

To generate and display the captcha to the user, two different methods are used, first the HTTP Post and then the HTTP Get.

If the captcha is too complicated for the user, a new one can be requested with the same procedure through a refresh feature.

## Account validation

Once the account is succesfully created, the user will receive an email to validate it.

This validation has a time limit of 1 day, if the user doesn't confirm, its account will be removed from GLPI.

When the validation is successful the profile is changed from Flyve MDM inactive registered users to Flyve MDM registered user.

The user will have now access to both the Web Dashboard and the GLPI interface.

## Demo account time limit

If the trial period is enabled, after 90 days the demo accounts created will be disabled.