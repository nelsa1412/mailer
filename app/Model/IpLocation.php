<?php

/**
 * IpLocation class.
 *
 * Model class for IP Locations
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   MVC Model
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log as LaravelLog;

class IpLocation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_code', 'country_name', 'region_code',
        'region_name', 'city', 'zipcode',
        'latitude', 'longitude', 'metro_code', 'areacode',
    ];

    /**
     * Add new IP.
     *
     * return Location
     */
    public static function add($ip)
    {
        //SELECT * FROM `ip2location_db11` WHERE INET_ATON('116.109.245.204') <= ip_to LIMIT 1

        $location = self::where('ip_address', '=', $ip)->first();
        if (!is_object($location)) {
            $location = new self();
        }
        $location->ip_address = $ip;

        // Get info
        try {
            $location_table = 'ip2location_db11';
            $location_tables = \DB::select('SHOW TABLES LIKE "'.$location_table.'"');
            // Local service
            if (count($location_tables)) {
                $records = \DB::select("SELECT * FROM `".$location_table."` WHERE INET_ATON(?) <= ip_to LIMIT 1", [$ip]);
                if (count($records)) {
                    $record = $records[0];
                    $location->country_code = $record->country_code;
                    $location->country_name = $record->country_name;
                    $location->region_name = $record->region_name;
                    $location->city = $record->city_name;
                    $location->zipcode = $record->zip_code;
                    $location->latitude = $record->latitude;
                    $location->longitude = $record->longitude;
                }
            // Remote service
            } else {
                $result = file_get_contents('http://freegeoip.net/json/'.$ip);
                $values = json_decode($result, true);

                $location->fill($values);
            }
        } catch (\Exception $e) {
            // Note log
            LaravelLog::warning('Cannot get IP location info: ' . $e->getMessage());
        }

        $location->save();

        return $location;
    }

    /**
     * Location name.
     *
     * return Location
     */
    public function name()
    {
        $str = [];
        if (!empty($this->city)) {
            $str[] = $this->city;
        }
        if (!empty($this->region_name)) {
            $str[] = $this->region_name;
        }
        if (!empty($this->country_name)) {
            $str[] = $this->country_name;
        }
        $name = implode(', ', $str);
        return (empty($name) ? trans('messages.unknown') : $name);
    }
}
