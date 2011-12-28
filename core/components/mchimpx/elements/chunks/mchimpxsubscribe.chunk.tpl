[[!FormIt?
  &submitVar=`subscribeMchimp`
  &hooks=`spam,mChimpXSubscribe`
  &validate=`nospam:blank,firstname:required,lastname:required,email:email:required`
  &successMessage=`You're now subscribed to the mailinglist!`
  
  &mcApiKey=`0cf375b985c6d135d89baab4e6fb0b21-us2`
  &mcListId=`9fa7f3c6a1`
  &mcMergeTags=`FNAME:firstname,LNAME:lastname,FULLNAME:firstname:lastname`
  &mcSendWelcome=`0`
]]

[[!+fi.error_message:notempty=`<p>[[+fi.error_message]]</p>`]]

[[!+fi.successMessage:notempty=`<p><strong>[[+fi.successMessage]]</strong></p>`]]

<form action="[[~[[*id]]]]" method="post">
  <p style="margin:0px;"><input type="hidden" name="nospam" value="" /></p>
  <p>
    <strong>Your firstname:</strong><br />
    <input type="text" name="firstname" value="[[+fi.firstname]]" />
  </p>
  <p>
    <strong>Your lastname:</strong><br />
    <input type="text" name="lastname" value="[[+fi.lastname]]" />
  </p>
  <p>
    <strong>Your Email:</strong><br />
    <input type="text" name="email" value="[[+fi.email]]" />
  </p>
  <p>
    <input type="submit" name="subscribeMchimp" value="Subscribe" />
  </p>
</form>