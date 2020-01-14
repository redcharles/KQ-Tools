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
//$emails->downloadEmails()

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
if ($emails->downloadEmails()) {
    echo "Updating DB: \n";
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
            $key = (string) $key;
            if ($key == 'Item Number') {
                continue;
            }
            $rowCount++;
            $imageUrl           = null;
            $getName            = null;
            $prodDescription    = null;

            $fetchData = $scraper->fetchAll($key);

            if($fetchData != false){
                $imageUrl = $fetchData['image'];
                $prodDescription = $fetchData['description'];
                $getName = $fetchData['name'];
            }
            
            
            $value->imageURL = $imageUrl;
            $value->SKU = $key;
            $skuCheck = "SELECT * FROM products WHERE SKU = :sku";
            $db->query($skuCheck);
            $db->bind(':sku', $key);
            $db->execute();

            echo "Checking SKU: $key \n";

            if ($db->rowCount() === 0) {
                echo "Inserting SKU: $key \n";
                
                $sql = "INSERT INTO products (Category, Description, ProductDescription, ImageURL, PromoPrice, RetailPrice, SKU, Subcategory, Vendor, date_created) VALUES (:category, :description, :prodDescription, :imageURL, :promo, :retail, :sku, :subcat, :vendor, NOW())";
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
                
                $caught = false;
                try {
                    $db->execute();
                } catch (Exception $e){
                    $caught = true;
                    $tempArr['error'][] = $e->getMessage();
                    echo $e->getMessage(), "\n";
                }
                if($caught === false){
                    $tempArr['AddedToDb'][] = $key;
                }
                
            } else {
                echo "Updating SKU: $key \n";
                $results = $db->single();
                $isDifferent = $scraper->compareData($results, $value);
                if($isDifferent){
                    $key = (string) $key;
                    echo "Data different \n";
                    
                    $updateSQL = "UPDATE products 
                                    SET PromoPrice=:promo, RetailPrice=:retail, date_updated=NOW() 
                                    WHERE SKU=:sku ";
                    $db->query($updateSQL);
                    $db->bind(':promo', $value->PromoPrice);
                    $db->bind(':retail', $value->RetailPrice);
                    $db->bind(':sku', $key);
                    
                    $caught = false;
                    echo "Attempting update: \n";
                    try {
                        $db->execute();
                    } catch (Exception $e){
                        $caught = true;
                        $tempArr['error'][] = $e->getMessage();
                        echo $e->getMessage(), "\n";
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
echo "Creating/Updating Categories: \n";
$createCategories               = $cat->createCategories();
echo "Starting to add products.. \n";
$batchAdd                       = $woo->batchAdd();
echo "Updating products... \n";
$updateProducts                 = $woo->updateProducts();
// Set Category Messages
$resultsObj->newPrimCat          = (isset($createCategories['newPrimCat']) ? json_encode($createCategories['newPrimCat']) : json_encode("None"));
$resultsObj->newSubCat           = (isset($createCategories['newSubCat'])  ? json_encode($createCategories['newSubCat']) : json_encode("None") );
// Set CSV Messages
$resultsObj->csvErrors           = (isset($tempArr['error'])         ? json_encode($tempArr['error'])         : json_encode("None"));
$resultsObj->csvUpdated          = (isset($tempArr['UpdatedInDb'])   ? json_encode($tempArr['UpdatedInDb'])   : json_encode("None"));
$resultsObj->csvSame             = (isset($tempArr['DataSame'])      ? json_encode($tempArr['DataSame'])      : json_encode("None"));
$resultsObj->csvNew              = (isset($tempArr['AddedToDb'])     ? json_encode($tempArr['AddedToDb'])     : json_encode("None"));
// Set New Product Messages
$resultsObj->NewProductCount    = (isset($batchAdd['count'])        ? json_encode($batchAdd['count'])         : json_encode(0));
// Set Updated Product Messages
$resultsObj->UpdateCount        = (isset($updateProducts['UpdateCount']) ? json_encode($updateProducts['UpdateCount'])   : json_encode("None"));
$resultsObj->UpdateList         = (isset($updateProducts['UpdatedList']) ? json_encode($updateProducts['UpdatedList'])   : json_encode("None"));

$endtime                        = microtime(true);
$timediff                       = $endtime - $starttime;
$resultsObj->Time               = secondsToTime($timediff);
$resultsObj->DateRan            = date('Y-m-d H:i:s');






$sql = "INSERT INTO logs (newCount, updateCount, csvErrors, csvUpdated, csvSame, csvNew, updateList, time, dateRan) 
        VALUES (:newCount, :updateCount, :csvErrors, :csvUpdated, :csvSame, :csvNew, :updateList, :time, :dateRan)";
$db->query($sql);
$db->bind(':newCount', $resultsObj->NewProductCount);
$db->bind(':updateCount', $resultsObj->UpdateCount);
$db->bind(':csvErrors', $resultsObj->csvErrors);
$db->bind(':csvUpdated', $resultsObj->csvUpdated);
$db->bind(':csvSame', $resultsObj->csvSame);
$db->bind(':csvNew', $resultsObj->csvNew);
$db->bind(':updateList', $resultsObj->UpdateList);
$db->bind(':time', $resultsObj->Time);
$db->bind(':dateRan', $resultsObj->DateRan);

try {
    $db->execute();
} catch(Exception $e){
    echo $e->getMessage();
}
