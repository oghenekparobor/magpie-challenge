<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

require '../vendor/autoload.php';

class Scrape
{
    private array $products = [];



    public function run(): void
    {
        // // Condition to stop the loop based on page content
        // $pageHasData = true;
        $page = 1;

        while (true) {
            $document = ScrapeHelper::fetchDocument('https://www.magpiehq.com/developer-challenge/smartphones/?page=' . $page);
            $productResult = $document->filter('.product');

            if ($productResult->count() == 0) {
                break;
            }

            for ($i = 0; $i < $productResult->count(); $i++) {
                $title =  $productResult->eq($i)->filter('.bg-white .text-blue-600 .product-name')->text();
                $image =  $productResult->eq($i)->filter('img')->attr('src');
                $capacity = $productResult->eq($i)->filter('.bg-white .text-blue-600 .product-capacity')->text();
                $title = $productResult->eq($i)->filter('.bg-white .text-blue-600 .product-name');
                $price = $productResult->eq($i)->filter('.bg-white div.my-8.block.text-center.text-lg')->count();
                $ava = $productResult->eq($i)->filter('div:contains("Availability:")')->count() != 0 ?  $productResult->eq($i)->filter('div:contains("Availability:")')->text() : '';
                $colors = $productResult->eq($i)->filter('.flex-wrap [data-colour]')->each(function (Crawler $node, $i) {
                    return $node->attr('data-colour');
                });
                $shippingCrawlerInfo = $productResult->eq($i)->filter('div:contains("Deliver"), div:contains("free shipping")');
                $deliveryInformation = [];

                foreach ($shippingCrawlerInfo as $shipInfo) {
                    $text = $shipInfo->textContent;

                    // Check for 'Deliver' or 'free shipping'
                    if (strpos($text, 'Deliver') !== false || strpos($text, 'free shipping') !== false) {

                        // Find and extract date
                        preg_match('/\b(?:\d{1,2}(?:st|nd|rd|th)?\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4})\b/', $text, $matches);

                        $deliveryInformation[] = [
                            'text' => $text,
                            'date' => isset($matches[0]) ? $matches[0] : null
                        ];
                    }
                }


                $products[] = (array(
                    'title' => $title,
                    'capacity' => $capacity,
                    'price' => $price,
                    'colours' => empty($colors) ? 0 : 1,
                    'availibityText' => $ava,
                    'isAvailable' => true,
                    'shippingText' => '',
                    'shippingDate' => '',
                    'imageUrl' => $image,
                ));
            }

            $page++;
        }



        // file_put_contents('output.json', json_encode($this->products));
    }
}

$scrape = new Scrape();
$scrape->run();
