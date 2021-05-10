# NYCO Send Me NYC for WordPress

A developer plugin for WordPress that enables sharing website links via SMS or Email. It uses [Twilio](https://www.twilio.com/) for the SMS service, [Amazon SES](https://aws.amazon.com/ses/) for the email service, [Bitly](https://bitly.com/) for url shortening, [Timber](https://www.upstatement.com/timber/) for email template rendering, and [WordPress Multilingual Plugin](https://wpml.org/) for translating email content.

## Installation using [Composer](https://getcomposer.org/)

**$1** This package uses [Composer Installers](https://github.com/composer/installers) to install the package in the **Must Use** plugins directory (*/wp-content/mu-plugins*):

    composer require nyco/wp-send-me-nyc

*Not using Composer?* Download an archive of the code and drop it into the mu-plugins directory.

**$2** [Create a proxy PHP loader file](https://wordpress.org/support/article/must-use-plugins/#caveats) inside the mu-plugins directory, or [use the one included with the plugin](https://github.com/CityOfNewYork/nyco-wp-send-me-nyc/blob/master/autoloader-sample.php):

    mv wp-content/mu-plugins/wp-send-me-nyc/autoloader-sample.php wp-content/mu-plugins/send-me-nyc.php

## Initialization

The [sample autoloader](https://github.com/CityOfNewYork/nyco-wp-send-me-nyc/blob/master/autoloader-sample.php) contains the basic code required to initialize the plugin. It will...

- Require all files containing classes and helper functions.
- Initialize the [`SMNYC\ContactMe`](https://github.com/CityOfNewYork/nyco-wp-send-me-nyc/blob/master/ContactMe.php), [`SMNYC\SmsMe`](https://github.com/CityOfNewYork/nyco-wp-send-me-nyc/blob/master/SMSMe.php), and [`SMNYC\EmailMe`](https://github.com/CityOfNewYork/nyco-wp-send-me-nyc/blob/master/EmailMe.php) classes.
- Register admin actions for [sending a message](#sending-a-message).
- Register [custom post types](#custom-post-types) for custom SMS and Email content (SMNYC Emails and SMNYC SMS).
- Create an admin settings page under *Settings > Send Me NYC* for [configuration](#configuration).

## Configuration

Each settings section corresponds to a specific service that needs to be configured to work with your WordPress Installation.

### Bitly Settings

- Bitly Shortening API Link
- Bitly Access Token

### SMS Settings (Twilio)

- Account SID
- Sender Phone Number
- API Key SID
- API Key Secret

### Email Settings (Amazon SES)

- Key
- Secret
- From Email Address
- Email Display Name (optional)
- Reply-To (optional)

**Note:** This plugin works nicely with the [NYCO WordPress Config](https://github.com/CityOfNewYork/nyco-wp-config) plugin.

## Custom Post Types

These custom post types are created to store reusable SMS and Email content that is sent in our messages.

### SMNYC SMS

An example of post content could include the following;

> REMINDER: you may be eligible for these NYC Programs: {{ BITLY_URL }}

The template tag `{{ BITLY_URL }}` will be replaced with a shortened Bitly url that is intended to be shared with the recipient. The SMNYC SMS post type does not require any specific templating.

For more dynamic content, you can include an input for `sharetext` and include the template tag `{{ SHARE_TEXT }}` into the post content.

> {{ SHARE_TEXT }} {{ BITLY_URL }}

### SMNYC Email

An example of post content could be the same as the SMS, however, you may not need to use the Bitly shortener for the url. In this case, replace `{{ BITLY_URL }}` with `{{ URL }}`.

> REMINDER: you may be eligible for these NYC Programs: {{ URL }}

The SMNYC Email post type requires a template controller that extends the [`Timber\Post` class](https://timber.github.io/docs/reference/timber-post/) and a [Twig](https://twig.symfony.com/) file containing the template that will render the content into an HTML email. The [sample controller](https://github.com/CityOfNewYork/nyco-wp-send-me-nyc/blob/master/controllers/smnyc-email-sample.php) contains a working class that extends `Timber\Post`. Move this file to the root of your active theme directory.

    mv wp-content/mu-plugins/nyco-wp-send-me-nyc/controllers/smnyc-email-single-sample.php wp-content/themes/theme/smnyc-email.php

The [sample email](https://github.com/CityOfNewYork/nyco-wp-send-me-nyc/blob/master/views/email/single-sample.twig) contains a working email twig template. Use this file or create a file within your [Timber Views](https://timber.github.io/docs/guides/template-locations/#changing-the-default-folder-for-twig-files) directory called *emails/single.twig*. This is where the HTML markup for your email will be placed.

**Customization**

The path to the controller file and class contents can be used as is or modified as needed. By default, `SMNYC\EmailMe` requires a file called `smnyc-email.php` in the root of the activated WordPress theme that contains the the controller class. However, different path can be passed to the `SMNYC\EmailMe` class on instantiation in the [auto loader](#initialization);

    $email = new SMNYC\EmailMe('controllers/smnyc-email.php');

Examining the `smnyc-email.php` file, we can see that the class has a template constant.

    /** The twig template for emails */
    const TEMPLATE = 'emails/single.twig';

This is the path inside the [*views*](https://timber.github.io/docs/guides/template-locations/#changing-the-default-folder-for-twig-files) where the email template is stored. Modify the string with a different path if desired. The `smnyc-email.php` also contains a method called `->addToPost()` where programmable post content can be added to pass to the view when it is rendered.

## Sending a Message

WordPress Admin Ajax is used to send data from the front-end to the plugin. When the plugin is initialized, actions [using `wp_ajax_{$_REQUEST[‘action’]}`](https://developer.wordpress.org/reference/hooks/wp_ajax__requestaction/) and [`wp_ajax_nopriv_{$_REQUEST[‘action’]}`](https://developer.wordpress.org/reference/hooks/wp_ajax_nopriv__requestaction/) are registerd. Hidden inputs can be used to configure the data that is sent via the script below.

    {# .twig #}

    <form action="https://mysite.com/wp-admin/admin-ajax.php" method="post" data-js="smnyc">
      <input type="hidden" readonly name="action" value="sms_send" />
      <input type="hidden" readonly name="url" value="https://mysite.com/my-url-to-share/" />
      <input type="hidden" readonly name="template" value="my-sms-post-template" />
      <input type="hidden" readonly name="lan" value="en" />
      <input type="hidden" readonly name="sharetext" value="{{ message }}" />
      <input type="hidden" readonly name="hash" value="{{ hash }}" />

      <input name="to" placeholder="Phone Number" required="true" type="tel" />
      <button class="btn btn-primary btn-small" type="submit">Share</button>
    </form>

### Values

Name       | Value Description
-----------|-
`to`       | A valid email address (validates against the [`FILTER_VALIDATE_EMAIL`](https://www.php.net/manual/en/filter.filters.validate.php) validate filter) or phone number (10 digit number including area code), depending on the action.
`action`   | The Ajax action callback name; `sms_send` or `email_send`.
`url`      | The URL to be shared with the recipient, this is what replaces the contents of the `{{ BITLY_URL }}` and `{{ URL }}` in the post content.
`template` | The slug of the SMNYC Post to use as the template for sms or email content.
`lang`     | The language code of the template content. This should generally be the same as the current language of the page document. This value is passed to the [`wpml_object_id`](https://wpml.org/wpml-hook/wpml_object_id/) filter provided by [WPML](https://wpml.org/).
`hash`     | Required to prevent [CSRF](https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)). The plugin ships with a helper function to generate a new hash value to pass to your view. Below is an example of adding a hash to [Timber context](https://timber.github.io/docs/getting-started/theming/#get-the-context) that is passed to the view template.

#### SMNYC\hash()

    /* php */

    // Retrieving a hash using the SMNYC helper function.
    $context['hash'] = SMNYC\hash('https://mysite.com/my-to-share/');

Below is an example script that adds a submit event listener to the form, [serializes](https://www.npmjs.com/package/form-serialize) the form data on submit, and passes the data as a JSON object to the [Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API).

    /* JavaScript */

    // https://www.npmjs.com/package/form-serialize
    import FormSerialize from 'form-serialize';

    let FORM = document.querySelector('[data-js="smnyc"]');

    FORM.addEventListener('submit', (event) => {
      // To send the data with the application/x-www-form-urlencoded header
      // we need to use URLSearchParams(); instead of FormData(); which uses
      // multipart/form-data
      let formData = new URLSearchParams();

      // Serialize the form data.
      let data = FormSerialize(FORM, {hash: true});

      // Iterate over our serialized data and append to formData.
      Object.keys(data).map(k => {
        formData.append(k, data[k]);
      });

      // Send the request via the Fetch API.
      fetch(FORM.getAttribute('action'), {
        method: FORM.getAttribute('method'),
        body: formData
      }).then(response => response.json())
        .then(response => {
          // My Response handler
        }).catch(data => {
          // My Error handler
        });
    });

## Response Codes

Below are various response codes returned by the Email/SMS service and their meaning.

Codes               | Meaning
--------------------|-
**General**         |
9                   | The hash provided is invalid. See the [values](#values) table for a description of the `SMNYC\hash()` usage.
-1                  | The [configuration](#configuration) is invalid.
400                 | A URL is missing from the request. See the [values](#values) table for a full list of required values.
**Email**           |
1                   | Missing email address. See the [values](#values) table for a full list of required values.
2                   | Invalid email address.
3                   | An exception from the Amazon SES service. [Reference the PHP SDK docs](https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.Ses.Exception.SesException.html) for error descriptions.
**SMS Errors**      |
1                   | Missing phone number. See the [values](#values) table for a full list of required values.
2                   | Invalid phone number. Must be a 10-digit number including area code. The final value should be without spaces, dashed, etc.
**Twilio Response** |
30006               | Unable to send to number provided.
21611               | The outbox queue is full (please try again later).
30007               | Invalid message body.
30009               | Ephemeral errors that a retry might solve.
...                 | [Reference the Twilio Docs](https://www.twilio.com/docs/sms) for other errors.

## Actions

### smnyc_message_sent

This action is fired after a message is sent successfully.

**...args**

- `String  $type` Email/SMS/whatever the class type is.
- `String  $to` Recipient of message.
- `String  $guid` Session GUID.
- `String  $url` URL to that has been shared.
- `String  $msg` The body of the message.

#### Examples

    add_action('smnyc_message_sent', function($type, $to, $uid, $url, $message) {
      // Successful message sent handler
    }, 10, 5);

---

![The Mayor's Office for Economic Opportunity](NYCMOEO_SecondaryBlue256px.png)

[The Mayor's Office for Economic Opportunity](http://nyc.gov/opportunity) (NYC Opportunity) is committed to sharing open source software that we use in our products. Feel free to ask questions and share feedback. **Interested in contributing?** See our open positions on [buildwithnyc.github.io](http://buildwithnyc.github.io/). Follow our team on [Github](https://github.com/orgs/CityOfNewYork/teams/nycopportunity) (if you are part of the [@cityofnewyork](https://github.com/CityOfNewYork/) organization) or [browse our work on Github](https://github.com/search?q=nycopportunity).