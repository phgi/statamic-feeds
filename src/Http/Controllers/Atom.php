<?php

namespace Edalzell\Feeds\Http\Controllers;

use SimpleXMLElement;
use Statamic\Modifiers\Modify;

class Atom extends BaseController
{
    public function __invoke()
    {
        $atom = new SimpleXMLElement('<feed xmlns="http://www.w3.org/2005/Atom"></feed>');
        $atom->addAttribute('xmlns:xml:lang', 'en');
        $atom->addAttribute('xmlns:xml:base', $this->site_url);

        $atom->addChild('id', $this->feed_url);
        $atom->addChild('title', htmlspecialchars($this->title));

        if ($this->entries->count()) {
            $atom->addChild('updated', $this->entries->first()->date()->toRfc3339String());
        }

        $link = $atom->addChild('link');
        $link->addAttribute('rel', 'self');
        $link->addAttribute('href', $this->feed_url);
        $link->addAttribute('xmlns', 'http://www.w3.org/2005/Atom');

        collect(config('feed.discovery', []))->each(function ($url, $key) use ($atom) {
            $link = $atom->addChild('link');
            $link->addAttribute('rel', 'hub');
            $link->addAttribute('href', '//'.$url);
            $link->addAttribute('xmlns', 'http://www.w3.org/2005/Atom');
        });

        $this->entries->each(function ($entry, $key) use ($atom) {
            $entryXml = $atom->addChild('entry');

            $entryXml->addChild('id', 'urn:uuid:'.$entry->id());
            $entryXml->addChild('title', htmlspecialchars($entry->get('title')));
            $entryXml->addChild('author')
                ->addChild('name', $this->makeName($entry->get($this->author_field)));
            $entryXml->addChild('link')->addAttribute('href', $entry->absoluteUrl());
            $entryXml->addChild('updated', $entry->date()->toRfc3339String());
            if ($this->getContent($entry)) {
                $entryXml->addChild('content', htmlspecialchars(Modify::value($this->getContent($entry))->fullUrls()))
                    ->addAttribute('type', 'html');
            }
        });

        return response($atom->asXML(), 200, ['Content-Type' => 'application/atom+xml']);
    }
}
