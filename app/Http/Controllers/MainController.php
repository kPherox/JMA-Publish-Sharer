<?php

namespace App\Http\Controllers;

use App\Eloquents\Entry;
use App\Eloquents\EntryDetail;
use App\Eloquents\Feed;
use App\Services\SimpleXML;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MainController extends Controller
{
    /**
     * Index page.
     */
    public function index() : View
    {
        $type = request('type', null);
        $kind = request('kind', null);

        $data = $this->entries($type, $kind)->merge([
                    'routeUrl' => route('index'),
                ]);

        return view('index', $data->all());
    }

    /**
     * Event index page.
     *
     * @param  string $eventId
     */
    public function event(string $eventId) : View
    {
        $entries = Entry::whereHas('entryDetails', function ($query) use ($eventId) {
            return $query->whereEventId($eventId);
        })->paginate(15);

        return view('event', [
            'entries' => $entries,
            'eventId' => $eventId,
            'routeUrl' => route('event', ['id' => $eventId]),
        ]);
    }

    /**
     * Observatory page.
     *
     * @param  string $observatoryName
     */
    public function observatory(string $observatoryName) : View
    {
        $type = request('type', null);
        $kind = request('kind', null);

        $observatories = Entry::select('observatory_name as name')
                    ->selectRaw('count(*) as count')
                    ->selectRaw('MAX(updated) as max_updated')
                    ->orderBy('max_updated', 'desc')
                    ->groupBy('observatory_name')
                    ->get()
                    ->map(function ($observatory) {
                        $observatory->url = route('observatory', ['observatory' => $observatory->name]);

                        return $observatory;
                    });

        $data = $this->entries($type, $kind, $observatoryName)->merge([
                    'observatory' => $observatoryName,
                    'observatories' => $observatories,
                    'routeUrl' => route('observatory', ['observatory' => $observatoryName]),
                ]);

        return view('observatory', $data->all());
    }

    /**
     * Create entries list & filter list for index & observatory page.
     *
     * @param  string? $type
     * @param  string? $kind
     * @param  string? $observatoryName
     */
    private function entries(?string $type, ?string $kind, string $observatoryName = null) : Collection
    {
        $typeOrKind = 'Select Type or Kind';
        $selected = '';
        $entries = Entry::orderBy('updated', 'desc');
        $appends = [];

        $feeds = Feed::select(['url'])
                    ->having('entries_count', '>=', 1);
        $kindList = EntryDetail::select('kind_of_info')
                    ->selectRaw('count(*) as count')
                    ->groupBy('kind_of_info');

        if ($observatoryName) {
            $entries = $entries->ofObservatory($observatoryName);
            $feeds = $feeds->withCount(['entries' => function ($query) use ($observatoryName) {
                return $query->ofObservatory($observatoryName);
            }]);
            $kindList = $kindList->whereHas('entry', function ($query) use ($observatoryName) {
                return $query->ofObservatory($observatoryName);
            });
        } else {
            $feeds = $feeds->withCount('entries');
        }

        $feeds = $feeds->get()->sortByType();
        $kindList = $kindList->get()->sortByKind();

        if ($type) {
            $selected = $type;
            $typeOrKind = 'Type: '.trans('feedtypes.'.$type);
            $appends['type'] = $type;
            $entries = $entries->whereHas('feed', function ($query) use ($type) {
                return $query->ofType($type);
            });
        } elseif ($kind) {
            $selected = $kindList->search(function ($i) use ($kind) {
                return $i->kind_of_info === $kind;
            });
            $typeOrKind = 'Kind: '.$kind;
            $appends['kind'] = $kind;
            $entries = $entries->whereHas('entryDetails', function ($query) use ($kind) {
                return $query->where('kind_of_info', $kind);
            });
        }

        $entries = $entries->paginate(15)->appends($appends);

        return collect([
            'entries' => $entries,
            'selected' => $selected,
            'typeOrKind' => $typeOrKind,
            'feeds' => $feeds,
            'kindList' => $kindList,
        ]);
    }

    /**
     * Entry page.
     *
     * @param  \App\Eloquents\EntryDetail $entry
     */
    public function entry(EntryDetail $entry) : View
    {
        $feed = $entry->entry->feed;
        try {
            $entryArray = collect((new SimpleXML($entry->xml_file, true))->toArray(true));
        } catch (FileNotFoundException $e) {
            abort(404, $e);
        } catch (\Exception $e) {
            abort(403, $e);
        }

        $kindViewName = config('jmaxml.kinds.'.data_get($entryArray, 'Control.Title').'.view');
        $viewName = \View::exists($kindViewName) ? $kindViewName : 'entry';

        return view($viewName, [
                    'entry' => $entryArray,
                    'xmlUrl' => $entry->xml_file_url,
                    'jsonUrl' => $entry->json_file_url,
                    'feed' => $feed,
                ]);
    }

    /**
     * Entry xml.
     *
     * @param  string $uuid
     */
    public function entryXml(EntryDetail $entry) : Response
    {
        if (! $entry->existsXmlFile()) {
            abort(404);
        }

        $headers = ['Content-Type' => 'application/xml'];
        if (collect(request()->header('Accept-Encoding'))->contains('gzip') && $entry->existsGzippedXmlFile()) {
            $header['Content-Encoding'] = 'gzip';

            return response($entry->gzipped_xml_file, 200, $headers);
        }

        return response($entry->xml_file, 200, $headers);
    }

    /**
     * Entry json.
     *
     * @param  string $uuid
     */
    public function entryJson(EntryDetail $entry) : JsonResponse
    {
        if (! $entry->existsXmlFile()) {
            abort(404);
        }

        try {
            $entryArray = collect((new SimpleXML($entry->xml_file, true))->toArray(true));
        } catch (\Exception $e) {
            abort(403, $e);
        }

        return response()->json($entryArray, 200, [], JSON_UNESCAPED_UNICODE);
    }
}
