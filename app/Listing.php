<?php

namespace App;

use App\Http\Helpers\BcarOptions;
use App\Http\Helpers\EcarOptions;
use App\Http\Helpers\ListingsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Listing extends Model
{
    protected $guarded = [];

    /**
     * A Listing has many photos
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photos()
    {
        return $this->hasMany('App\Photo');
    }

    /**
     * Get all BCAR MLS listings
     * @return void
     */
    public function freshBcarListings()
    {
        $classArray = ['A', 'C', 'E', 'F', 'G', 'J'];

        foreach ($classArray as $class) {
            $this->insertBcarListingsIntoDatabase($class);
        }
    }

    /**
     * Get all ECAR MLS listings
     * @return void
     */
    public function freshEcarListings()
    {
        $classArray  = ['A', 'B', 'C', 'E', 'F', 'G', 'H', 'I'];

        foreach ($classArray as $class) {
            $this->insertEcarListingsIntoDatabase($class);
        }
    }

    public function insertEcarListingsIntoDatabase($class)
    {
        $mls            = new ApiCall();
        $rets           = $mls->loginToEcar();
        $counter        = 0;
        $maxRowsReached = false;
        $offset         = 0;

        echo "Fetching listings for class {$class}:";

        while (! $maxRowsReached) {
            $ecarOptions = EcarOptions::all($offset);

            $results = $rets->Search('Property', $class, '*', $ecarOptions[$class]);

            foreach ($results as $result) {
                ListingsHelper::createEcarListing($result, $class);
            }

            $offset += $results->getReturnedResultsCount();

            if ($offset >= $results->getTotalResultsCount()) {
                $maxRowsReached = true;
            }
            echo '\nOffset for class ' . $class . ': ' . $offset . ' | Total Results Count:' . $results->getTotalResultsCount() . '\n';
        }
    }

    /**
     * Persist the listings for the specified class into the database
     * @param string $class
     * @return void
     */
    public function insertBcarListingsIntoDatabase($class)
    {
        $mls         = new ApiCall();
        $rets        = $mls->loginToBcar();
        $bcarOptions = BcarOptions::all();

        $results = $rets->Search('Property', $class, '*', $bcarOptions[$class]);

        foreach ($results as $result) {
            ListingsHelper::createBcarListing($result, $class);
        }
    }
    public static function getColumn($columnName)
    {
        $values  = DB::table('listings')->select($columnName)->where($columnName, '!=', '')->groupBy($columnName)->get();

        return $values->toArray();
    }
}
