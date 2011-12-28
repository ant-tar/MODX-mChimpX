<?php
/**
 * Description:  Array of snippet objects for mChimpX package
 * @package mchimpx
 * @subpackage build
 */

if (!function_exists('getSnippetContent')) {
    function getSnippetContent($filename) {
        $o = file_get_contents($filename);
        $o = str_replace('<?php','',$o);
        $o = str_replace('?>','',$o);
        $o = trim($o);
        return $o;
    }
}
$snippets = array();

$snippets[1]= $modx->newObject('modSnippet');
$snippets[1]->fromArray(array(
    'id' => 1,
    'name' => 'mChimpXSubscribe',
    'description' => 'Subscribe users to the Mailchimp mailing list',
    'snippet' => getSnippetContent($sources['source_core'].'/elements/snippets/mchimpxsubscribe.snippet.php'),
),'',true,true);
unset($properties);

return $snippets;

?>