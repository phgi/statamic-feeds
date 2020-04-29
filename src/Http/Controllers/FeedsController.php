<?php

namespace Edalzell\Feeds\Http\Controllers;

use SimpleXMLElement;
use Statamic\Facades\URL;
use Statamic\Support\Arr;
use Statamic\Facades\Data;
use Statamic\Entries\Entry;
use Statamic\Facades\Parse;
use Illuminate\Http\Request;
use Statamic\Facades\Config;
use Statamic\Modifiers\Modify;
use Statamic\Http\Controllers\Controller as BaseController;

class FeedsController extends BaseController
{
    /** @var string */
    private $title;

    /** @var string */
    private $feed_url;

    /** @var string */
    private $site_url;

    /** @var array */
    private $name_fields;

    /** @var string */
    private $author_field;

    /** @var string */
    private $custom_content;

    /** @var string */
    private $content;

    /** @var \Statamic\Data\Entries\EntryCollection */
    private $entries;

    public function __construct(Request $request)
    {
        $config = collect(config('feeds.types', []))->firstWhere('route', $request->getPathInfo());

        $this->title = Arr::get($config, 'title');
        $this->name_fields = Arr::get($config, 'name_fields', []);
        $this->author_field = Arr::get($config, 'author_field');
        $this->custom_content = Arr::get($config, 'custom_content', false);
        $this->content = Arr::get($config, 'content');
        $this->site_url = URL::makeAbsolute(Config::getSiteUrl());
        $this->feed_url = $request->fullUrl();

        $this->entries = Entry::query()
            ->whereIn('collection', Arr::get($config, 'collections', []))
            ->where('published', true)
            ->where('date', '<=', now())
            ->orderBy('date', 'desc')
            ->orderBy('title')
            ->limit(20)
            ->get();
    }

    public function json()
    {
        return [
            'version' => 'https://jsonfeed.org/version/1',
            'title' => $this->title,
            'home_page_url' => $this->site_url,
            'feed_url' => $this->feed_url,
            'items' => $this->getItems(),
        ];
    }

    public function atom()
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

    private function getItems()
    {
        return $this->entries->map(function ($entry) {
            $item = [
                'id' => $entry->id(),
                'title' => $entry->get('title'),
                'url' => $entry->absoluteUrl(),
                'date_published' => $entry->date()->toRfc3339String(),
                'content_html' => (string) Modify::value($this->getContent($entry))->fullUrls(),
            ];

            if ($entry->has($this->author_field)) {
                $item['author'] = ['name' => $this->makeName($entry->get($this->author_field))];
            }

            if ($entry->has('link')) {
                $item['external_url'] = $entry->get('link');
            }

            return $item;
        })->values()->all();
    }

    private function getContent(Entry $entry)
    {
        if ($this->custom_content) {
            $content = Parse::template($this->content, $entry->data()->all());
        } else {
            $content = $entry->parseContent();
        }

        return $content;
    }

    private function makeName($id)
    {
        $name = 'Anonymous';

        if ($author = Data::find($id)) {
            $this->name_fields;
            $name = implode(
                ' ',
                array_merge(
                    array_flip($this->name_fields),
                    Arr::only($author->data()->all(), $this->name_fields)
                )
            );
        }

        return $name;
    }
}
