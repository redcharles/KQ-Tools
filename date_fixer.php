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

echo "Fixing updated dates: \n";
$dir = 'feeds/';
$itemArr = array();
$folderContents = scandir($dir);
$selectedCsv = (array_search('all.csv', $folderContents) ? 'all.csv' : 'daily.csv');
if (isset($selectedCsv)) {
    $csv = fopen($dir . $selectedCsv, 'r');
    while (!feof($csv)) {
        $csvArr             = fgetcsv($csv);
        $item               = new stdClass();
        $item->Desc         = $csvArr[1];
        $item->Cat          = $csvArr[10];
        $item->SubCat       = $csvArr[11];
        $item->Vendor       = $csvArr[13];
        $item->RetailPrice  = $csvArr[9];
        $item->PromoPrice   = $csvArr[17];
        $itemArr[$csvArr[0]] = $item;
    }
    fclose($csv);
    
    foreach ($itemArr as $key => $value) {    
    
        echo "Updating SKU: $key \n";

        $date = date('Y-m-d H:i:s');
        $key = (string) $key;
        $updateSQL = "UPDATE products SET date_updated=NOW() WHERE SKU=':sku' ";
        $db->query($updateSQL);

        $db->bind(':sku', $key);
        
        $caught = false;
        try {
            $db->execute();
        } catch (Exception $e){
            $caught = true;
            echo $e->getMessage(), "\n";
        }
        
    } 
}