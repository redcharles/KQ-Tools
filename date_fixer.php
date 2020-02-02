<?php 

/** 
 * 
 * Download Latest Emails & Update Database
 * 
 */
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/classes/api.php';
require __DIR__ . '/classes/scrape.php';
require __DIR__ . '/classes/gmail.php';
require_once __DIR__ . '/classes/db.php';
require_once __DIR__ . '/category.php';
require_once __DIR__ . '/update.php';

$emails     = new fetchEmails;
$scraper    = new scraper;
$db         = new Database;
$api        = new API;
$cat        = new Categories;
$woo        = new WooMethods;

$SQL = "SELECT * FROM products WHERE `ImageUrl` IS NULL";

$db->query($SQL);
$db->execute();

$results = $db->resultSet();

foreach($results AS $k => $v){
    $fetchData = $scraper->fetchAll('36799');
    print_r($fetchData);
    exit;
    // if($fetchData != false){
    //     $imageUrl = $fetchData['image'];
    //     $prodDescription = $fetchData['description'];
    //     $getName = $fetchData['name'];

    // }
    
}