<?php
if (!$argv[1]) {
    throw new Exception('You must specify path to .txt file with issues!');
}
include_once 'Counter.php';
/** @var Counter $counter */
$counter = new Counter(file_get_contents($argv[1]));
var_dump($counter->getCount());