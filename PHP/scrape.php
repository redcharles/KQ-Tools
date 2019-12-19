<?php 

include ('vendor/autoload.php');

$url = "https://www.acehardware.com/search?query=1000117";

$client = \Symfony\Component\Panther\Client::createChromeClient();

$crawler = $client->request('GET', $url);

$fullPageHtml = $crawler->html();

// $imageFilter = $crawler->filter('.zoomWindow')->css();

print_r($fullPageHtml);



