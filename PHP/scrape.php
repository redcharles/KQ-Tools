<?php

require __DIR__.'/vendor/autoload.php'; // Composer's autoloader

$client = \Symfony\Component\Panther\Client::createChromeClient();
$client->request('GET', 'https://api-platform.com'); // Yes, this website is 100% written in JavaScript
$client->clickLink('Support');

// Wait for an element to be rendered
$crawler = $client->waitFor('.support');

echo $crawler->filter('.support')->text();
$client->takeScreenshot('screen.png'); // Yeah, screenshot!