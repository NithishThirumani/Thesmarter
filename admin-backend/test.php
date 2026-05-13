<?php
include 'connection.php';
include 'config.php';

$jsonString = file_get_contents('test.json');

$jsonArray = json_decode($jsonString,true);
foreach ($jsonArray as $key => $value)
{
    echo $key."=".$value;
    exit;
}