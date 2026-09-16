<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\Response;

/** Both language versions of the public pages, with hreflang alternates. */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $pages = [
            ['route' => 'landing', 'priority' => '1.0', 'freq' => 'weekly'],
            ['route' => 'quiz', 'priority' => '0.8', 'freq' => 'monthly'],
        ];

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml"/>');

        foreach ($pages as $page) {
            foreach (Locale::supported() as $locale) {
                $name = Locale::routeName($page['route'], $locale);

                if (! app('router')->has($name)) {
                    continue;
                }

                $url = $xml->addChild('url');
                $url->addChild('loc', htmlspecialchars(route($name)));
                $url->addChild('changefreq', $page['freq']);
                $url->addChild('priority', $page['priority']);

                foreach (Locale::supported() as $alternate) {
                    $altName = Locale::routeName($page['route'], $alternate);

                    if (! app('router')->has($altName)) {
                        continue;
                    }

                    $link = $url->addChild('xhtml:link', null, 'http://www.w3.org/1999/xhtml');
                    $link->addAttribute('rel', 'alternate');
                    $link->addAttribute('hreflang', $alternate);
                    $link->addAttribute('href', route($altName));
                }
            }
        }

        return response((string) $xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }
}
