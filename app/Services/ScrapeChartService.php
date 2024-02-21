<?php

namespace App\Services;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class ScrapeChartService
{
    protected $crawler;

    public function __construct(protected $browser = null)
    {
        $this->browser = new HttpBrowser(HttpClient::create());
    }

    public static function make()
    {
        return app(ScrapeChartService::class);
    }

    public function scrapePages($url)
    {
        $page = 1;
        $pages = 5;

        $podcasts = [];

        while($page < $pages)
        {
            $scrapedPodcasts = $this->setCrawler($url . '?page=' . $page)->scrape();

            if (! count($scrapedPodcasts)) {
                // dd($page, $scrapedPodcasts);
            }

            $podcasts = array_merge($podcasts, $scrapedPodcasts);
            $page++;
        }

        return $podcasts;
    }

    public function setCrawler($url)
    {
        $this->crawler = $this->browser->request('GET', $url);

        return $this;
    }

    public function scrape()
    {
        return $this->crawler->filter('tr')->each(function (Crawler $node, $i) {
            // Extract the rank and title
            $rank = trim($node->filter('td')->eq(0)->filter('.f2')->text());
            
            // Check if the title exists
            $titleNode = $node->filter('.title');
            $title = $titleNode->count() ? trim($titleNode->text()) : '';
    
            // Check if the author and network exists
            $authorAndNetworkNode = $node->filter('td')->eq(2)->filter('.silver');
            $authorAndNetwork = $authorAndNetworkNode->count() ? trim($authorAndNetworkNode->text()) : '';
            
            // Check if the link exists
            $linkNode = $node->filter('.title a');
            $link = $linkNode->count() ? $linkNode->attr('href') : '';

            // Check if the image exists and extract the URL
            $imageNode = $node->filter('td')->eq(1)->filter('img');
            if ($imageNode->count()) {
                $imageUrl = $imageNode->attr('data-src') ? $imageNode->attr('data-src') : $imageNode->attr('src');
            } else {
                $imageUrl = '';
            }

            return [
                'rank' => $rank,
                'title' => $title,
                'author' => $authorAndNetwork,
                'link' => $link,
                'image' => $imageUrl
            ];
        });
    }

    public function getRssFeed($url)
    {
        $this->setCrawler($url);
        
        return $this->crawler->filter('a')->reduce(function (Crawler $node) {
            return str_contains($node->text(), 'RSS feed');
        })->attr('href');
    }
}