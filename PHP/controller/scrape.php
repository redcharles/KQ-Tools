<?php

use Goutte\Client;

class scraper {
    
    public function getImageUrl($sku){
        $client = new Client();
        $url    = "https://www.acehardware.com/search?query=$sku";
        $url2   = "";
            
        $crawler = $client->request('GET', $url);

        
        // Check to make sure we get an image from acehardware.com
        if($crawler->filter('img.mz-img-zoom')->count() > 0){
            $results = $crawler->filter('img.mz-img-zoom')->eq(0)->attr('src');
            return $results;
        } else {
            // TODO: change $url variable to check for image at the other location

            return false;
        }
        
    }

}


// $results = getImageUrl('https://www.acehardware.com/search?query=1000117');

