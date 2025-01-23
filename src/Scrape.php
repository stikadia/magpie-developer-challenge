<?php

namespace App;

require 'vendor/autoload.php';

class Scrape
{
    private array $products = [];

    public function run(): void
    {
        
        $document = ScrapeHelper::fetchDocument('https://www.magpiehq.com/developer-challenge/smartphones');
        
        $finalProducts = [];
        $finalProducts = ScrapeHelper::fetchProducts($document);
        
        //fetch pagination
        $pages = $document->filter("#pages .flex-wrap a")->each(function($link){
            $classAttribute = $link->attr('class');
            if (strpos($classAttribute, 'active') === false) {
                return ScrapeHelper::cleanURL($link->attr('href'));
            }
            return null;
        });

        //fetch products from other pages
        foreach ($pages as $page) {
            if ($page) {
                $document = ScrapeHelper::fetchDocument($page);
                $finalProducts = array_merge($finalProducts, ScrapeHelper::fetchProducts($document));
            }
        }

        //check unique products with title and colour as unique key
        $this->products = ScrapeHelper::getUniqueProducts($finalProducts);
        
        file_put_contents('output.json', json_encode($this->products,true));
    }
}

$scrape = new Scrape();
$scrape->run();
