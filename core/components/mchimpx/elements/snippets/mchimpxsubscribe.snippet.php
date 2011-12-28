<?php
/**
 * mChimpX
 *
 * Copyright 2011-2012 by Bert Oost at OostDesign.nl <bert@oostdesign.nl>
 *
 * mChimpX is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * mChimpX is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * mChimpX; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package mchimpx
 */
/**
 * mChimpXSubscribe - Subscribe users to the Mailchimp mailing list
 * USE IT AS FORMIT HOOK!
 *
 * @package mchimpx
 * @author Bert Oost at OostDesign.nl <bert@oostdesign.nl>
 */

$apikey = $modx->getOption('mcApiKey', $scriptProperties, false);
$listid = $modx->getOption('mcListId', $scriptProperties, false);
$emailField = $modx->getOption('mcEmailField', $scriptProperties, 'email');
$mergeTags = $modx->getOption('mcMergeTags', $scriptProperties, 'FNAME:firstname,LNAME:lastname,FULLNAME:firstname:lastname');

// subscribe options
$emailType = $modx->getOption('mcEmailType', $scriptProperties, 'html');
$doubleOptin = (boolean) $modx->getOption('mcDoubleOptin', $scriptProperties, 1);
$updateExisting = (boolean) $modx->getOption('mcUpdateExisting', $scriptProperties, 0);
$replaceInterests = (boolean) $modx->getOption('mcReplaceInterests', $scriptProperties, 1);
$sendWelcome = (boolean) $modx->getOption('mcSendWelcome', $scriptProperties, 1);

// error reporting options
$debug = (boolean) $modx->getOption('mcDebug', $scriptProperties, 0);
$errorApiKey = (boolean) $modx->getOption('mcFailOnApiKey', $scriptProperties, 0);
$errorListNotExists = (boolean) $modx->getOption('mcFailOnListNotExists', $scriptProperties, 0);
$errorAlreadySubscribed = (boolean) $modx->getOption('mcFailOnAlreadySubscribed', $scriptProperties, 0);
$errorNotSubscribed = (boolean) $modx->getOption('mcFailOnNotSubscribed', $scriptProperties, 0);
$errorMissingReq = (boolean) $modx->getOption('mcFailOnMissingRequired', $scriptProperties, 0);

// get form values
$values = $hook->getValues();

// load lexicons
$modx->lexicon->load('mchimpx:default');

if(empty($apikey)) {
  $hook->addError('', $modx->lexicon('mchimpx.error.noapi'));
  return false;
}
if(empty($listid)) {
  $hook->addError('', $modx->lexicon('mchimpx.error.nolistid'));
  return false;
}
if(empty($emailField) || !isset($values[$emailField]) || empty($values[$emailField])) {
  $hook->addError('', $modx->lexicon('mchimpx.error.noemail'));
  return false;
}
if(empty($mergeTags) || !stripos($mergeTags, ':')) {
  $hook->addError('', $modx->lexicon('mchimpx.error.nomergefields'));
  return false;
}

// Secure page?
$secure = false;
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
  $secure = true;
}

// load Mailchimp API
try {
  $modx->loadClass('MCAPI', $modx->getOption('mchimpx.core_path',null,$modx->getOption('core_path').'components/mchimpx/').'model/', true, true);
  $mc = new MCAPI($apikey, $secure);
  
  // find out the merge values
  $mergeValues = array();
  $parsefields = explode(',', trim($mergeTags));
  foreach($parsefields as $field) {
    $fields = explode(':', $field);
    $keyField = array_shift($fields);
    $mergeValues[$keyField] = '';
    foreach($fields as $index => $submitfield) {
      if(isset($values[$submitfield])) {
	$mergeValues[$keyField] .= (($index > 0) ? ' ' : '').$values[$submitfield];
      }
    }
  }
  
  // subscribe
  $success = $mc->listSubscribe($listid, $values[$emailField], $mergeValues, $emailType, $doubleOptin, $updateExisting, $replaceInterests, $sendWelcome);
  if(!$success) {
    
    switch($mc->errorCode) {
      case '104': // Invalid_ApiKey
        if($errorApiKey) { $hook->addError('', $modx->lexicon('mchimpx.error.invalidapikey')); }
      break;
      case '200': // List_DoesNotExist
        if($errorListNotExists) { $hook->addError('', $modx->lexicon('mchimpx.error.listnotexists')); }
      break;
      case '214': // List_AlreadySubscribed
      case '230': // Email_AlreadySubscribed
        if($errorAlreadySubscribed) { $hook->addError('', $modx->lexicon('mchimpx.error.alreadysubscribed')); }
      break;
      case '215': // List_NotSubscribed
      case '233': // Email_NotSubscribed
        if($errorNotSubscribed) { $hook->addError('', $modx->lexicon('mchimpx.error.notsubscribed')); }
      break;
      case '250': // List_MergeFieldRequired
        if($errorMissingReq) { $hook->addError('', $modx->lexicon('mchimpx.error.missingrequired')); }
      break;
    }
    
    if($debug) {
      $modx->log(modX::LOG_LEVEL_ERROR, '[mChimpX] ERROR: '.$mc->errorMessage);
    }
    
    return false;
  }
  
  return true;
}
catch(Exception $e) {
  
  if($debug) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[mChimpX] ERROR: '.$e->getMessage());
  }
  
  $hook->addError('', $modx->lexicon('mchimpx.error.unknown'));
  return false;
}

?>