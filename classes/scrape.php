<?php

use Goutte\Client;

class scraper
{

    public function getImageUrl($sku)
    {
        $client = new Client();
        $aceUrl    = "https://www.acehardware.com/search?query=$sku";
        $epicoreURL   = "http://media.epicor-inet.com/coop/ace/image/$sku.jpg";

        $crawler = $client->request('GET', $aceUrl);
        $altCrawl = $client->request('GET', $epicoreURL);
        // Check to make sure we get an image from acehardware.com
        if ($crawler->filter('img.mz-img-zoom')->count() > 0) {
            $results = $crawler->filter('img.mz-img-zoom')->eq(0)->attr('src');
            return $results;
        }
        if ($altCrawl->filter('img')->count() > 0) {
            $results = $crawler->filter('img')->eq(0)->attr('src');
            return $results;
        }
        if ($crawler->filter('img.mz-img-zoom')->count() === 0 && $altCrawl->filter('img')->count() === 0) {
            return false;
        }
    }

    public function getProductDescription($sku){
        $client     = new Client();
        $aceUrl     = "https://www.acehardware.com/search?query=$sku";
        $crawler    = $client->request('GET', $aceUrl);

        if($crawler->filter('#productDetailsContainer')->count() > 0){
            $results = $crawler->filter('#productDetailsContainer')->html();
            return $results;  
        } 
        return false;
    }

    public function getProductName($sku){
        $client = new Client();
        $aceUrl = "https://www.acehardware.com/search?query=$sku";
        $crawler = $client->request('GET', $aceUrl);

        if($crawler->filter('#page-content > div:nth-child(12) > div.mz-productActions-wrap.col-xs-12.col-lg-5.mz-mobile-center > div.mz-productHeader.mz-mobile-center.hidden-sm.hidden-xs > h1')->count() > 0){
            $results = $crawler->filter('#page-content > div:nth-child(12) > div.mz-productActions-wrap.col-xs-12.col-lg-5.mz-mobile-center > div.mz-productHeader.mz-mobile-center.hidden-sm.hidden-xs > h1')->text();
            return $results;
        }

        return false;
    }

    public function compareData($obj1, $obj2)
    {
        $obj1 = (array) $obj1;
        $obj2 = (array) $obj2;
        unset($obj1['id']);
        unset($obj1['date_created']);
        unset($obj1['date_updated']);

        $differenceCount = array_diff($obj1, $obj2);

        if (count($differenceCount) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function downloadImage($url)
    {
        $mimeTypeArr = [
            '2' => '.jpeg',
            '3' => '.png'
        ];
        $urlArr = explode('?', $url);
        $url = 'https:' . $urlArr[0];
        $mimeType =  exif_imagetype($url);
        $extension =  $mimeTypeArr[$mimeType];

        // Initialize the cURL session 
        $ch = curl_init($url);
        // Inintialize directory name where 
        // file will be save 
        $dir = 'images/';
        // Use basename() function to return 
        // the base name of file  
        $file_name = basename($url);
        // Save file into file location 
        $save_file_loc = $dir . $file_name . $extension;
        // Open file  
        $fp = fopen($save_file_loc, 'wb');
        // It set an option for a cURL transfer 
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // Perform a cURL session 
        curl_exec($ch);
        // Closes a cURL session and frees all resources 
        curl_close($ch);
        // Close file 
        fclose($fp);
        
        $filePath = "http://newkq.reddresssolutions.com/".$file_name.$extension;
        return $filePath;        
    }
}
