Contao Mailchimp Bundle
==========================

This Contao bundle provides subscribe and unsubscribe forms for easy integration in Contao.

[![Author](http://img.shields.io/badge/author-@1upgmbh-blue.svg?style=flat-square)](https://twitter.com/1upgmbh)
[![Software License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](http://img.shields.io/packagist/dt/oneup/contao-mailchimp.svg?style=flat-square)](https://packagist.org/packages/oneup/contao-mailchimp)

--

The fields that will be shown are managed through MailChimp's [`List fields and *|MERGE|* tags`](http://kb.mailchimp.com/lists/managing-subscribers/set-default-merge-values-for-a-list). You may also change the order of this fields: In MailChimp, go to Signup forms > General forms > Signup Form and rearrange the fields via drag'n'drop. You then have to import the new field order into Contao, by simply re-saving the MailChimp list in the back end. 

## How-to

Install using the command line `composer require oneup/contao-mailchimp` or by using the [Contao Manager](https://contao.org/de/download.html).

### Require of oneup/contao-mailchimp

- [codefog/contao-haste](https://github.com/codefog/contao-haste)
- [contao/core-bundle](https://github.com/contao/core-bundle)
- [oneup/mailchimp-api-v3](https://github.com/1up-lab/mailchimp-api-v3)
- [patchwork/utf8](https://github.com/tchwork/utf8)

After installing the Contao MailChimp bundle you have to build a connection to MailChimp.

Login into the Contao Backend and go to Content > MailChimp:

- Create a new List
- Enter a name for your list
- Enter your [MailChimp API Key](https://mailchimp.com/help/about-api-keys/#find+or+generate+your+api+key)
- Enter your subscriber [List ID](https://mailchimp.com/help/find-your-list-id/)

Create four pages under Layout > Site Structure:

- Subscribe to the newsletter
- Subscribe confirmation
- Unsubscribe from the newsletter
- Unsubscribe confirmation

Go to Layout > Themes > Modules:

- Add a module of the type **Subscirbe form**
- Choose your MailChimp List
- Choose the redirection page after subscription
- Choose whether you will use double opt-in for subscription
- Choose whether you will use the HTML5-Placeholder in the form fields or not
- Add a module of the type **Unsubscribe form**
- Choose your MailChimp List
- Choose the redirection page after unsubscription
- Choose whether you will use the HTML5-Placeholder in the form fields or not

Go to Content > Articles:

- Insert the module of the type **Subscirbe form** on the page Subscribe to the newsletter
- Insert the module of the type **Unsubscribe form** on the page Unsubscribe from the newsletter
