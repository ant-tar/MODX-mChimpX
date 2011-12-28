--------------------
Component: mChimpX
--------------------
Author: Bert Oost at OostDesign.nl <bert@oostdesign.nl>

A FormIt hook provided to subscribe an emailaddress to your Mailchimp list. Fully confiurable.

Possible options which can be added to the FormIt tag:
---------------------------------
- mcApiKey
  The Mailchimp API Key
- mcListId
  The Mailchimp list id to subscribe to
- mcEmailField
  The name of the email field in the form. defaults to 'email'
- mcMergeTags
  The merge tags of Mailchimp combined with the form fields.
  defaults to 'FNAME:firstname,LNAME:lastname,FULLNAME:firstname:lastname'


Subscribe parameters
General: true eq. 1 / false eq. 0
---------------------------------
- mcEmailType
  Email type preference for the email (html, text, or mobile defaults to html)
- mcDoubleOptin
  To control whether a double opt-in confirmation message is sent, defaults to true
- mcUpdateExisting
  To control whether a existing subscribers should be updated instead of throwing and 
  error, defaults to false
- mcReplaceInterests
  To determine whether we replace the interest groups with the groups provided, or we 
  add the provided groups to the member's interest groups (optional, defaults to true)
- mcSendWelcome
  if your double_optin is false and this is true, we will send your lists Welcome Email 
  if this subscribe succeeds - this will *not* fire if we end up updating an existing 
  subscriber. If double_optin is true, this has no effect. defaults to false.


Error parameters
General: true eq. 1 / false eq. 0
---------------------------------
- mcDebug
  True or false to debug messages in MODX error log, when needed. defaults to false.
- mcFailOnApiKey
  True or false to return API key errors to the front-end form. defaults to false.
- mcFailOnListNotExists
  True or false to return 'list not exists' error to the front-end form. defaults to false.
- mcFailOnAlreadySubscribed
  True or false to return 'already subscribed' error to the front-end form. defaults to false.
- mcFailOnNotSubscribed
  True or false to return 'not subscribed' error to the front-end form. defaults to false.
- mcFailOnMissingRequired
  True or false to return 'missing required' error to the front-end form. defaults to false.

Official Documentation:
http://rtfm.modx.com/display/ADDON/mChimpX