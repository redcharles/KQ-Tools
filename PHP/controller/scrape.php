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
        if($crawler->filter('img.mz-img-zoom')->count() === 0 && $altCrawl->filter('img')->count() === 0){
            return false;
        }
    }
}


// $results = getImageUrl('https://www.acehardware.com/search?query=1000117');
