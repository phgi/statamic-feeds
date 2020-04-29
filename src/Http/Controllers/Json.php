<?php

namespace Edalzell\Feeds\Http\Controllers;

class Json extends BaseController
{
    public function __invoke()
    {
        return [
            'version' => 'https://jsonfeed.org/version/1',
            'title' => $this->title,
            'home_page_url' => $this->site_url,
            'feed_url' => $this->feed_url,
            'items' => $this->getItems(),
        ];
    }
}
