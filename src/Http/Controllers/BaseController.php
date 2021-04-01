<?php

namespace Edalzell\Feeds\Http\Controllers;

use Statamic\Facades\URL;
use Statamic\Support\Arr;
use Statamic\Facades\Data;
use Statamic\Entries\Entry;
use Statamic\Facades\Parse;
use Illuminate\Http\Request;
use Statamic\Facades\Config;
use Statamic\Modifiers\Modify;
use Statamic\Http\Controllers\Controller as StatamicController;

class BaseController extends StatamicController
{
    /** @var string */
    protected $title;

    /** @var string */
    protected $feed_url;

    /** @var string */
    protected $site_url;

    /** @var array */
    protected $name_fields;

    /** @var string */
    protected $author_field;

    /** @var string */
    protected $custom_content;

    /** @var string */
    protected $content;

    /** @var \Statamic\Data\Entries\EntryCollection */
    protected $entries;

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

    protected function getItems()
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

    protected function getContent(Entry $entry)
    {
        if ($this->custom_content) {
            $content = Parse::template($this->content, $entry->data()->all());
        } else {
            $content = $entry->augmentedValue('content');
        }

        return $content;
    }

    protected function makeName($ids)
    {
        $name = 'Anonymous';

        if ($authors = collect($ids)->transform(function ($id) {return Entry::find($id);})) {

            $authors = $authors->map(function($author) {
                return implode(
                    ' ',
                    array_merge(
                        array_flip($this->name_fields),
                        Arr::only($author->data()->all(), $this->name_fields)
                    )
                );
            })->toArray();

            $name = implode(', ', $authors);
        }

        return $name;
    }
}
