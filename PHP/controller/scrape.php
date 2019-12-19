<?php

use Goutte\Client;

class scraper {
    
    public function getImageUrl($sku){
        $client = new Client();
        $url = "https://www.acehardware.com/search?query=$sku";
            
        $crawler = $client->request('GET', $url);
        if($crawler->filter('img.mz-img-zoom')){
            $results = $crawler->filter('img.mz-img-zoom')->eq(0)->attr('src');
            return $results;
        } else {
            return false;
        }
        
    }

}


// $results = getImageUrl('https://www.acehardware.com/search?query=1000117');

