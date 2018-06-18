<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7;
use App\Eloquents\Feed;
use App\Eloquents\Entry;
use App\Eloquents\EntryDetail;
use App\Services\SimpleXML;
use Storage;

class WebSubController extends Controller
{
    /**
     * Subscribe Check JMA
     *
     * @return \Illuminate\Http\Response
     */
    function subscribeCheck(Request $request) {
        // Subscribe check
        $hubMode = $request->hub_mode;
        abort_if($hubMode != 'subscribe' && $hubMode != 'unsubscribe', 404);

        if (config('app.isUseWebSubVerifyToken')) {
            $hubVerifyToken = $request->hub_verify_token;
            abort_if(empty($hubVerifyToken), 403, 'Not exist hub.verify_token');
            abort_if($hubVerifyToken != config('app.websubVerifyToken'), 403, 'Incorrect hub.verify_token');
        }
        $hubChallenge = $request->hub_challenge;
        \Log::notice($hubMode);
        \Log::info('Success subscribe check');

        return response($hubChallenge, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Recive JMA Publish
     *
     * @return \Illuminate\Http\Response
     */
    function receiveFeed(Request $request) {
        // Xml parse
        $content = $request->getContent();

        if (config('app.isUseWebSubVerifyToken')) {
            $hubSignature = $request->header('x-hub-signature');
            abort_if(empty($hubSignature), 403, 'Not exist x-hub-signature header');

            $signature = collect(explode('=',$hubSignature));
            abort_if($signature->count() !== 2, 403, 'Invalid x-hub-signature header');

            $hash = hash_hmac($signature->first(),$content,config('app.websubVerifyToken'));
            abort_if($signature->last() !== $hash, 403, 'Invalid hub signature');

            \Log::debug('Success check hub signature');
        }

        $simpleXml = new SimpleXML($content);

        try {
            $feed = $simpleXml->toArray(true, true);
        } catch (\Exception $e) {
            \Log::warning($e);
            abort(403, $e);
        }
        \Log::debug('Success feed parse');

        $now = Carbon::now();
        $now->setTimezone(config('app.timezone'));

        $feedUuid = collect(explode(':', $feed['id']))->last();
        $feedUpdated = Carbon::parse($feed['updated']);
        $feedUpdated->setTimezone(config('app.timezone'));
        $links = collect($feed['link'])
            ->map(function($item) {return $item['@attributes'];})
            ->pluck('href', 'rel');
        $feedUrl = $links['self'];
        $feeds = Feed::firstOrNew([
            'uuid' => $feedUuid,
            'url' => $feedUrl
        ]);
        $feeds->updated = $feedUpdated;
        $feeds->save();

        // Fetch JMA xml
        $entryArrays = [];
        $promises = [];
        $results  = [];

        if (Arr::isAssoc($feed['entry'])) {
            $feed['entry'] = [$feed['entry']];
        }

        foreach ($feed['entry'] as $entry) {
            $entryUuid = collect(explode(':', $entry['id']))->last();
            $kindOfInfo = $entry['title'];
            $observatory = collect($entry['author'])->get('name');
            $headline = $entry['content'];
            $updated = Carbon::parse($entry['updated']);
            $updated->setTimezone(config('app.timezone'));
            $url = $entry['link']['@attributes']['href'];

            $promises[$entryUuid] = \Guzzle::getAsync($url);
            $entry = Entry::firstOrCreate([
                'observatory_name' => $observatory,
                'headline' => $headline,
                'updated' => $updated,
                'feed_uuid' => $feedUuid,
            ]);

            $entryArrays[$entryUuid] = [
                'entry_id' => $entry->id,
                'kind_of_info' => $kindOfInfo,
                'url' => $url,
            ];
        }

        foreach (Promise\settle($promises)->wait() as $key => $obj) {
            switch ($obj['state']) {
                case 'fulfilled':
                    $results[$key] = $obj['value'];
                    break;
                case 'rejected':
                    $results[$key] = new Psr7\Response($obj['reason']->getCode());
                    break;
                default:
                    $results[$key] = new Psr7\Response(0);
            }
        }

        $entryRecords = [];
        foreach ($results as $key => $result) {
            $entryArray = $entryArrays[$key];
            $entryArray['uuid'] = $key;
            $entryArray['created_at'] = $now;
            $entryArray['updated_at'] = $now;

            if ($result->getReasonPhrase() === 'OK') {
                $xmlDoc = $result->getBody()->getContents();
                Storage::put('entry/'.$key, $xmlDoc);
            }

            $entryRecords[] = $entryArray;
        }

        $entries = EntryDetail::insert($entryRecords);
    }
}

