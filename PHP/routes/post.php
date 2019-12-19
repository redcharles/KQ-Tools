<?php 

/** 
 * 
 * Handles POST request from Python component
 * 
 */


$api = new API;

if($_POST){
    $api->POST($_POST);
}

