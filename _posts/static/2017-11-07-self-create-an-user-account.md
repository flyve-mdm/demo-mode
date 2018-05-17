---
layout: post
howtos: true
published: true
title: Self create a user account
permalink: howtos/self-create-account
description: All the requests implemented
category: user
date: 2018-01-13
---

## Requests

### Obtaining a session token

The dashboard must first acquire a session token issuing a request like the following:

```
GET http://api.domain.com/initSession?user_token=45erjbudklq5865sdkjhjks
Content-Type: application/json
```

The user_token is the token of the service account preconfigured in the dashboard. This token can be found in the Flyve MDM Demo configuration as **Service's API Token**.

Note: **the header is required**

Answer:

```json
200 OK
{
   "session_token": "83af7e620c83a50a18d3eac2f6ed05a3ca0bea62"
}
```

### Request a new captcha

```
POST http://api.domain.com/PluginFlyvemdmdemoCaptcha?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62
Content-Type: application/json
```

Answer if the request succeeds

```json
200 OK
{
  "id": 3,
  "message": ""
}
```

Answers if the request fails

```
400 Bad Request
```

```
401 Unauthorized
```

Payload

```json
{"input":
  {
    "a": "a"
  }
}
```

Note: 2017-07-28 it seems GLPI misbehaves if input is empty.

Retain in memory the ID of the captcha returned in the answer.

It is possible to request new captchas before instanciating the user, in a quantity limit per period of time and per IP address.

### Get the captcha picture

```
GET http://api.domain.com/PluginFlyvemdmdemoCaptcha/:id?alt=media
Content-Type: application/octet-stream
```

Payload

A RAW picture stream (JPEG) to be presented to the user as a Turing test.

Note: The API consumer may provide a captcha refresh feature to allow the user to change the captcha if it is too hard. However generation limitation rate occurs.

### Instanciation of the user

After a session token is acquired, the dashboard must create an user account. The property **_newsletter** must contain a non zero value to register the user in the newsletter.

```
POST http://api.domain.com/PluginFlyvemdmdemoUser?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62
Content-Type: application/json
```

Payload

```json
{"input":
  {
    "name": "emailaddress@domain.com",
    "password": "p@ssw0rd",
    "password2": "p@ssw0rd",
    "firstname": "John",
    "realname": "Doe",
    "_newsletter": "1",
    "_plugin_flyvemdmdemo_captchas_id": "3",
    "_answer": "wsad"
  }
}
```

* *_plugin_flyvemdmdemo_captchas_id* the captcha ID generated previously
* *_answer* is the answer to the captcha

Answer:

```json
200 OK
{
   "id": 19,
   "message": ""
}
```

On success the demo plugin created the user account, an entity for him, and a default fleet. An email is sent to the email address to validate the account creation.

### Account validation

The email contains a validation link to the dashboard. The dashboard "converts" the request from a HTTP GET verb to a HTTP PUT verb.

The link in the email contains a validation ID and an associated validation token. The validation token is provided in the body of the request, the ID can be provided either in the URL or in the body. Refer to the GLPI's API documentation.

```
PUT http://api.domain.com/PluginFlyvemdmdemoAccountvalidation/4?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62
Content-Type: application/json
```

Payload

```json
{"input":
  {
    "_validate": "6543654dsfjkqs5465786764"
  }
}
```

Answer if success

```
200 OK
[{"10":true, "message": ""}]
```

Answer if the request fails

```
400 Bad Request
```

### Logout after creation

```
GET http://api.domain.com/killSession?session_token=83af7e620c83a50a18d3eac2f6ed05a3ca0bea62
Content-Type: application/json
```

The answer should contain an empty body

Answer if the request succeeds

```
200 OK
```

Answer if the request fails

```
400 Bad Request
```