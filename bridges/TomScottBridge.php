<?php

class TomScottBridge extends FeedExpander
{
    const MAINTAINER = 'schabi.org';
    const NAME = 'Tom Scott';
    const URI = 'https://www.tomscott.com/';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = "Returns Tom Scott's weekly newsletter with full article content";
    const PARAMETERS = [[
        'limit' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => false,
            'title' => 'Number of newsletters to return',
            'defaultValue' => 10,
        ],
    ]];

    public function collectData()
    {
        $this->collectExpandableDatas('https://www.tomscott.com/updates.xml', $this->getInput('limit'));
    }

    protected function parseItem(array $item)
    {
        $article = getSimpleHTMLDOMCached($item['uri']);
        if (!$article) {
            return $item;
        }

        $article = defaultLinkTo($article, $item['uri']);

        $body = $article->find('div.newsletter-body', 0);
        if ($body) {
            $item['content'] = $body->innertext;
        }

        return $item;
    }
}
