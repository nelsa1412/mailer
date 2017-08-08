<?php

/**
 * SendingServer class.
 *
 * An abstract class for different types of sending servers
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
use Acelle\Library\RouletteWheel;
use Acelle\Library\Log as MailLog;
use Acelle\Library\QuotaTrackerFile;
use Carbon\Carbon;

class SendingServer extends Model
{
    const DELIVERY_STATUS_SENT = 'sent';
    const DELIVERY_STATUS_FAILED = 'failed';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $quotaTracker;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'host', 'aws_access_key_id', 'aws_secret_access_key', 'aws_region', 'smtp_username',
        'smtp_password', 'smtp_port', 'smtp_protocol', 'quota_value', 'quota_base', 'quota_unit',
        'bounce_handler_id', 'feedback_loop_handler_id', 'sendmail_path', 'domain', 'api_key'
    ];

    // Supported server types
    public static $serverMapping = array(
        'amazon-api' => 'SendingServerAmazonApi',
        'amazon-smtp' => 'SendingServerAmazonSmtp',
        'smtp' => 'SendingServerSmtp',
        'sendmail' => 'SendingServerSendmail',
        'php-mail' => 'SendingServerPhpMail',
        'mailgun-api' => 'SendingServerMailgunApi',
        'mailgun-smtp' => 'SendingServerMailgunSmtp',
        'sendgrid-api' => 'SendingServerSendGridApi',
        'sendgrid-smtp' => 'SendingServerSendGridSmtp',
        'elasticemail-api' => 'SendingServerElasticEmailApi',
        'elasticemail-smtp' => 'SendingServerElasticEmailSmtp',
        'sparkpost-api' => 'SendingServerSparkPostApi',
        'sparkpost-smtp' => 'SendingServerSparkPostSmtp',
    );

    /**
     * Tracking logs.
     *
     * @return collection
     */
    public function trackingLogs()
    {
        return $this->hasMany('Acelle\Model\TrackingLog', 'sending_server_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the segment of the campaign.
     */
    public function bounceHandler()
    {
        return $this->belongsTo('Acelle\Model\BounceHandler');
    }

    /**
     * Map a server to its class type and initiate an instance.
     *
     * @return mixed
     * @param campaign
     */
    public static function mapServerType($server)
    {
        $class_name = '\Acelle\Model\\'.self::$serverMapping[$server->type];

        return $class_name::find($server->id);
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public function getVerp($recipient)
    {
        if (is_object($this->bounceHandler)) {
            $validator = \Validator::make(
                ['email' => $this->bounceHandler->username],
                ['email' => 'required|email']
            );

            if ($validator->passes()) {
                // @todo disable VERP as it is not supported by all mailbox
                // return str_replace('@', '+'.str_replace('@', '=', $recipient).'@', $this->bounceHandler->username);
                return $this->bounceHandler->username;
            }
        }

        return null;
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        return self::where('status', '=', 'active');
    }

    /**
     * Associations.
     *
     * @var object | collect
     */
    public function customer()
    {
        return $this->belongsTo('Acelle\Model\Customer');
    }

    public function admin()
    {
        return $this->belongsTo('Acelle\Model\Admin');
    }

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($request)
    {
        $user = $request->user();
        $query = self::select('sending_servers.*');

        // Keyword
        if (!empty(trim($request->keyword))) {
            foreach (explode(' ', trim($request->keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('sending_servers.name', 'like', '%'.$keyword.'%')
                        ->orWhere('sending_servers.type', 'like', '%'.$keyword.'%')
                        ->orWhere('sending_servers.host', 'like', '%'.$keyword.'%');
                });
            }
        }

        // filters
        $filters = $request->filters;
        if (!empty($filters)) {
            if (!empty($filters['type'])) {
                $query = $query->where('sending_servers.type', '=', $filters['type']);
            }
        }

        // Other filter
        if(!empty($request->customer_id)) {
            $query = $query->where('sending_servers.customer_id', '=', $request->customer_id);
        }

        if(!empty($request->admin_id)) {
            $query = $query->where('sending_servers.admin_id', '=', $request->admin_id);
        }

        // remove customer sending servers
        if(!empty($request->no_customer)) {
            $query = $query->whereNull('customer_id');
        }

        return $query;
    }

    /**
     * Search items.
     *
     * @return collect
     */
    public static function search($request)
    {
        $query = self::filter($request);

        if(!empty($request->sort_order)) {
            $query = $query->orderBy($request->sort_order, $request->sort_direction);
        }

        return $query;
    }

    /**
     * Find item by uid.
     *
     * @return object
     */
    public static function findByUid($uid)
    {
        return self::where('uid', '=', $uid)->first();
    }

    /**
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;

    /**
     * Bootstrap any application services.
     */
    public static function boot()
    {
        parent::boot();

        // Create uid when creating list.
        static::creating(function ($item) {
            // Create new uid
            $uid = uniqid();
            while (SendingServer::where('uid', '=', $uid)->count() > 0) {
                $uid = uniqid();
            }
            $item->uid = $uid;

            // SendingServer custom order
            SendingServer::getAll()->increment('custom_order', 1);
            $item->custom_order = 0;
        });
    }

    /**
     * Type of server.
     *
     * @return object
     */
    public static function types()
    {
        return [
            'amazon-smtp' => [
                'cols' => [
                    'name' => 'required',
                    'host' => 'required',
                    'aws_access_key_id' => 'required',
                    'aws_secret_access_key' => 'required',
                    'aws_region' => 'required',
                    'smtp_username' => 'required',
                    'smtp_password' => 'required',
                    'smtp_port' => 'required',
                    'smtp_protocol' => 'required',
                ],
            ],
            'amazon-api' => [
                'cols' => [
                    'name' => 'required',
                    'aws_access_key_id' => 'required',
                    'aws_secret_access_key' => 'required',
                    'aws_region' => 'required',
                ],
            ],
            'sendgrid-smtp' => [
                'cols' => [
                    'name' => 'required',
                    'api_key' => 'required',
                    'host' => 'required',
                    'smtp_username' => 'required',
                    'smtp_password' => 'required',
                    'smtp_port' => 'required',
                ],
            ],
            'sendgrid-api' => [
                'cols' => [
                    'name' => 'required',
                    'api_key' => 'required',
                ],
            ],
            'mailgun-api' => [
                'cols' => [
                    'name' => 'required',
                    'domain' => 'required',
                    'api_key' => 'required',
                ],
            ],
            'mailgun-smtp' => [
                'cols' => [
                    'name' => 'required',
                    'domain' => 'required',
                    'api_key' => 'required',
                    'host' => 'required',
                    'smtp_username' => 'required',
                    'smtp_password' => 'required',
                    'smtp_port' => 'required',
                    'smtp_protocol' => 'required',
                ],
            ],
            'elasticemail-api' => [
                'cols' => [
                    'name' => 'required',
                    'api_key' => 'required',
                ],
            ],
            'elasticemail-smtp' => [
                'cols' => [
                    'name' => 'required',
                    'api_key' => 'required',
                    'host' => 'required',
                    'smtp_username' => 'required',
                    'smtp_password' => 'required',
                    'smtp_port' => 'required',
                ],
            ],
            'sparkpost-api' => [
                'cols' => [
                    'name' => 'required',
                    'api_key' => 'required',
                ],
            ],
            'sparkpost-smtp' => [
                'cols' => [
                    'name' => 'required',
                    'api_key' => 'required',
                    'host' => 'required',
                    'smtp_username' => 'required',
                    'smtp_password' => 'required',
                    'smtp_port' => 'required',
                    'smtp_protocol' => '',
                ],
            ],
            'smtp' => [
                'cols' => [
                    'name' => 'required',
                    'host' => 'required',
                    'smtp_username' => 'required',
                    'smtp_password' => 'required',
                    'smtp_port' => 'required',
                    'smtp_protocol' => '',
                    'bounce_handler_id' => '',
                    'feedback_loop_handler_id' => '',
                ],
            ],
            'sendmail' => [
                'cols' => [
                    'name' => 'required',
                    'sendmail_path' => 'required',
                    'bounce_handler_id' => '',
                    'feedback_loop_handler_id' => '',
                ],
            ],
            'php-mail' => [
                'cols' => [
                    'name' => 'required',
                    'bounce_handler_id' => '',
                    'feedback_loop_handler_id' => '',
                ],
            ],
        ];
    }

    /**
     * Get select options.
     *
     * @return array
     */
    public static function getSelectOptions()
    {
        $query = self::getAll();
        $options = $query->orderBy('name')->get()->map(function ($item) {
            return ['value' => $item->uid, 'text' => $item->name];
        });

        return $options;
    }

    /**
     * Get sending server's quota.
     *
     * @return string
     */
    public function getSendingQuota()
    {
        return $this->quota_value;
    }

    /**
     * Get sending server's sending quota.
     *
     * @return string
     */
    public function getSendingQuotaUsage()
    {
        $tracker = $this->getQuotaTracker();
        return $tracker->getUsage();
    }

    /**
     * Get sending server's sending quota rate.
     *
     * @return string
     */
    public function getSendingQuotaUsagePercentage()
    {
        if ($this->getSendingQuota() == '∞') {
            return '0';
        } elseif ($this->getSendingQuota() == '0' || $this->getSendingQuotaUsage() >= $this->getSendingQuota()) {
            return '100';
        }

        return round(($this->getUsagePercentage * 100), 2);
    }

    /**
     * Get user's sending quota rate.
     *
     * @return string
     */
    public function displaySendingQuotaUsage()
    {
        if ($this->getSendingQuota() == '∞') {
            return trans('messages.unlimited');
        }

        return $this->getSendingQuotaUsagePercentage().'%';
    }

    /**
     * Get rules.
     *
     * @return string
     */
    public static function rules($type)
    {
        $rules = self::types()[$type]['cols'];
        $rules['quota_value'] = 'required|numeric';
        $rules['quota_base'] = 'required|numeric';
        $rules['quota_unit'] = 'required';

        return $rules;
    }

    /**
     * Quota display.
     *
     * @return string
     */
    public function displayQuota()
    {
        return $this->quota_value.' / '.$this->quota_base.' '.trans('messages.'.\Acelle\Library\Tool::getPluralPrase($this->quota_unit, $this->quota_base));
    }

    /**
     * Select options for aws region.
     *
     * @return array
     */
    public static function awsRegionSelectOptions()
    {
        return [
            ['value' => '', 'text' => trans('messages.choose')],
            ['value' => 'us-east-1', 'text' => 'US East (N. Virginia)'],
            ['value' => 'us-west-2', 'text' => 'US West (Oregon)'],
            ['value' => 'ap-southeast-1', 'text' => 'Asia Pacific (Singapore)'],
            ['value' => 'ap-southeast-2', 'text' => 'Asia Pacific (Sydney)'],
            ['value' => 'ap-northeast-1', 'text' => 'Asia Pacific (Tokyo)'],
            ['value' => 'eu-central-1', 'text' => 'EU (Frankfurt)'],
            ['value' => 'eu-west-1', 'text' => 'EU (Ireland)'],
        ];
    }

    /**
     * Disable sending server
     *
     * @return array
     */
    public function disable()
    {
        $this->status = "inactive";
        $this->save();
    }

    /**
     * Enable sending server
     *
     * @return array
     */
    public function enable()
    {
        $this->status = "active";
        $this->save();
    }

    /**
     * Get sending server's QuotaTracker
     *
     * @return array
     */
    public function getQuotaTracker() {
        if(!$this->quotaTracker) {
            $this->initQuotaTracker();
        }

        return $this->quotaTracker;
    }

    /**
     * Initialize the quota tracker
     *
     * @return void
     */
    public function initQuotaTracker() {
        $this->quotaTracker = new QuotaTrackerFile($this->getSendingQuotaLockFile(), null, [$this->getQuotaIntervalString() => $this->getSendingQuota()]);
        // @note: in case of multi-process, the following command must be issued manually
        //     $this->renewQuotaTracker();
    }

    /**
     * Get sending quota lock file
     *
     * @return string file path
     */
    public function getSendingQuotaLockFile() {
        return storage_path("app/server/quota/{$this->uid}");
    }

    /**
     * Get quota starting time
     *
     * @return string
     */
    public function getQuotaIntervalString() {
        return "{$this->quota_base} {$this->quota_unit}";
    }

    /**
     * Initialize the quota tracker
     *
     * @return array
     */
    public function renewQuotaTracker() {
        $this->getQuotaTracker()->getExclusiveLock(function() {
            $start = $this->getQuotaStartingTime();

            // recent tracking logs
            // @todo: potential issue here, application fails in case $start is NULL
            $recent = $this->trackingLogs()->where('created_at', '>=', $start);

            // existing quota usage
            $stored = null;

            // load the tracker from DB (if exists)
            // then merge with the current tracking log data
            if ($this->quota) {
                try {
                    $stored = unserialize($this->quota);
                } catch (\Exception $x) {
                    // @TODO logging here
                    MailLog::warning('Cannot retrieve sending server quota');
                }
            }

            // load the tracker from DB
            if (!is_null($stored) && !empty($stored)) {
                // retrieve the stored quota usage and merge it with actual (newer) usage
                $recent = $recent->where('created_at', '>', \Carbon\Carbon::createFromTimestamp(end($stored)));
                $series = $recent->get()->map(function($trackingLog) {
                    return $trackingLog->created_at->timestamp;
                })->toArray();

                $series = array_merge($stored, $series);
            } else {
                $series = $recent->get()->map(function($trackingLog) {
                    return $trackingLog->created_at->timestamp;
                })->toArray();
            }

            $this->getQuotaTracker()->reset($series);
        });
    }

    /**
     * Get quota starting time
     *
     * @return array
     */
    public function getQuotaStartingTime() {
        return "{$this->getQuotaIntervalString()} ago";
    }

    /**
     * Store the current quota usage info to DB
     *
     * @return array
     */
    public function saveQuotaUsageInfo() {
        $this->quota = serialize($this->getQuotaTracker()->getSeries());
        $this->save();
    }

    /**
     * Increment quota usage
     *
     * @return void
     */
    public function countUsage(Carbon $timePoint = null)
    {
        return $this->getQuotaTracker($timePoint)->add();
    }

    /**
     * Check if user has used up all quota allocated.
     *
     * @return string
     */
    public function overQuota()
    {
        return !$this->getQuotaTracker()->check();
    }

    /**
     * Check if sending server supports custom ReturnPath header (used for bounced/feedback handling)
     *
     * @return boolean
     */
    public function allowCustomReturnPath()
    {
        return ( $this->type == 'smtp' || $this->type == 'sendmail' || $this->type == 'php-mail' );
    }

    /**
     * Get all active items.
     *
     * @return collect
     */
    public static function getAllActive()
    {
        return self::where('status', '=', SendingServer::STATUS_ACTIVE);
    }

    /**
     * Get all active system items.
     *
     * @return collect
     */
    public static function getAllAdminActive()
    {
        return self::getAllActive()->whereNotNull('admin_id');
    }

    /**
     * Add customer action log.
     */
    public function log($name, $customer, $add_datas = [])
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        $data = array_merge($data, $add_datas);

        Log::create([
            'customer_id' => $customer->id,
            'type' => 'sending_server',
            'name' => $name,
            'data' => json_encode($data),
        ]);
    }
}
