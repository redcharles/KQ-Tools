<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controller/api.php';
require __DIR__ . '/controller/scrape.php';
require __DIR__ . '/controller/gmail.php';


$emails     = new fetchEmails;
$scraper    = new scraper;

if($emails->downloadEmails()){
    $dir = 'feeds/';
    $itemArr = array();

    $folderContents = scandir($dir);
    if(array_search('all.csv', $folderContents) ){
        $csv = fopen($dir.'all.csv', 'r');
        
        while(! feof($csv) ){
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

        foreach($itemArr as $key => $value){
            if($key == 'Item Number'){
                continue;
            }
            $imageUrl = $scraper->getImageUrl($key);
            if(!$imageUrl) {
                $imageUrl = NULL;
            }
            echo "Item Number: " . $key . "\r\n"  . "Image URL: " . $imageUrl . "\r\n\r\n";
            //print_r($imageUrl);
            //exit;
        }
    }
}
