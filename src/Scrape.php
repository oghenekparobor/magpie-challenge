<?php

namespace App;

use Exception;
use Symfony\Component\DomCrawler\Crawler;

require '../vendor/autoload.php';

class Scrape
{
    private array $products;

    public function __construct()
    {
        $this->products = [];
    }

    public function run(): void
    {
        $page = 1;

        // filter selectors
        $productSelector = '.product';

        while (true) {
            $document = ScrapeHelper::fetchDocument(ScrapeHelper::HOST . '/smartphones/?page=' . $page);

            if ($document == null) {
                exit();
            }

            $productResult = $document->filter($productSelector);

            if ($productResult->count() == 0) {
                break;
            }

            $productResult->each(function (Crawler $product) {
                $titleSelector = '.bg-white .text-blue-600 .product-name';
                $imageSelector = 'img';
                $capacitySelector = '.bg-white .text-blue-600 .product-capacity';
                $priceSelector = '.bg-white div.my-8.block.text-center.text-lg';
                $availabilitySelector = '.bg-white  div.my-4.text-sm.block:contains("Availability:")';
                $colorSelector = '.flex-wrap [data-colour]';
                $shippingInfoSelector = '.bg-white div.my-4.text-sm.block.text-center:contains("Delivery from"), .bg-white div.my-4.text-sm.block.text-center:contains("Delivers"), .bg-white div.my-4.text-sm.block.text-center:contains("Order within"), .bg-white div.my-4.text-sm.block.text-center:contains("Free Delivery"), .bg-white div.my-4.text-sm.block.text-center:contains("Free shipping"), .bg-white div.my-4.text-sm.block.text-center:contains("Available"), .bg-white div.my-4.text-sm.block.text-center:contains("Unavailable"), .bg-white div.my-4.text-sm.block.text-center:contains("Delivery by")';


                $title = $product->filter($titleSelector)->text();
                $image = $product->filter($imageSelector)->attr('src');
                $capacity = $product->filter($capacitySelector)->text();
                $price = $product->filter($priceSelector)->text();
                $availabilityText = $product->filter($availabilitySelector)->count() != 0 ? $product->filter($availabilitySelector)->text() : '';

                $colors = $product->filter($colorSelector)->each(function (Crawler $node, $i) {
                    return $node->attr('data-colour');
                });

                $shippingCrawlerInfo = $product->filter($shippingInfoSelector);

                if ($shippingCrawlerInfo->count() != 0) {
                    $shippingInfo = ScrapeHelper::extractDateAndText($shippingCrawlerInfo->text());
                } else {
                    $shippingInfo = ['date' => '', 'text' => ''];
                }

                $image = ScrapeHelper::imagePath($image, ScrapeHelper::HOST);

                foreach ($colors as $color) {
                    $productObj = new Product();

                    $productObj->title = $title;
                    $productObj->capacityMB = ScrapeHelper::getCapacityInMB($capacity);
                    $productObj->price = $price;
                    $productObj->colour = $color;
                    $productObj->availabilityText = str_replace("Availability", "", ($availabilityText));
                    $productObj->isAvailable = strpos(strtolower($availabilityText), 'in stock') !== false ? 'true' : 'false';
                    $productObj->shippingText = $shippingInfo['text'];
                    $productObj->shippingDate = $shippingInfo['date'];
                    $productObj->imageUrl = $image;

                    $this->products[] = $productObj;
                }
            });

            $page++;
        }


        try {
            $jsonData = json_encode(ScrapeHelper::removeDuplicateProducts($this->products), JSON_PRETTY_PRINT);

            if ($jsonData !== false) {
                file_put_contents('output.json', $jsonData);

                echo 'Data written to output.json';
            } else {
                throw new Exception('Error encoding data to JSON');
            }
        } catch (Exception $e) {
            error_log('Error writing to file: ' . $e->getMessage());
            echo 'Error writing to file: ' . $e->getMessage();
        }
    }
}

$scrape = new Scrape();
$scrape->run();
