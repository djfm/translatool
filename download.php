<?

@ini_set('display_errors','on');

require '../../config/config.inc.php';

require 'translatool.php';

$tt = new Translatool();

$csv = $tt->getAllKeys();

header('Content-Description: File Transfer');
header('Content-Type: application/text');

echo $csv;
