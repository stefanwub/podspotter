<?php

namespace App\Http\Controllers;

use App\Jobs\ImportShow;
use App\Models\Show;
use Illuminate\Http\Request;
use willvincent\Feeds\Facades\FeedsFacade;

class ImportShowsFromCsvController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $collection = collect($this->extractSocialMediaLinks('/Users/stefan/Downloads/pages.csv'));

        foreach ($collection->where('youtube')->whereNotIn('slug', ['themetalk', 'kukuru']) as $show) {
            dispatch( function () use ($show) {
                $feed = FeedsFacade::make($show['youtube']);

                if ($feed->get_title()) {
                    $show = Show::create([
                        'feed_url' => $show['youtube']
                    ]);

                    ImportShow::dispatch($show);
                }
            });
        }

        return [
            'youtube_count' => $collection->where('youtube')->count(),
            'instagram_count' => $collection->where('instagram')->count(),
            'collection' => $collection->where('youtube')->whereNotIn('slug', ['themetalk', 'kukuru'])
        ];
    }

    public function extractSocialMediaLinks($csvFilePath) {
        $resultArray = []; // Initialize an empty array to hold the data

        // Check if the file exists and is readable
        if (!file_exists($csvFilePath) || !is_readable($csvFilePath)) {
            return "File does not exist or cannot be read.";
        }

        // Open the CSV file for reading
        if (($handle = fopen($csvFilePath, "r")) !== FALSE) {
            $columnHeaders = fgetcsv($handle); // Read the first line to get column headers
            $socialsIndex = array_search('socials', $columnHeaders); // Find the index of the 'socials' column
            $statusIndex = array_search('status', $columnHeaders); // Find the index of the 'status' column
            $nameIndex = array_search('name', $columnHeaders); // Find the index of the 'name' column
            $slugIndex = array_search('slug', $columnHeaders); // Find the index of the 'slug' column

            // Check if necessary columns were found
            if ($socialsIndex === FALSE || $statusIndex === FALSE) {
                return "The 'socials' or 'status' column was not found in the CSV.";
            }

            // Loop through each line of the CSV after the header
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Check the 'status' column for 'active' or 'paused'
                if (isset($data[$statusIndex]) && ($data[$statusIndex] == 'active')) {
                    if (isset($data[$socialsIndex])) {
                        $socialsData = json_decode($data[$socialsIndex], true); // Decode the JSON in the 'socials' column
                        $entry = []; // Initialize an empty array for this row's data

                        // Check for and assign youtube and instagram links if they exist
                        if ($socialsData) {
                            if (isset($socialsData['youtube']) && !empty($socialsData['youtube'])) {
                                $entry['youtube'] = $socialsData['youtube'];
                            }

                            if (isset($socialsData['instagram']) && !empty($socialsData['instagram'])) {
                                $entry['instagram'] = $socialsData['instagram'];
                            }

                            // If either social link exists, add name and slug to the entry
                            if (!empty($entry)) {
                                $entry['name'] = isset($data[$nameIndex]) ? $data[$nameIndex] : '';
                                $entry['slug'] = isset($data[$slugIndex]) ? $data[$slugIndex] : '';
                                
                                // Add the entry to the result array
                                $resultArray[] = $entry;
                            }
                        }
                    }
                }
            }

            fclose($handle); // Close the file handle
        }

        return $resultArray; // Return the result array
    }
}
