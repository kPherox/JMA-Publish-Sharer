<?php

use App\Eloquents;
use Illuminate\Database\Migrations\Migration;

class CopyDataToEntryDetailsTableFromEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $all_count = Eloquents\Entry::count();
        if (! $all_count) {
            return;
        }

        $entries_last_id = Eloquents\Entry::select('id')
                ->orderBy('id', 'desc')
                ->limit(1)->first()->id;

        $processed_count = 0;
        echo 'all count is '.$all_count.PHP_EOL;
        for ($i = 0; $i < $entries_last_id + 1000; $i += 1000) {
            $range = [
                ($i + 1),
                ($i + 1000)
            ];
            $entries = Eloquents\Entry::whereRaw('id BETWEEN ? AND ?', $range)->get();
            echo vsprintf('from %d to %d entries selected.', $range).PHP_EOL;
            foreach ($entries as $entry) {
                $detail = Eloquents\EntryDetail::firstOrNew(['uuid' => $entry->uuid]);
                $detail->entry_id = $entry->id;
                $detail->kind_of_info = $entry->kind_of_info;
                $detail->url = $entry->url;
                $detail->observatory_name = $entry->observatory_name;
                $detail->headline = $entry->headline;
                $detail->updated = $entry->updated;
                $detail->save();
                $processed_count++;
                if (($processed_count) % 1000 == 0) {
                    echo($processed_count).' of '.$all_count.' records copied.'.PHP_EOL;
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Eloquents\EntryDetail::truncate();
    }
}
