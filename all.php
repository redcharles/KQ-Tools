<?php 
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

function secondsToTime($s)
{
    $h = floor($s / 3600);
    $s -= $h * 3600;
    $m = floor($s / 60);
    $s -= $m * 60;
    return $h.':'.sprintf('%02d', $m).':'.sprintf('%02d', $s);
}


$starttime = microtime(true);


// If set to true, parse all.csv and insert raw values into db
if(false){
    $dir = 'feeds/';
    $itemArr = array();
    $folderContents = scandir($dir);
    $selectedCsv = 'all.csv';

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
    $tempArr = [];

    foreach ($itemArr as $key => $value) {    
        
        if ($key == 'Item Number') {
            continue;
        }
        // Get this data later on, after insert
        $getImageUrl = $key;
        $getDesc = $key;
        $getName = $key;

        $imageUrl = NULL;
        $prodDescription = NULL;
        
        $value->imageURL = $imageUrl;
        $value->SKU = $key;

        
            $date = date('Y-m-d H:i:s');
            $sql = "INSERT INTO products (Category, Description, ProductDescription, ImageURL, PromoPrice, RetailPrice, SKU, Subcategory, Vendor, date_created) VALUES (:category, :description, :prodDescription, :imageURL, :promo, :retail, :sku, :subcat, :vendor, :date)";
            $db->query($sql);
            $db->bind(':category', $value->Cat);
            $db->bind(':description', $value->Desc);    
            $db->bind(':prodDescription', $prodDescription);
            $db->bind(':imageURL', $imageUrl);
            $db->bind(':promo', $value->PromoPrice);
            $db->bind(':retail', $value->RetailPrice);
            $db->bind(':sku', $key);
            $db->bind(':subcat', $value->SubCat);
            $db->bind(':vendor', $value->Vendor);
            $db->bind(':date', $date);
            $caught = false;
            try {
                $db->execute();
            } catch (Exception $e){
                $caught = true;
                $tempArr['error'][] = $e->getMessage();
            }
    }
}

// Update data in database with correct data
$getProducts = "SELECT * FROM products";
$db->query($getProducts);
$results = $db->resultSet();
$count = 0;
foreach($results as $key => $value){
    echo "Row $count \n";
    $count++;
    $sku = $value->SKU;
    $scraperData = $scraper->fetchAll($sku);
    if($scraperData != false){
        $date = date('Y-m-d H:i:s');
        $updateSQL = "UPDATE products SET Description=:description, ImageURL=:imageURL, ProductDescription=:prodDesc, date_updated=:date WHERE SKU=:sku ";
        $db->query($updateSQL);
        $db->bind(':description', $scraperData['name']);
        $db->bind(':prodDesc', $scraperData['description']);
        $db->bind(':imageURL', $scraperData['image']);
        $db->bind(':sku', $sku);
        $db->bind(':date', $date);
        $caught = false;
        try {
            $db->execute();
        } catch (Exception $e){
            $caught = true;
            echo $e->getMessage(), "\n";
        }
        echo "Updating SKU: $sku \n";
    }
}


$endtime                        = microtime(true);
$timediff                       = $endtime - $starttime;
echo secondsToTime($timediff);