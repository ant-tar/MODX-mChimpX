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
 * mChimpXSubscribe - Subscribe users to the Mailchimp mailing list
 * USE IT AS FORMIT HOOK!
 *
 * @package mchimpx
 * @author Bert Oost at OostDesign.nl <bert@oostdesign.nl>
 * @author Oleg Pryadko <oleg@websitezen.com> - simplify, fixes, & update for Mailchimp API v2.0
 * @author Anton Tarasov <contact@antontarasov.com> - Further maintenance & support
 *
 * &mcGroupings Format: comma-separated list of: <grouping_id_or_name>:<group_names_separated_by_colons>
 *   -> Example using numeric grouping id: &mcGroupings=`1234:GroupName1:GroupName2, 2222:GroupName2,GroupName3`
 * &mcGroupingFields Format: comma-separated list of: <field_name>:<grouping_id_or_name>:<group_names_separated_by_colons>
 *   -> The <field_name> can be any FormIt with any value; the groups are added as long as the field value is not empty.
 *   -> Example: &mcGroupingFields=`option_1:Grouping:Group1:Group2, option2:Grouping2:Group1:Group2`
 *
 * &mcApiKey and &mcListId can be replaced by appropriate system setting values: mcApiKey and mcListId, but are still here and can be used from
 * backward compatibility point of view
 *
 * Example:
 *      &mcApiKey=`0cda888888800000000-us7`
 *      &mcListId=`21234523456b`
 *      &mcMergeTags=`FNAME:firstname,LNAME:lastname,FULLNAME:firstname:lastname`
 *      &mcGroupings=`<grouping_id>:<group>,<grouping_id>:<group>`
 *      &mcGroupingFields=`option_group1:1234:Group1,option_group2:Grouping2:Group2`
 *
 * @var array $scriptProperties
 * @var modX $modx
 * @var fiHooks $hook
 * @return bool
 */
if (!function_exists('parse_mchimpx')) {
    function parse_mchimpx(modX $modx, fiHooks $hook, array $scriptProperties) {
        $debug = $modx->getOption('mcDebugPh', $scriptProperties, false);
        $apikey = $modx->getOption('mcApiKey', null, $modx->getOption('mcApiKey', $scriptProperties, false), true);
        $listid = $modx->getOption('mcListId', null, $modx->getOption('mcListId', $scriptProperties, false), true);
        $emailField = $modx->getOption('mcEmailField', $scriptProperties, 'email');
        $emailTypeField = $modx->getOption('mcEmailTypeField', $scriptProperties, 'email_type');
        $mergeTags = $modx->getOption('mcMergeTags', $scriptProperties, 'FNAME:firstname,LNAME:lastname,FULLNAME:firstname:lastname');
        $mcGroupings = $modx->getOption('mcGroupings', $scriptProperties, '');  #
        $mcGroupingFields = $modx->getOption('mcGroupingFields', $scriptProperties, '');

        // subscribe options
        $doubleOptin = (boolean)$modx->getOption('mcDoubleOptin', $scriptProperties, 1);
        $updateExisting = (boolean)$modx->getOption('mcUpdateExisting', $scriptProperties, 0);
        $replaceInterests = (boolean)$modx->getOption('mcReplaceInterests', $scriptProperties, 1);
        $sendWelcome = (boolean)$modx->getOption('mcSendWelcome', $scriptProperties, 1);

        // error reporting options
        $log_errors = (boolean)$modx->getOption('mcLogErrors', $scriptProperties, 1);
        $display_errors = (boolean)$modx->getOption('mcShowErrors', $scriptProperties, 0);
        $ERROR_KEY = (boolean)$modx->getOption('mcErrorField', $scriptProperties, 'error_message');

        // get form values
        $values = $hook->getValues();
        $email = $values[$emailField];

        // load lexicons
        $modx->lexicon->load('mchimpx:default');

        if (empty($apikey)) {
            $hook->addError($ERROR_KEY, $modx->lexicon('mchimpx.error.noapi'));
            return false;
        }
        if (empty($listid)) {
            $hook->addError($ERROR_KEY, $modx->lexicon('mchimpx.error.nolistid'));
            return false;
        }
        if (empty($emailField) || !isset($values[$emailField]) || empty($values[$emailField])) {
            $hook->addError($ERROR_KEY, $modx->lexicon('mchimpx.error.noemail'));
            return false;
        }
        if (empty($mergeTags) || !stripos($mergeTags, ':')) {
            $hook->addError($ERROR_KEY, $modx->lexicon('mchimpx.error.nomergefields'));
            return false;
        }

        // load Mailchimp API
        try {

            $emailType = $emailTypeField ? $modx->getOption($emailTypeField, $values, 'html') : 'html';
            $emailType = in_array($emailType, array('html', 'text')) ? $emailType : 'html';

            // find out the merge values
            $mergeValues = array();
            $parsefields = explode(',', trim($mergeTags));
            foreach ($parsefields as $field) {
                $fields = array_map('trim', explode(':', $field));
                $keyField = array_shift($fields);
                foreach ($fields as $index => $submitfield) {
                    if (!isset($values[$submitfield])) {
                        continue;
                    }
                    if (!isset($mergeValues[$keyField])) {
                        $mergeValues[$keyField] = '';
                    }
                    $mergeValues[$keyField] .= (($index > 0) ? ' ' : '') . $values[$submitfield];
                }
            }

            $_groupings = array();

            // parse $mcGroupingFields
            foreach (explode(',', $mcGroupingFields) as $field) {
                # e.g. <fieldname>:<grouping>:<group>, <fieldname2>:<grouping>:<group>
                $parts = array_map('trim', explode(':', $field));
                $field_name = array_shift($parts);
                $grouping_id = array_shift($parts);
                if (empty($grouping_id)) {
                    continue;
                }
                $groups = $parts;
                $field_value = $modx->getOption($field_name, $values);
                // if no groups set, use checkbox option values for that
                if (!$groups) {
                    $groups = is_array($field_value) ? $field_value : explode(',', $field_value);
                    $groups = array_map('trim', $groups);
                // otherwise, use the groups set if the field_value
                } elseif (!$field_value) {
                    continue;
                }
                if (array_key_exists($grouping_id, $_groupings)) {
                    $_groupings[$grouping_id] = array_merge($_groupings[$grouping_id], $groups);
                } else {
                    $_groupings[$grouping_id] = $groups;
                }
            }

            // parse $mcGroupings
            foreach (explode(',', $mcGroupings) as $field) {
                # e.g. <fieldname>:<grouping>:<group>
                $parts = explode(':', $field);
                $grouping_id = trim(array_shift($parts));
                if (empty($grouping_id)) {
                    continue;
                }
                $groups = $parts;
                if (array_key_exists($grouping_id, $_groupings)) {
                    $_groupings[$grouping_id] = array_merge($_groupings[$grouping_id], $groups);
                } else {
                    $_groupings[$grouping_id] = $groups;
                }
            }

            // transform groupings to mergevalues
            $mergeValues['groupings'] = array();
            foreach ($_groupings as $id => $groups) {
                $grp = array();
                if (is_numeric($id)) {
                    $grp['id'] = (int)$id;
                } else {
                    $grp['name'] = $id;
                }
                foreach ($groups as $i => $grp_id) {
                    if (is_numeric($grp_id)) {
                        $groups[$i] = $grp_id;
                    } else {
                        $groups[$i] = $grp_id;
                    }
                }
                $grp['groups'] = array_values(array_unique($groups));
                $mergeValues['groupings'][] = $grp;
            }

            $modx->loadClass('mailchimpx', $modx->getOption('mchimpx.core_path', null, $modx->getOption('core_path') . 'components/mchimpx/') . 'model/', true, true);
            $mc = new MailchimpX($modx, $apikey);

            // subscribe
            $modx->log(modX::LOG_LEVEL_INFO, '[mChimpX] SEND: ' . print_r($mergeValues, 1));
            if ($debug) $modx->setPlaceholder('mChimpX.debug_send_'.$debug, $modx->toJSON(array(
                $listid,
                array('email' => $email),
                $mergeValues,
                $emailType,
                $doubleOptin,
                $updateExisting,
                $replaceInterests,
                $sendWelcome
            )));
            try {
                $result = $mc->lists->subscribe(
                    $listid,
                    array('email' => $email),
                    $mergeValues,
                    $emailType,
                    $doubleOptin,
                    $updateExisting,
                    $replaceInterests,
                    $sendWelcome
                );
            } catch (Mailchimp_List_AlreadySubscribed $e) {
                $hook->addError($ERROR_KEY, $modx->lexicon('mchimpx.error.alreadysubscribed'));
                return false;
            } catch (Mailchimp_Error $e) {
                $error_type = $mc->getHumanErrorType($e);
                $error_msg = $e->getMessage();
                $error_code = $e->getCode();
                $error = '[MailChimp Error! Code: ' . $error_code . '. Type: ' . $error_type . '. Message: ' . $error_msg;
                if ($log_errors) $modx->log(modX::LOG_LEVEL_ERROR, $error);
                $hook->addError($ERROR_KEY, $display_errors ? $error : $modx->lexicon('mchimpx.error.mailchimp_error'));
                return false;
            }
            if ($debug) {
                $modx->setPlaceholder('mChimpX.debug_receive_'.$debug, $modx->toJSON($result));
            }

            if ($modx->getOption('email', $result) != $email) {
                $error = sprintf('[mChimpX] ERROR: mismatched result email: %s', print_r($result, 1));
                if ($log_errors) $modx->log(modX::LOG_LEVEL_ERROR, $error);
                $hook->addError($ERROR_KEY, $display_errors ? $error : $modx->lexicon('mchimpx.error.system_error'));
                return false;
            }
            return true;
        } catch (Exception $e) {
            $error = sprintf('Exception processing mchimpxsubscribe snippet: %s', $e->getMessage());
            if ($log_errors) $modx->log(modX::LOG_LEVEL_ERROR, $error);
            $hook->addError($ERROR_KEY, $display_errors ? $error : $modx->lexicon('mchimpx.error.system_error'));
            return false;
        }
    }
}
return parse_mchimpx($modx, $hook, $scriptProperties);
