<?php

namespace App;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeHelper
{
    public static function fetchDocument(string $url): Crawler
    {
        $client = new Client();

        try{
            $response = $client->get($url);
            return new Crawler($response->getBody()->getContents(), $url);
        } catch(Exception $e)
        {
            //we can also do error_log to log the errors in file
            die("An error occurred while processing the URL: ".$url);
        }
    }

    /**
     * getFormatedDate
     *
     * @param  mixed $text
     * @return string
     */
    public static function getFormatedDate(string $text): string
    {
        $pattern = '/(?:by|from)?\s?(\d{1,2}(?:st|nd|rd|th)? \w+ \d{4}|\d{4}-\d{2}-\d{2}|tomorrow)/i';
        preg_match($pattern, $text, $match);

        if ($match) {
            $dateText = $match[1];

            // Handle the "tomorrow" case
            if (strtolower($dateText) == 'tomorrow') {
                $dateTime = new DateTime('tomorrow');
            }
            // Handle the "Y-m-d" format case
            elseif (strpos($dateText, '-') !== false) {
                $dateTime = DateTime::createFromFormat('Y-m-d', $dateText);
            }
            // Handle the "d M Y" format case (including ordinal suffixes like st, nd, rd, th)
            else {
                // Remove ordinal suffixes (st, nd, rd, th)
                $dateText = preg_replace('/(\d+)(st|nd|rd|th)/', '$1', $dateText);
                $dateTime = DateTime::createFromFormat('d M Y', $dateText);
            }

            // Check if DateTime object was successfully created
            if ($dateTime === false) {
                return "";  
            }

            return $dateTime->format('Y-m-d');
        }

        return "";
    }



    /**
     * getCapacity
     *
     * @param  mixed $value
     * @return string
     */
    public static function getCapacity(string $value): string
    {
        $capacity = str_replace(" ", "", $value);
        if (strpos($capacity, "GB") !== false) {
            $number = floatval(trim(str_replace('GB', '', $capacity)));
            $capacity = ($number * 1024) . "MB";
        }

        return $capacity;
    }

    /**
     * cleanPrice
     *
     * @param  mixed $text
     * @return string
     */
    public static function cleanPrice(string $text): string
    {
        return str_replace("£", "", $text);
    }

    /**
     * cleanURL
     *
     * @param  mixed $text
     * @return string
     */
    public static function cleanURL(string $text): string
    {
        return str_replace('../', 'https://www.magpiehq.com/developer-challenge/', $text);
    }

    /**
     * fetchProducts
     *
     * @param  mixed $document
     * @return array
     */
    public static function fetchProducts($document): array
    {
        $result = [];
        $document->filter("div > .flex-wrap > .product")->each(function ($product_div) use (&$result) {

            $avilable = explode(": ", $product_div->filter('.my-4.text-sm')->first()->text());
            $colors = $product_div->filter('.px-2 > span')->each(function ($item) {
                return $item->attr("data-colour");
            });

            foreach ($colors as $value) {
                
                $title = $product_div->filter('h3')->text();
                
                //if product title is not available then continue to next product
                if(trim($title) == '') continue;

                $product["title"] = $title;
                $product["price"] = self::cleanPrice($product_div->filter('.my-8.text-center')->text());

                $product["imageUrl"] = self::cleanURL($product_div->filter('img')->attr("src"));
                $product["capacityMB"] = self::getCapacity($product_div->filter('h3 > .product-capacity')->text());
                $product["colour"] = $value;
                $product["availabilityText"] = isset($avilable[1]) ? trim($avilable[1]) : "Out of Stock";
                $product["isAvailable"] = (isset($avilable[1]) && trim($avilable[1]) == "Out of Stock") ? false : true;

                $product["shippingDate"] = $product["shippingText"] = '';
                if ($product_div->filter('.my-4.text-sm')->count() > 1) {
                    $product["shippingText"] = $product_div->filter('.my-4.text-sm')->last()->text();
                    $product["shippingDate"] = self::getFormatedDate($product_div->filter('.my-4.text-sm')->last()->text());
                }

                $result[] = $product;
            }
        });
        return $result;
    }


    /**
     * getUniqueProucts
     *
     * @param  mixed $products
     * @return array
     */
    public static function getUniqueProducts(array $products): array
    {
        $finalResult = [];
        $check = [];

        foreach ($products as $product) {
            $key = $product['title'] . '_' . $product['colour'];

            if (!isset($check[$key])) {
                $finalResult[] = $product;
                $check[$key] = true;
            }
        }
        return $finalResult;
    }
}
