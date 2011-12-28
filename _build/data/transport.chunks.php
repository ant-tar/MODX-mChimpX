<?php
/**
 * Description: Array of chunk objects for mChimpX package
 * @package mchimpx
 * @subpackage build
 */

$chunks = array();

$chunks[1]= $modx->newObject('modChunk');
$chunks[1]->fromArray(array(
    'id' => 1,
    'name' => 'mChimpXSubscribe',
    'description' => 'A default example of how to use mChimpXSubscribe snippet',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/mchimpxsubscribe.chunk.tpl'),
    'properties' => '',
),'',true,true);

return $chunks;

?>