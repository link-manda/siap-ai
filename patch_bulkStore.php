<?php

$attribute = 'archives.0.classification_id';
preg_match('/archives\.(\d+)\.classification_id/', $attribute, $matches);
$index = $matches[1] ?? null;
var_dump($index);
