<?php

declare(strict_types=1);

class NerdbergPlenumBridge extends BridgeAbstract
{
    const NAME = 'Nerdberg Plenum';
    const URI = 'https://wiki.nerdberg.de/Plenen';
    const DESCRIPTION = 'Returns recent Plenum meetings of the Nerdberg hackspace.';
    const MAINTAINER = 'Schabi';
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $dom = getSimpleHTMLDOM(self::URI);
        $section = $dom->find('div.mw-parser-output', 0);
        if (!$section) {
            throw new \Exception('Unable to find content section on ' . self::URI);
        }

        foreach ($section->find('ul li') as $li) {
            $a = $li->find('a', 0);
            if (!$a) {
                continue;
            }

            $href = $a->href;
            $uri = urljoin('https://wiki.nerdberg.de', $href);
            $linkText = trim($a->plaintext);

            // Extract date (YYYY-MM-DD) from "Plenum:2026-04-29"
            $timestamp = null;
            if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $linkText, $m)) {
                $timestamp = mktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
            }

            // Extract meeting number "#109" from the li text
            $liText = trim($li->plaintext);
            $number = null;
            if (preg_match('/#(\d+)/', $liText, $m)) {
                $number = $m[1];
            }

            $title = $linkText;
            if ($number !== null) {
                $title = sprintf('Plenum #%s – %s', $number, str_replace('Plenum:', '', $linkText));
            }

            $item = [
                'uri' => $uri,
                'title' => $title,
                'uid' => $uri,
            ];
            if ($timestamp !== null) {
                $item['timestamp'] = $timestamp;
            }

            $item['content'] = $this->fetchPageContent($uri);

            $this->items[] = $item;
        }
    }

    private function fetchPageContent(string $uri): string
    {
        try {
            $page = getSimpleHTMLDOMCached($uri, 86400);
        } catch (\Exception $e) {
            return '';
        }
        if (!$page) {
            return '';
        }
        $content = $page->find('div.mw-parser-output', 0);
        if (!$content) {
            return '';
        }
        // Strip MediaWiki edit/cache comments and parser report nodes
        foreach ($content->find('.mw-editsection, .printfooter') as $strip) {
            $strip->outertext = '';
        }
        $html = $content->innertext;
        return defaultLinkTo($html, 'https://wiki.nerdberg.de');
    }
}
