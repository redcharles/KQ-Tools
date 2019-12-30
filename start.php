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
// $emails->downloadEmails()

function secondsToTime($s)
{
    $h = floor($s / 3600);
    $s -= $h * 3600;
    $m = floor($s / 60);
    $s -= $m * 60;
    return $h.':'.sprintf('%02d', $m).':'.sprintf('%02d', $s);
}

$starttime = microtime(true);
$rowCount = 0;
$tempArr = array();
if (true) {
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
            if ($key == 'Item Number') {
                continue;
            }
            $rowCount++;
            $getImageUrl = $scraper->getImageUrl($key);
            $getDesc = $scraper->getProductDescription($key);
            $getName = (!$scraper->getProductName($key) ? NULL : $scraper->getProductName($key));

            $imageUrl = ( !$getImageUrl ?  NULL : $getImageUrl  );
            $prodDescription = ( !$getDesc ? NULL : $getDesc );
            
            $value->imageURL = $imageUrl;
            $value->SKU = $key;
            $skuCheck = "SELECT * FROM products WHERE SKU = :sku";
            $db->query($skuCheck);
            $db->bind(':sku', $key);
            
            $db->execute();
            if ($db->rowCount() === 0) {
                $date = date('Y-m-d H:i:s');
                $sql = "INSERT INTO products (Category, Description, ProductDescription, ImageURL, PromoPrice, RetailPrice, SKU, Subcategory, Vendor, date_created) VALUES (:category, :description, :prodDescription, :imageURL, :promo, :retail, :sku, :subcat, :vendor, :date)";
                $db->query($sql);
                $db->bind(':category', $value->Cat);
                if(is_null($getName)){
                    $db->bind(':description', $value->Desc);
                } else {
                    $db->bind(':description', $getName);
                }
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
                if($caught === false){
                    $tempArr['AddedToDb'][] = $key;
                }
                
            } else {
                $results = $db->single();
                $isDifferent = $scraper->compareData($results, $value);
                if($isDifferent){
                    $date = date('Y-m-d H:i:s');
                    $updateSQL = "UPDATE products SET Category=:category, Description=:description, ImageURL=:imageURL, PromoPrice=:promo, RetailPrice=:retail, SKU=:sku, Subcategory=:subcat, Vendor=:vendor, date_updated=:date WHERE SKU=:sku ";
                    $db->query($updateSQL);
                    $db->bind(':category', $value->Cat);
                    $db->bind(':description', $value->Desc);
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
                    if($caught === false){
                        $tempArr['UpdatedInDb'][] = $key;
                    }

                } else {
                    $tempArr['DataSame'][] = $key;
                }
                
            }
        }
    }
    // Remove Parsed Files
    // foreach($folderContents as $key => $value){
    //     if($value == 'all.csv'){
    //         unlink($dir . $value);
    //     }
    //     if($value == 'daily.csv'){
    //         unlink($dir . $value);
    //     }
    // }    
}

$resultsObj = new stdClass();

$createCategories               = $cat->createCategories();
$batchAdd                       = $woo->batchAdd();
$updateProducts                 = $woo->updateProducts();
// Set Category Messages
$resultObj->newPrimCat          = $createCategories['newPrimCat'];
$resultObj->newSubCat           = $createCategories['newSubCat'];
// Set CSV Messages
$resultObj->csvErrors           = $tempArr['error'];
$resultObj->csvUpdated          = $tempArr['UpdatedInDb'];
$resultObj->csvSame             = $tempArr['DataSame'];
$resultObj->csvNew              = $tempArr['AddedToDb'];
// Set New Product Messages
$resultsObj->NewProductCount    = $batchAdd['count'];
// Set Updated Product Messages
$resultsObj->UpdateCount        = $updateProducts['UpdateCount'];
$resultsObj->UpdateList         = $updateProducts['UpdatedList'];

$endtime                        = microtime(true);
$timediff                       = $endtime - $starttime;
$resultsObj->Time               = secondsToTime($timediff);

return $resultsObj;
