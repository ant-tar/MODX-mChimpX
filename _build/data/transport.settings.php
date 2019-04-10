<?php
/* @var modX $modx */

$s = array(
    'mcApiKey' => '',
    'mcListId' => '',
);

$settings = array();

foreach ($s as $key => $value) {
    if (is_string($value) || is_int($value)) { $type = 'textfield'; }
    elseif (is_bool($value)) { $type = 'combo-boolean'; }
    else { $type = 'textfield'; }

    $area = 'Default';
    $settings['mchimpx.'.$key] = $modx->newObject('modSystemSetting');
    $settings['mchimpx.'.$key]->set('key', 'mchimpx.'.$key);
    $settings['mchimpx.'.$key]->fromArray(array(
        'value' => $value,
        'xtype' => $type,
        'namespace' => 'mchimpx',
        'area' => $area
    ));
}

return $settings;

?>
