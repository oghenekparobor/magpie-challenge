<?php

namespace App;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeHelper
{
    const HOST = "https://www.magpiehq.com/developer-challenge";

    public static function fetchDocument(string $url): Crawler
    {
        $client = new Client();

        $response = $client->get($url);

        return new Crawler($response->getBody()->getContents(), $url);
    }

    public static function extractDateAndText($inputString): array
    {
        $pattern = '/((\d{4}-\d{2}-\d{2})|(\b(?:(?:\d{1,2}(?:st|nd|rd|th)?\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4})|(?:\d{1,2}(?:st|nd|rd|th)?\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec))|(?:(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2}(?:st|nd|rd|th)?(?:,\s+\d{4})?))))\b/';
        preg_match($pattern, $inputString, $matches);

        $date = isset($matches[0]) ? $matches[0] : '';
        $text = trim(str_replace($date, '', $inputString));

        return ['date' => $date, 'text' => $text];
    }

    public static function imagePath($imageUrl, $hostname)
    {
        // Remove relative path
        $imageUrl = str_replace('../', '', $imageUrl);

        // Attaches the hostname
        $imageUrl = $hostname . '/' . $imageUrl;

        return $imageUrl;
    }

    public static function getCapacityInMB($capacity)
    {
        // Check if the capacity is specified in GB
        if (stripos($capacity, 'GB') !== false) {
            // Extract the numeric part
            $numericPart = (float)filter_var($capacity, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            // Convert GB to MB
            $capacityInMB = $numericPart * 1024;

            // Format the result with commas
            return number_format($capacityInMB) . 'MB';
        }

        // If already in MB or not recognized, return [$capacity]
        return $capacity;
    }

    public static function removeDuplicateProducts($products)
    {
        $uniqueProducts = [];

        foreach ($products as $product) {
            $key = $product->title . '_' . $product->colour;
            if (!isset($uniqueProducts[$key])) {
                $uniqueProducts[$key] = $product;
            }
        }

        return array_values($uniqueProducts);
    }
}
