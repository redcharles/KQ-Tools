<?php

/** 
 * 
 * Download Latest Emails & Update Database
 * 
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controller/api.php';
require __DIR__ . '/controller/scrape.php';
require __DIR__ . '/controller/gmail.php';
require_once __DIR__ . '/controller/db.php';


$emails     = new fetchEmails;
$scraper    = new scraper;
$db         = new Database;
$api        = new API;

if ($emails->downloadEmails()) {
    $dir = 'feeds/';
    $itemArr = array();

    $folderContents = scandir($dir);
    if (array_search('all.csv', $folderContents)) {
        $csv = fopen($dir . 'all.csv', 'r');

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
            $imageUrl = $scraper->getImageUrl($key);
            if (!$imageUrl) {
                $imageUrl = NULL;
            }
            $value->imageURL = $imageUrl;
            $value->SKU = $key;
            $skuCheck = "SELECT * FROM products WHERE SKU = :sku";

            $db->query($skuCheck);

            $db->bind(':sku', $key);
            
            $db->execute();
            if ($db->rowCount() === 0) {
                $date = date('Y-m-d H:i:s');
                echo "Product added \n";
                $sql = "INSERT INTO products (Category, Description, ImageURL, PromoPrice, RetailPrice, SKU, Subcategory, Vendor, date_created) VALUES (:category, :description, :imageURL, :promo, :retail, :sku, :subcat, :vendor, :date)";
                $db->query($sql);
                $db->bind(':category', $value->Cat);
                $db->bind(':description', $value->Desc);
                $db->bind(':imageURL', $imageUrl);
                $db->bind(':promo', $value->PromoPrice);
                $db->bind(':retail', $value->RetailPrice);
                $db->bind(':sku', $key);
                $db->bind(':subcat', $value->SubCat);
                $db->bind(':vendor', $value->Vendor);
                $db->bind(':date', $date);
                $db->execute();
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
                    $db->execute();
                    echo "Product updated \n";
                } else {
                    echo "Product Data Same \n";
                }
                
            }
        }
    }
}

