<?php

/**
 * Campaign class.
 *
 * Model class for campaigns related functionalities.
 * This is the center of the application
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
use Acelle\Library\Log as MailLog;
use Acelle\Library\StringHelper;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use DB;
use Carbon\Carbon;
use Acelle\Model\SystemJob;
use Acelle\Exceptions\CampaignPausedException;
use Acelle\Exceptions\CampaignErrorException;

class Campaign extends Model
{
    // Campaign status
    const STATUS_NEW = 'new';
    const STATUS_READY = 'ready'; // equiv. to 'queue'
    const STATUS_SENDING = 'sending';
    const STATUS_ERROR = 'error';
    const STATUS_DONE = 'done';
    const STATUS_PAUSED = 'paused';

    // Campaign types
    const TYPE_REGULAR = 'regular';
    const TYPE_PLAIN_TEXT = 'plain-text';

    // Campaign settings
    const WORKER_DELAY = 1;
    const DKIM_SELECTOR = 'mailer';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'run_at'];

    public static $required_tags = array(
        array('name' => '{UNSUBSCRIBE_URL}', 'required' => true),
    );

    /**
     * Get the mail list.
     */
    public function defaultMailList()
    {
        if ($this->isAuto()) {
            return $this->autoEvent()->automation->belongsTo('Acelle\Model\MailList', 'default_mail_list_id');
        } else {
            return $this->belongsTo('Acelle\Model\MailList', 'default_mail_list_id');
        }
    }

    /**
     * Get campaign validation rules.
     */
    public function rules()
    {
        return array(
            'name' => 'required',
            'subject' => 'required',
            'from_email' => 'required|email',
            'from_name' => 'required',
            'reply_to' => 'required|email',
        );
    }

    /**
     * Get campaign validation rules.
     */
    public function automatedCampaignRules()
    {
        return array(
            'type' => 'required',
            'name' => 'required',
            'subject' => 'required',
            'from_email' => 'required|email',
            'from_name' => 'required',
            'reply_to' => 'required|email',
        );
    }

    /**
     * Get the auto events of the campaign.
     */
    public function autoEvents()
    {
        return $this->belongsToMany('Acelle\Model\AutoEvent', 'auto_campaigns');
    }

    /**
     * Get the auto event of the campaign.
     */
    public function autoEvent()
    {
        return $this->autoEvents()->first();
    }

    /**
     * Get the links for campaign.
     */
    public function links()
    {
        return $this->belongsToMany('Acelle\Model\Link', 'campaign_links');
    }

    /**
     * Get the customer.
     */
    public function customer()
    {
        return $this->belongsTo('Acelle\Model\Customer');
    }

    /**
     * Get campaign tracking logs.
     *
     * @return mixed
     */
    public function trackingLogs()
    {
        return $this->hasMany('Acelle\Model\TrackingLog');
    }

    /**
     * Get campaign bounce logs.
     *
     * @return mixed
     */
    public function bounceLogs()
    {
        return BounceLog::select('bounce_logs.*')->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'bounce_logs.message_id')
            ->where('tracking_logs.campaign_id', '=', $this->id);
    }

    /**
     * Get campaign open logs.
     *
     * @return mixed
     */
    public function openLogs()
    {
        return OpenLog::select('open_logs.*')->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'open_logs.message_id')
            ->where('tracking_logs.campaign_id', '=', $this->id);
    }

    /**
     * Get campaign click logs.
     *
     * @return mixed
     */
    public function clickLogs()
    {
        return ClickLog::select('click_logs.*')->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'click_logs.message_id')
            ->where('tracking_logs.campaign_id', '=', $this->id);
    }

    /**
     * Get campaign feedback loop logs.
     *
     * @return mixed
     */
    public function feedbackLogs()
    {
        return FeedbackLog::select('feedback_logs.*')->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'feedback_logs.message_id')
            ->where('tracking_logs.campaign_id', '=', $this->id);
    }

    /**
     * Get campaign unsubscribe logs.
     *
     * @return mixed
     */
    public function unsubscribeLogs()
    {
        return UnsubscribeLog::select('unsubscribe_logs.*')->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'unsubscribe_logs.message_id')
            ->where('tracking_logs.campaign_id', '=', $this->id);
    }

    /**
     * Get campaign list segment.
     *
     * @return mixed
     */
    public function listsSegments()
    {
        return $this->hasMany('Acelle\Model\CampaignsListsSegment');
    }

    /**
     * Get campaign lists segments.
     *
     * @return mixed
     */
    public function getListsSegments()
    {
        if($this->isAuto()) {
            return $this->autoEvent()->automation->getListsSegments();
        }

        $lists_segments = $this->listsSegments;

        if($lists_segments->isEmpty()) {
            $lists_segment = new CampaignsListsSegment();
            $lists_segment->campaign_id = $this->id;
            $lists_segment->is_default = true;

            $lists_segments->push($lists_segment);
        }

        return $lists_segments;
    }

    /**
     * Get campaign lists segments group by list.
     *
     * @return mixed
     */
    public function getListsSegmentsGroups()
    {
        if($this->isAuto()) {
            return $this->autoEvent()->automation->getListsSegmentsGroups();
        }

        $lists_segments = $this->getListsSegments();
        $groups = [];

        foreach($lists_segments as $lists_segment) {
            if(!isset($groups[$lists_segment->mail_list_id])) {
                $groups[$lists_segment->mail_list_id] = [];
                $groups[$lists_segment->mail_list_id]['list'] = $lists_segment->mailList;
                if($this->default_mail_list_id == $lists_segment->mail_list_id) {
                    $groups[$lists_segment->mail_list_id]['is_default'] = true;
                } else {
                    $groups[$lists_segment->mail_list_id]['is_default'] = false;
                }
                $groups[$lists_segment->mail_list_id]['segment_uids'] = [];
            }
            if(is_object($lists_segment->segment) && !in_array($lists_segment->segment->uid, $groups[$lists_segment->mail_list_id]['segment_uids'])) {
                $groups[$lists_segment->mail_list_id]['segment_uids'][] = $lists_segment->segment->uid;
            }
        }

        return $groups;
    }

    /**
     * Reset max_execution_time so that command can run for a long time without being terminated
     *
     * @return mixed
     */
    public static function resetMaxExecutionTime() {
        try {
            set_time_limit(0);
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '-1');
        } catch (\Exception $e) {
            MailLog::warning('Cannot reset max_execution_time: '.$e->getMessage());
        }
    }

    /**
     * Start the campaign
     *
     */
    public function start($trigger = null, $subscribers = null) {
        try {
            // make sure the same campaign is not started twice
            if (!$this->refreshStatus()->isReady()) {
                MailLog::info("Campaign ID: {$this->id} is not ready or already started");
                return;
            }

            // lock the campaign, marked as sending
            $this->sending();
            MailLog::info('Starting campaign `'.$this->name.'`');

            // Reset max_execution_time so that command can run for a long time without being terminated
            self::resetMaxExecutionTime();

            // Only run multi-process if pcntl is enabled
            $processes = (int) $this->customer->getOption('max_process');
            if (extension_loaded('pcntl') && $processes > 1) {
                MailLog::info('Run in multi-process mode');
                $this->runMultiProcesses();
            } else {
                MailLog::info('Run in single-process mode');
                $this->run($subscribers);
            }
            MailLog::info('Finish campaign `'.$this->name.'`');

            // runMultiProcesses() and run() are responsible for setting DONE status
        } catch (\Exception $ex) {
            // Set ERROR status
            $this->error($ex->getMessage());
            MailLog::error('Starting campaign failed. '.$ex->getMessage());
        }
    }

    /**
     * Mark the campaign as 'done' or 'sent'.
     */
    public function done()
    {
        $this->status = self::STATUS_DONE;
        $this->save();
    }

    /**
     * Mark the campaign as 'sending'.
     */
    public function sending()
    {
        $this->status = self::STATUS_SENDING;
        $this->delivery_at = \Carbon\Carbon::now();
        $this->save();
    }

    /**
     * Check if the campaign is in the "SENDING" status;
     */
    public function isSending()
    {
        return $this->status == self::STATUS_SENDING;
    }

    /**
     * Check if the campaign is ready to start
     */
    public function isReady()
    {
        return $this->status == self::STATUS_READY;
    }

    /**
     * Mark the campaign as 'ready' (which is equiv. to 'queued')
     */
    public function ready()
    {
        $this->status = self::STATUS_READY;
        $this->save();
    }

    /**
     * Mark the campaign as 'done' or 'sent'.
     */
    public function error($error = null)
    {
        $this->status = self::STATUS_ERROR;
        $this->last_error = $error;
        $this->save();
    }

    /**
     * Mark the campaign as 'done' or 'sent'.
     */
    public function refreshStatus()
    {
        $me = self::find($this->id);
        $this->status = $me->status;
        $this->save();

        return $this;
    }

    /**
     * Get campaign's email chunks.
     *
     * @return mixed
     */
    public function getChunks($collection, $count)
    {
        $result = [];
        if (sizeof($collection) == 0) {
            return $result;
        }
        $chunks = array_chunk(range(0, sizeof($collection) - 1), ceil(sizeof($collection) / (float) $count), true);
        foreach ($chunks as $key => $chunk) {
            $list = [];
            foreach (array_values($chunk) as $i) {
                $list[] = $collection[$i];
            }
            $result[] = $list;
        }

        return $result;
    }

    /**
     * Start the campaign, using PHP fork() to launch multiple processes.
     *
     * @return mixed
     */
    public function runMultiProcesses()
    {
        $count = (int) $this->customer->getOption('max_process');
        $subscribers = $this->getPendingSubscribers();
        MailLog::info(sizeof($subscribers).' subscriber(s) selected');

        // split the big subscribers list into smaller ones
        // this is also the number of processes to fork
        $chunks = $this->getChunks($subscribers, $count);

        MailLog::info('Forking '.sizeof($chunks).' process(es)');
        $parentPid = getmypid();
        $children = [];
        for ($i = 0; $i < sizeof($chunks); ++$i) {
            $pid = pcntl_fork();

            // for child process only
            if (!$pid) {
                // Reconnect to the DB to prevent connection closed issue when using fork
                DB::reconnect('mysql');

                // Re-initialize logging to capture the child process' PID
                MailLog::fork();

                MailLog::info('Start child process '.($i + 1).' of '.sizeof($chunks).' (forked from '.$parentPid.')');
                sleep(self::WORKER_DELAY);
                $this->run($chunks[$i]);

                exit($i + 1);
                // end child process
            } else {
                $children[] = $pid;
            }
        }

        // wait for child processes to finish
        foreach($children as $child) {
            $pid = pcntl_wait($status);
            if (pcntl_wifexited($status)) {
                $code = pcntl_wexitstatus($status);
                MailLog::info("Child process $pid finished, status code: $code");
            } else {
                MailLog::warning("Child process $pid did not normally exit");
                $this->error("Child process $pid did not normally exit");
            }
        }

        // after all child processes are done
        $this->refreshStatus();

        // If all child processes finish sucessfully, just mark campaign as done
        // Otherwise, mark campaign with error status (by child process)
        //
        // There are conventions here:
        //   + Child process does not update status from SENDING to DONE, it is left for the parent process
        //   + Child process only updates status from SENDING to ERROR
        //   + Parent process only updates status to DONE when current status is SENDING
        //     indicating that all child processes finish sucessfully
        //   + In case one child process update the status from SENDING to ERROR, it is left as the final status
        //     the latest error is kept
        if ($this->status == self::STATUS_SENDING) {
            $this->done();
        } else {
            // leave the latest error reported by child process
        }
    }

    /**
     * Send Campaign
     * Iterate through all subscribers and send email.
     */
    public function run($subscribers = null)
    {
        // check if the method is trigger by a child process (triggered by startMultiProcess method)
        $asChildProcess = !($subscribers == null);

        // try/catch to make sure child process does not stop without reporting any error
        try {
            if (!$asChildProcess) {
                $subscribers = $this->getPendingSubscribers();
            }

            $i = 0;
            foreach ($subscribers as $subscriber) {
                // @todo: customerneedcheck
                if ($this->customer->overQuota()) {
                    throw new \Exception("Customer has reached sending limit");
                }

                // randomly check for campaign PAUSED status after 10 emails
                // @todo wrap up the probability checking here
                if (($i % 50) == 0) {
                    if ($this->refreshStatus()->isPaused()) {
                        event(new \Acelle\Events\CampaignUpdated($this, false));
                        throw new CampaignPausedException();
                    }
                }

                $i += 1;
                MailLog::info("Sending to subscriber `{$subscriber->email}` ({$i}/".sizeof($subscribers).')');

                // Pick up an available sending server
                // Throw exception in case no server available
                $server = $this->pickSendingServer($this);

                list($message, $msgId) = $this->prepareEmail($subscriber, $server);

                $sent = $server->send($message);

                $this->trackMessage($sent, $subscriber, $server, $msgId);
            }

            // only mark campaign as done when running as its own process
            // as a child process, just finish and leave the parent process to update campaign status
            if (!$asChildProcess) {
                $this->done();
            }

        } catch (CampaignPausedException $e) {
            // just finish
            MailLog::warning('Campaign paused');
        } catch (CampaignErrorException $e) {
            // just finish
            MailLog::warning('Campaign terminated: ' . $e->getMessage());
        } catch (\Exception $e) {
            MailLog::error($e->getMessage());
            $this->error($e->getMessage());
        } finally {
            // reset server pools: just in case DeliveryServerAmazonSesWebApi
            // --> setup SNS requires using from_email of the corresponding campaign
            // but SNS is only made once when the server is initiated
            // UPDATE: this is no longer the case as one process now only deals with one campaign
            //         however, the method is still needed to save sending servers''
            MailList::resetServerPools();

            // store customer quota usage
            $this->customer->saveQuotaUsageInfo();

            // Trigger the CampaignUpdate event to update the campaign cache information
            // The second parameter of the constructor function is false, meanining immediate update
            event(new \Acelle\Events\CampaignUpdated($this, false));
            event(new \Acelle\Events\MailListUpdated($this, false));
        }
    }

    /**
     * Log delivery message, used for later tracking.
     *
     */
    public function trackMessage($response, $subscriber, $server, $msgId)
    {
        // @todo: customerneedcheck
        $params = array_merge(array(
                'campaign_id' => $this->id,
                'message_id' => $msgId,
                'subscriber_id' => $subscriber->id,
                'sending_server_id' => $server->id,
                'customer_id' => $this->customer->id,
            ), $response);

        if (!isset($params['runtime_message_id'])) {
            $params['runtime_message_id'] = $msgId;
        }

        // create tracking log for message
        TrackingLog::create($params);

        // increment customer quota usage
        $this->customer->countUsage();
        $server->countUsage();
    }

    /**
     * Get tagged Subject
     *
     * @return String
     */
    public function getSubject($subscriber, $msgId) {
        return $this->tagMessage($this->subject, $subscriber, $msgId);
    }

    /**
     * Pick up a delivery server for the campaign.
     *
     * @return mixed
     */
    public function pickSendingServer()
    {
        return $this->defaultMailList->pickSendingServer();
    }

    /**
     * Transform Tags
     * Transform tags to actual values before sending.
     */
    public function tagMessage($message, $subscriber, $msgId)
    {
        // @todo consider a solution for UNSUBSCRIBE_URL for test subscriber (also for other tags like: UPDATE_PROFILE_URL)

        $tags = array(
            'SUBSCRIBER_EMAIL' => $subscriber->email,
            'CAMPAIGN_NAME' => $this->name,
            'CAMPAIGN_UID' => $this->uid,
            'CAMPAIGN_SUBJECT' => $this->subject,
            'CAMPAIGN_FROM_EMAIL' => $this->from_email,
            'CAMPAIGN_FROM_NAME' => $this->from_name,
            'CAMPAIGN_REPLY_TO' => $this->reply_to,
            'SUBSCRIBER_UID' => $subscriber->uid,
            'CURRENT_YEAR' => date('Y'),
            'CURRENT_MONTH' => date('m'),
            'CURRENT_DAY' => date('d'),
            'UNSUBSCRIBE_URL' => str_replace('MESSAGE_ID', StringHelper::base64UrlEncode($msgId), Setting::get('url_unsubscribe')),
            'CONTACT_NAME' => $this->defaultMailList->contact->company,
            'CONTACT_COUNTRY' => $this->defaultMailList->contact->country->name,
            'CONTACT_STATE' => $this->defaultMailList->contact->state,
            'CONTACT_CITY' => $this->defaultMailList->contact->city,
            'CONTACT_ADDRESS_1' => $this->defaultMailList->contact->address_1,
            'CONTACT_ADDRESS_2' => $this->defaultMailList->contact->address_2,
            'CONTACT_PHONE' => $this->defaultMailList->contact->phone,
            'CONTACT_URL' => $this->defaultMailList->contact->url,
            'CONTACT_EMAIL' => $this->defaultMailList->contact->email,
            'LIST_NAME' => $this->defaultMailList->name,
            'LIST_SUBJECT' => $this->defaultMailList->default_subject,
            'LIST_FROM_NAME' => $this->defaultMailList->from_name,
            'LIST_FROM_EMAIL' => $this->defaultMailList->from_email,
            'WEB_VIEW_URL' => str_replace('MESSAGE_ID', StringHelper::base64UrlEncode($msgId), Setting::get('url_web_view')),
        );

        // UPDATE_PROFILE_URL
        if(!$this->isStdClassSubscriber($subscriber)) {
            // in case of actually sending campaign
            $tags['UPDATE_PROFILE_URL'] = str_replace('LIST_UID', $this->defaultMailList->uid,
                str_replace('SUBSCRIBER_UID', $subscriber->uid,
                str_replace('SECURE_CODE', $subscriber->getSecurityToken('update-profile'), Setting::get('url_update_profile'))));
        }

        // Update tags layout
        foreach ($tags as $tag => $value) {
            $message = str_replace('{'.$tag.'}', $value, $message);
        }

        if (!$this->isStdClassSubscriber($subscriber)) {
            // in case of actually sending campaign
            foreach ($this->defaultMailList->fields as $field) {
                $message = str_replace('{SUBSCRIBER_'.$field->tag.'}', $subscriber->getValueByField($field), $message);
            }
        } else {
            // in case of sending test email
            // @todo how to manage such tags?
            $message = str_replace('{SUBSCRIBER_EMAIL}', $subscriber->email, $message);
        }

        return $message;
    }

    /**
     * Get Pending Subscribers
     * Select only subscribers that are ready for sending. Those whose status is `blacklisted`, `pending` or `unconfirmed` are not included.
     */
    public function getPendingSubscribers()
    {
        /* *
         *
         * Using LEFT JOIN is much slower than NOT IN
         *
        $subscribers = $this->subscribers()
                ->leftJoin(DB::raw('(SELECT * FROM '.DB::getTablePrefix().'tracking_logs WHERE campaign_id = '.$this->id.') log'), 'subscribers.id', '=', 'subscriber_id')
                ->whereRaw('log.id IS NULL')
                ->whereRaw(DB::getTablePrefix()."subscribers.status = '".Subscriber::STATUS_SUBSCRIBED."'")
                ->select('subscribers.*')
                ->get();
        */

        $subscribers = $this->subscribers()
                ->whereRaw(sprintf(table('subscribers').'.id NOT IN (SELECT subscriber_id FROM %s WHERE campaign_id = %s)', table('tracking_logs'), $this->id))
                ->whereRaw(DB::getTablePrefix()."subscribers.status = '".Subscriber::STATUS_SUBSCRIBED."'")
                ->select('subscribers.email')
                ->addSelect('subscribers.uid')
                ->addSelect('subscribers.id')
                ->get();

        // the array_unique_by functions out-perform the Collection::unique of Laravel!!!
        $unique = array_unique_by($subscribers, function ($r) {
            return $r['email'];
        });

        return $unique;
    }

    /**
     * Append Open Tracking URL
     * Append open-tracking URL to every email message.
     */
    public function appendOpenTrackingUrl($body, $msgId)
    {
        $openTrackingBaseURL = Setting::get('url_open_track');
        $tracking_url = str_replace('MESSAGE_ID', StringHelper::base64UrlEncode($msgId), $openTrackingBaseURL);

        return $body.'<img src="'.$tracking_url.'" width="0" height="0" alt="" style="visibility:hidden" />';
    }

    /**
     * Build Email Custom Headers
     *
     * @return Hash list of custom headers
     */
    public function getCustomHeaders($subscriber, $server) {
        $msgId = StringHelper::generateMessageId(StringHelper::getDomainFromEmail($this->from_email));

        return array(
            'X-Acelle-Campaign-Id' => $this->uid,
            'X-Acelle-Subscriber-Id' => $subscriber->uid,
            'X-Acelle-Customer-Id' => $this->customer->uid,
            'X-Acelle-Message-Id' => $msgId,
            'X-Acelle-Sending-Server-Id' => $server->uid,
            'List-Unsubscribe' => '<'.str_replace('MESSAGE_ID', StringHelper::base64UrlEncode($msgId), Setting::get('url_unsubscribe')).'>',
            'Precedence' => 'bulk'
        );
    }

    /**
     * Build Email HTML content
     *
     * @return String
     */
    public function getHtmlContent($subscriber, $msgId) {
        // @note: IMPORTANT: the order must be as follows
        // * addTrackingURL
        // * appendOpenTrackingUrl
        // * tagMessage

        // @note: addTrackingUrl() must go before appendOpenTrackingUrl()
        $body = $this->html;

        // Enable click tracking
        if ($this->track_click) {
            $body = $this->addTrackingUrl($body, $msgId);
        }

        // Enable open tracking
        if ($this->track_open) {
            $body = $this->appendOpenTrackingUrl($body, $msgId);
        }

        // Transform tags
        $body = $this->tagMessage($body, $subscriber, $msgId);

        // Transform CSS/HTML content to inline CSS
        $body = $this->inlineHtml($body);

        return $body;
    }

    /**
     * Build Email HTML content
     *
     * @return String
     */
    public function getPlainContent($subscriber, $msgId) {
        $plain = $this->tagMessage($this->plain, $subscriber, $msgId);

        return $plain;
    }

    /**
     * Prepare the email content using Swift Mailer.
     *
     * @input object subscriber
     * @input object sending server
     *
     * @return MIME text message
     */
    public function prepareEmail($subscriber, $server)
    {
        // build the message
        $customHeaders = $this->getCustomHeaders($subscriber, $this);
        $msgId = $customHeaders['X-Acelle-Message-Id'];

        $message = \Swift_Message::newInstance();
        $message->setId($msgId);

        if ($this->type == self::TYPE_REGULAR) {
            $message->setContentType('text/html; charset=utf-8');
        } else {
            $message->setContentType('text/plain; charset=utf-8');
        }

        foreach($customHeaders as $key => $value) {
            $message->getHeaders()->addTextHeader($key, $value);
        }

        // @TODO for AWS, setting returnPath requires verified domain or email address
        if ($server->allowCustomReturnPath()) {
            $returnPath = $server->getVerp($subscriber->email);
            if ($returnPath) {
                $message->setReturnPath($returnPath);
            }
        }
        $message->setSubject($this->getSubject($subscriber, $msgId));
        $message->setFrom(array($this->from_email => $this->from_name));
        $message->setTo($subscriber->email);
        $message->setReplyTo($this->reply_to);
        $message->setEncoder(\Swift_Encoding::get8bitEncoding());
        $message->addPart($this->getPlainContent($subscriber, $msgId), 'text/plain');
        if ($this->type == self::TYPE_REGULAR) {
            $message->addPart($this->getHtmlContent($subscriber, $msgId), 'text/html');
        }

        if ($this->sign_dkim) {
            $message = $this->sign($message);
        }

        // @todo attachment
        //$message->attach(Swift_Attachment::fromPath('/tmp/gaugau.csv'));
        return array($message, $msgId);
    }

    /**
     * Find sending domain from email.
     *
     * @return mixed
     */
    public function findSendingDomain($email)
    {
        $domain = substr(strrchr($email, '@'), 1);

        return SendingDomain::where('name', $domain)->first();
    }

    /**
     * Sign the message with DKIM.
     *
     * @return mixed
     */
    public function sign($message)
    {
        $sendingDomain = $this->findSendingDomain($this->from_email);

        if (empty($sendingDomain)) {
            return $message;
        }

        $privateKey = $sendingDomain->dkim_private;
        $domainName = $sendingDomain->name;
        $selector = self::DKIM_SELECTOR;
        $signer = new \Swift_Signers_DKIMSigner($privateKey, $domainName, $selector);
        $signer->ignoreHeader('Return-Path');
        $message->attachSigner($signer);

        return $message;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'subject', 'from_name', 'from_email',
        'reply_to', 'track_open',
        'track_click', 'sign_dkim', 'track_fbl',
        'html', 'plain', 'template_source',
    ];

    /**
     * The rules for validation.
     *
     * @var array
     */
    public static $rules = array(
        'mail_list_uid' => 'required',
    );

    /**
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        return self::select('campaigns.*');
    }

    /**
     * Get select options.
     *
     * @return array
     */
    public static function getSelectOptions($customer = null, $status = null)
    {
        $query = self::getAll();
        if (is_object($customer)) {
            $query = $query->where('customer_id', '=', $customer->id);
        }
        if (isset($status)) {
            $query = $query->where('status', '=', $status);
        }
        $options = $query->orderBy('created_at', 'DESC')->get()->map(function ($item) {
            return ['value' => $item->uid, 'text' => $item->name];
        });

        return $options;
    }

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
            while (Campaign::where('uid', '=', $uid)->count() > 0) {
                $uid = uniqid();
            }
            $item->uid = $uid;

            // Update custom order
            Campaign::getAll()->increment('custom_order', 1);
            $item->custom_order = 0;
        });

        // Created
        static::created(function ($item) {
            // Update links
            $item->updateLinks();
        });

        static::updating(function ($item) {
            // Update links
            $item->updateLinks();
        });
    }

    /**
     * Get current links of campaign.
     */
    public function getLinks()
    {
        return $this->links()->whereIn('url', $this->getUrls())->get();
    }

    /**
     * Get urls from campaign html.
     */
    public function getUrls()
    {
        // Find all links in campaign content
        preg_match_all('/<a[^>]*href=["\'](?<url>http[^"\']*)["\']/i', $this->html, $matches);
        $hrefs = array_unique($matches['url']);

        $urls = [];
        foreach ($hrefs as $href) {
            if (preg_match('/^http/i', $href) && strpos($href, '{UNSUBSCRIBE_URL}') === false) {
                $urls[] = strtolower(trim($href));
            }
        }

        return $urls;
    }

    /**
     * Update campaign links.
     */
    public function updateLinks()
    {
        foreach ($this->getUrls() as $url) {
            $link = Link::where('url', '=', $url)->first();
            if (!is_object($link)) {
                $link = new Link();
                $link->url = $url;
                $link->save();
            }

            // Campaign link
            if ($this->links()->where('url', '=', $url)->count() == 0) {
                $cl = new CampaignLink();
                $cl->campaign_id = $this->id;
                $cl->link_id = $link->id;
                $cl->save();
            }
        }
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
     * CHeck UNSUBSCRIBE_URL.
     *
     * @return object
     */
    public function unsubscribe_url_valid()
    {
        if($this->type != 'plain-text' &&
           \Auth::user()->customer->getOption('unsubscribe_url_required') == 'yes' &&
            strpos($this->html, '{UNSUBSCRIBE_URL}') == false
        ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get max step.
     *
     * @return object
     */
    public function step()
    {
        $step = 0;

        // Step 1
        if (is_object($this->defaultMailList)) {
            $step = 1;
        } else {
            return $step;
        }

        // Step 2
        if (!empty($this->name) && !empty($this->subject) && !empty($this->from_name)
                && !empty($this->from_email) && !empty($this->reply_to)) {
            $step = 2;
        } else {
            return $step;
        }

        // Step 3
        if ((!empty($this->html) || $this->type == 'plain-text') && !empty($this->plain) && $this->unsubscribe_url_valid()) {
            $step = 3;
        } else {
            return $step;
        }

        // Step 4
        if (isset($this->run_at) && $this->run_at != '0000-00-00 00:00:00') {
            $step = 4;
        } else {
            return $step;
        }

        // Step 5
        if ($this->subscribers()->count()) {
            $step = 5;
        } else {
            return $step;
        }

        return $step;
    }

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($request)
    {
        $customer = $request->user()->customer;
        $query = self::where('customer_id', '=', $customer->id);

        // Get campaign from ... (all|normal|automated)
        if($request->source == 'template') {
            $query = $query->where('html', '!=', null);
        } else {
            $query = $query->where('is_auto', '=', false);
        }

        // Keyword
        if (!empty(trim($request->keyword))) {
            $query = $query->where('name', 'like', '%'.$request->keyword.'%');
        }

        // Status
        if (!empty(trim($request->statuses))) {
            $query = $query->whereIn('status', explode(",", $request->statuses));
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

        $query = $query->orderBy($request->sort_order, $request->sort_direction);

        return $query;
    }

    /**
     * Subscribers.
     *
     * @return collect
     */
    public function subscribers($params=[])
    {
        // get subscribers from auto campaign or normal campaign
        if($this->isAuto()) {
            // Get subscriber from auto event
            $query = $this->autoEvent()->subscribers();
        } else {
            if($this->listsSegments->isEmpty()) {
                return collect([]);
            }

            $query = Subscriber::select("subscribers.*");
            // Email verification join
            $query = $query->leftJoin('email_verifications', 'email_verifications.subscriber_id', '=', 'subscribers.id');

            // Get subscriber from mailist and segment
            $conditions = [];
            foreach($this->listsSegments as $lists_segment) {
                if(!empty($lists_segment->segment_id)) {
                    $conds = $lists_segment->segment->getSubscribersConditions();

                    // JOINS...
                    if (!empty($conds['joins'])) {
                        foreach ($conds['joins'] as $joining) {
                            $query = $query->leftJoin($joining['table'], function($join) use ($joining)
                            {
                                $join->on($joining['ons'][0][0], '=', $joining['ons'][0][1]);
                                if (isset($joining['ons'][1])) {
                                    $join->on($joining['ons'][1][0], '=', $joining['ons'][1][1]);
                                }
                            });
                        }
                    }

                    // WHERE...
                    if (!empty($conds['conditions'])) {
                        $conditions[] = $conds['conditions'];
                    }

                } else {
                    $conditions[] = '(' . table('subscribers') . '.mail_list_id = ' . $lists_segment->mail_list_id . ')';
                }
            }

            if (!empty($conditions)) {
                $query = $query->whereRaw('(' . implode(' OR ', $conditions) . ')');
            }
        }

        // Filter
        $filters = isset($params["filters"]) ? $params["filters"] : null;

        if(isset($filters) || $this->status == "done") {
            $query = $query->leftJoin('tracking_logs', 'tracking_logs.subscriber_id', '=', 'subscribers.id');
            $query = $query->where('tracking_logs.id', "!=", "NULL");
            $query = $query->where('tracking_logs.campaign_id', "=", $this->id);
        }

        if(isset($filters)) {
            if(isset($filters["open"])) {
                $equal = ($filters["open"] == "opened") ? "!=" : "=";
                $query = $query->leftJoin('open_logs', 'tracking_logs.message_id', '=', 'open_logs.message_id')
                    ->where('open_logs.id', $equal, null);
            }
            if(isset($filters["click"])) {
                $equal = ($filters["click"] == "clicked") ? "!=" : "=";
                $query = $query->leftJoin('click_logs', 'tracking_logs.message_id', '=', 'click_logs.message_id')
                    ->where('click_logs.id', $equal, null);
            }
            if(isset($filters["tracking_status"])) {
                $val = ($filters["tracking_status"] == "not_sent") ? null : $filters["tracking_status"];
                $query = $query->where('tracking_logs.status', "=", $val);
            }
        }

        // keyword
        if (isset($params["keyword"]) && !empty(trim($params["keyword"]))) {
            foreach (explode(' ', trim($params["keyword"])) as $keyword) {
                $query = $query->leftJoin('subscriber_fields', 'subscribers.id', '=', 'subscriber_fields.subscriber_id');
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('subscribers.email', 'like', '%'.$keyword.'%')
                        ->orWhere('subscriber_fields.value', 'like', '%'.$keyword.'%');
                });
            }
        }

        return $query->distinct('subscribers.email');
    }

    /**
     * Create customer action log.
     *
     * @param string $cat
     * @param Customer   $customer
     * @param array  $add_datas
     */
    public function log($name, $customer, $add_datas = [])
    {
        $data = [
                'id' => $this->id,
                'name' => $this->name,
        ];

        if (is_object($this->defaultMailList)) {
            $data['list_id'] = $this->default_mail_list_id;
            $data['list_name'] = $this->defaultMailList->name;
        }

        if (is_object($this->segment)) {
            $data['segment_id'] = $this->segment_id;
            $data['segment_name'] = $this->segment->name;
        }

        $data = array_merge($data, $add_datas);

        \Acelle\Model\Log::create([
                                'customer_id' => $customer->id,
                                'type' => 'campaign',
                                'name' => $name,
                                'data' => json_encode($data),
                            ]);
    }

    /**
     * Count delivery processed.
     *
     * @return number
     */
    public function trackingCount()
    {
        return $this->trackingLogs()->count();
    }

    /**
     * Count delivery processed.
     *
     * @return number
     */
    public function deliveredCount()
    {
        return $this->trackingLogs()->where('status', '=', 'sent')->count();
    }

    /**
     * Count failed processed.
     *
     * @return number
     */
    public function failedCount()
    {
        return $this->trackingLogs()->where('status', '=', 'failed')->count();
    }

    /**
     * Count delivery success rate.
     *
     * @return number
     */
    public function deliveredRate()
    {
        $subscribers_count = $this->subscribersCount();

        if ($subscribers_count == 0) {
            return 0;
        }

        return round(($this->deliveredCount() / $subscribers_count) * 100, 0);
    }

    /**
     * Count click.
     *
     * @return number
     */
    public function clickCount($start = null, $end = null)
    {
        $query = $this->clickLogs();

        if (isset($start)) {
            $query = $query->where('click_logs.created_at', '>=', $start);
        }
        if (isset($end)) {
            $query = $query->where('click_logs.created_at', '<=', $end);
        }

        return $query->count();
    }

    /**
     * Url count.
     *
     * @return number
     */
    public function urlCount()
    {
        return $this->links()->count();
    }

    /**
     * Click rate.
     *
     * @return number
     */
    public function clickedLinkCount()
    {
        return $this->clickLogs()->distinct('url')->count('url');
    }

    /**
     * Click rate.
     *
     * @return number
     */
    public function clickRate()
    {
        $url_count = $this->urlCount();

        if ($url_count == 0) {
            return 0;
        }

        return round(($this->clickedLinkCount() / $url_count) * 100, 0);
    }

    /**
     * Count unique clicked opened emails.
     *
     * @return number
     */
    public function clickedEmailsCount()
    {
        $query = $this->clickLogs();

        return $query->distinct('subscriber_id')->count('subscriber_id');
    }

    /**
     * Click a link rate.
     *
     * @return number
     */
    public function clickALinkRate()
    {
        $open_count = $this->openCount();

        if ($open_count == 0) {
            return 0;
        }

        return round(($this->clickCount() / $open_count) * 100, 0);
    }

    /**
     * Clicked emails count.
     *
     * @return number
     */
    public function clickedEmailsRate()
    {
        $open_count = $this->openUniqCount();

        if ($open_count == 0) {
            return 0;
        }

        return round(($this->clickedEmailsCount() / $open_count) * 100, 2);
    }

    /**
     * Count click.
     *
     * @return number
     */
    public function clickPerUniqOpen()
    {
        $open_count = $this->openCount();

        if ($open_count == 0) {
            return 0;
        }

        return round(($this->clickCount() / $open_count) * 100, 0);
    }

    /**
     * Count abuse feedback.
     *
     * @return number
     */
    public function abuseFeedbackCount()
    {
        return $this->feedbackLogs()->where('feedback_type', '=', 'abuse')->count();
    }

    /**
     * Count open.
     *
     * @return number
     */
    public function openCount()
    {
        return $this->openLogs()->count();
    }

    /**
     * Not open count.
     *
     * @return number
     */
    public function notOpenCount()
    {
        return $this->subscribers()->count() - $this->openUniqCount();
    }

    /**
     * Count unique open.
     *
     * @return number
     */
    public function openUniqCount($start = null, $end = null)
    {
        $query = $this->openLogs();
        if (isset($start)) {
            $query = $query->where('open_logs.created_at', '>=', $start);
        }
        if (isset($end)) {
            $query = $query->where('open_logs.created_at', '<=', $end);
        }

        return $query->distinct('subscriber_id')->count('subscriber_id');
    }

    /**
     * Open rate.
     *
     * @return number
     */
    public function openRate()
    {
        $delivered_count = $this->deliveredCount();

        if ($delivered_count == 0) {
            return 0;
        }

        return round(($this->openCount() / $delivered_count) * 100, 0);
    }

    /**
     * Not open rate.
     *
     * @return number
     */
    public function notOpenRate()
    {
        $subcribers_count = $this->subscribers()->count();

        if ($subcribers_count == 0) {
            return 0;
        }

        return round(($this->notOpenCount() / $subcribers_count) * 100, 0);
    }

    /**
     * Count unique open rate.
     *
     * @return number
     */
    public function openUniqRate()
    {
        $delivered_count = $this->deliveredCount();

        if ($delivered_count == 0) {
            return 0;
        }

        return round(($this->openUniqCount() / $delivered_count) * 100, 2);
    }

    /**
     * Count bounce back.
     *
     * @return number
     */
    public function feedbackCount()
    {
        return $this->feedbackLogs()->distinct('subscriber_id')->count('subscriber_id');
    }

    /**
     * Count feedback rate.
     *
     * @return number
     */
    public function feedbackRate()
    {
        $delivered_count = $this->deliveredCount();

        if ($delivered_count == 0) {
            return 0;
        }

        return round(($this->feedbackCount() / $delivered_count) * 100, 0);
    }

    /**
     * Count bounce back.
     *
     * @return number
     */
    public function bounceCount()
    {
        return $this->bounceLogs()->distinct('subscriber_id')->count('subscriber_id');
    }

    /**
     * Count bounce rate.
     *
     * @return number
     */
    public function bounceRate()
    {
        $delivered_count = $this->deliveredCount();

        if ($delivered_count == 0) {
            return 0;
        }

        return round(($this->bounceCount() / $delivered_count) * 100, 0);
    }

    /**
     * Count hard bounce.
     *
     * @return number
     */
    public function hardBounceCount()
    {
        return $this->campaign_bounce_logs()->where('bounce_type', '=', 'hard')->distinct('subscriber_id')->count('subscriber_id');
    }

    /**
     * Count hard bounce rate.
     *
     * @return number
     */
    public function hardBounceRate()
    {
        $delivered_processed_count = $this->deliveryProcessedCount();

        if ($delivered_processed_count == 0) {
            return 0;
        }

        return round(($this->hardBounceCount() / $delivered_processed_count) * 100, 0);
    }

    /**
     * Count soft bounce.
     *
     * @return number
     */
    public function softBounceCount()
    {
        return $this->campaign_bounce_logs()->where('bounce_type', '=', 'soft')->distinct('subscriber_id')->count('subscriber_id');
    }

    /**
     * Count soft bounce rate.
     *
     * @return number
     */
    public function softBounceRate()
    {
        $tracking_count = $this->trackingCount();

        if ($tracking_count == 0) {
            return 0;
        }

        return round(($this->softBounceCount() / $tracking_count) * 100, 0);
    }

    /**
     * Count unsubscibe.
     *
     * @return number
     */
    public function unsubscribeCount()
    {
        return $this->unsubscribeLogs()->distinct('subscriber_id')->count('subscriber_id');
    }

    /**
     * Count unsubscibe rate.
     *
     * @return number
     */
    public function unsubscribeRate()
    {
        $delivered_count = $this->deliveredCount();

        if ($delivered_count == 0) {
            return 0;
        }

        return round(($this->unsubscribeCount() / $delivered_count) * 100, 0);
    }

    /**
     * Get last click.
     *
     * @param number $number
     *
     * @return collect
     */
    public function lastClick()
    {
        return $this->clickLogs()->orderBy('created_at', 'desc')->first();
    }

    /**
     * Get last open.
     *
     * @param number $number
     *
     * @return collect
     */
    public function lastOpen()
    {
        return $this->openLogs()->orderBy('created_at', 'desc')->first();
    }

    /**
     * Get last open list.
     *
     * @param number $number
     *
     * @return collect
     */
    public function lastOpens($number)
    {
        return $this->openLogs()->orderBy('created_at', 'desc')->limit($number);
    }

    /**
     * Get most open subscribers.
     *
     * @param number $number
     *
     * @return collect
     */
    public function mostOpenSubscribers($number)
    {
        return \Acelle\Web\Subscriber::selectRaw(\DB::getTablePrefix().'list_subscriber.*, COUNT('.\DB::getTablePrefix().'campaign_track_unsubscribe.id) AS openCount')
                            ->leftJoin('campaign_track_unsubscribe', 'campaign_track_unsubscribe.subscriber_id', '=', 'list_subscriber.subscriber_id')
                            ->where('campaign_track_unsubscribe.campaign_id', '=', $this->campaign_id)
                            ->groupBy('list_subscriber.subscriber_id')
                            ->orderBy('openCount', 'desc')
                            ->limit($number);
    }

    /**
     * Get last opened time.
     *
     * @return datetime
     */
    public function getLastOpen()
    {
        $last = $this->campaign_track_opens()->orderBy('created_at', 'desc')->first();

        return is_object($last) ? $last->created_at : null;
    }

    /**
     * Campaign top 5 opens.
     *
     * @return datetime
     */
    public static function topOpens($number = 5, $customer = null)
    {
        $records = self::select(\DB::raw(\DB::getTablePrefix().'campaigns.*, count(*) as `aggregate`'))
            ->join('tracking_logs', 'tracking_logs.campaign_id', '=', 'campaigns.id')
            ->join('open_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id');

        if (isset($customer)) {
            $records = $records->where('campaigns.customer_id', '=', $customer->id);
        }

        $records = $records->groupBy('campaigns.id')
            ->orderBy('aggregate', 'desc');

        return $records->take($number);
    }

    /**
     * Campaign top 5 clicks.
     *
     * @return datetime
     */
    public static function topClicks($number = 5, $customer = null)
    {
        $records = self::select(\DB::raw(\DB::getTablePrefix().'campaigns.*, count(*) as `aggregate`'))
            ->join('tracking_logs', 'tracking_logs.campaign_id', '=', 'campaigns.id')
            ->join('click_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id');

        if (isset($customer)) {
            $records = $records->where('campaigns.customer_id', '=', $customer->id);
        }

        $records = $records->groupBy('campaigns.id')
                    ->orderBy('aggregate', 'desc');

        return $records->take($number);
    }

    /**
     * Campaign top 5 clicks.
     *
     * @return datetime
     */
    public static function topLinks($number = 5, $customer = null)
    {
        $records = Link::select(\DB::raw(\DB::getTablePrefix().'links.*, count(*) as `aggregate`'))
            ->join('campaign_links', 'campaign_links.link_id', '=', 'links.id')
            ->join('tracking_logs', 'tracking_logs.campaign_id', '=', 'campaign_links.campaign_id')
            ->join('click_logs', function ($join) {
                $join->on('click_logs.message_id', '=', 'tracking_logs.message_id')
                ->on('click_logs.url', '=', 'links.url');
            });

        if (isset($customer)) {
            $records = $records->join('campaigns', 'campaign_links.campaign_id', '=', 'campaigns.id')
                ->where('campaigns.customer_id', '=', $customer->id);
        }

        $records = $records->groupBy('links.id')
            ->orderBy('aggregate', 'desc');

        return $records->take($number);
    }

    /**
     * Campaign top 5 clicks.
     *
     * @return datetime
     */
    public function getTopLinks($number = 5)
    {
        $records = ClickLog::select(\DB::raw(\DB::getTablePrefix().'click_logs.*, count(*) as `aggregate`'))
            ->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'click_logs.message_id')
            ->where('tracking_logs.campaign_id', '=', $this->id);

        $records = $records->groupBy('click_logs.url')
            ->orderBy('aggregate', 'desc');

        return $records->take($number);
    }

    /**
     * Campaign top 5 clicks.
     *
     * @return datetime
     */
    public function getTopOpenSubscribers($number = 5)
    {
        $records = Subscriber::select(\DB::raw(\DB::getTablePrefix().'subscribers.*, count(*) as `aggregate`'))
            ->join('tracking_logs', 'tracking_logs.subscriber_id', '=', 'subscribers.id')
            ->join('open_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
            ->where('campaign_id', '=', $this->id);

        $records = $records->groupBy('tracking_logs.message_id')
            ->orderBy('aggregate', 'desc');

        return $records->take($number);
    }

    /**
     * Recent subscriber opens.
     *
     * @return datetime
     */
    public function getRecentOpenSubscribers($number = 5)
    {
        $records = Subscriber::select(\DB::raw(\DB::getTablePrefix().'subscribers.*, count(*) as `aggregate`'))
            ->join('tracking_logs', 'tracking_logs.subscriber_id', '=', 'subscribers.id')
            ->join('open_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
            ->where('campaign_id', '=', $this->id);

        $records = $records->groupBy('tracking_logs.message_id')
            ->orderBy('aggregate', 'desc');

        return $records->take($number);
    }

    /**
     * Campaign top 5 open location.
     *
     * @return datetime
     */
    public function topLocations($number = 5, $customer = null)
    {
        $records = IpLocation::select(\DB::raw(\DB::getTablePrefix().'ip_locations.*, count(*) as `aggregate`'))
            ->join('open_logs', 'open_logs.ip_address', '=', 'ip_locations.ip_address')
            ->join('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
            ->where('tracking_logs.campaign_id', '=', $this->id);

        if (isset($customer)) {
            $records = $records->join('campaigns', 'tracking_logs.campaign_id', '=', 'campaigns.id')
                ->where('campaigns.customer_id', '=', $customer->id);
        }

        $records = $records->groupBy('open_logs.ip_address')
            ->orderBy('aggregate', 'desc');

        return $records->take($number);
    }

    /**
     * Campaign top 5 open countries.
     *
     * @return datetime
     */
    public function topCountries($number = 5, $customer = null)
    {
        $records = IpLocation::select(\DB::raw(\DB::getTablePrefix().'ip_locations.*, count(*) as `aggregate`'))
            ->join('open_logs', 'open_logs.ip_address', '=', 'ip_locations.ip_address')
            ->join('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
            ->where('tracking_logs.campaign_id', '=', $this->id);

        if (isset($customer)) {
            $records = $records->join('campaigns', 'tracking_logs.campaign_id', '=', 'campaigns.id')
                ->where('campaigns.customer_id', '=', $customer->id);
        }

        $records = $records->groupBy('ip_locations.country_name')
            ->orderBy('aggregate', 'desc');

        return $records->take($number);
    }

    /**
     * Campaign top 5 click countries.
     *
     * @return datetime
     */
    public function topClickCountries($number = 5, $customer = null)
    {
        $records = IpLocation::select(\DB::raw(\DB::getTablePrefix().'ip_locations.*, count(*) as `aggregate`'))
            ->join('click_logs', 'click_logs.ip_address', '=', 'ip_locations.ip_address')
            ->join('tracking_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id')
            ->where('tracking_logs.campaign_id', '=', $this->id);

        if (isset($customer)) {
            $records = $records->join('campaigns', 'tracking_logs.campaign_id', '=', 'campaigns.id')
                ->where('campaigns.customer_id', '=', $customer->id);
        }

        $records = $records->groupBy('ip_locations.country_name')
            ->orderBy('aggregate', 'desc');

        return $records->take($number);
    }

    /**
     * Campaign locations.
     *
     * @return datetime
     */
    public function locations()
    {
        $records = IpLocation::select('ip_locations.*', 'open_logs.created_at as open_at', 'subscribers.email as email')
            ->leftJoin('open_logs', 'open_logs.ip_address', '=', 'ip_locations.ip_address')
            ->leftJoin('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
            ->leftJoin('subscribers', 'subscribers.id', '=', 'tracking_logs.subscriber_id')
            ->where('tracking_logs.campaign_id', '=', $this->id);

        return $records;
    }

    /**
     * Replace link in text by click tracking url.
     *
     * @return text
     * @note addTrackingUrl() must go before appendOpenTrackingUrl()
     */
    public function addTrackingUrl($email_html_content, $msgId)
    {
        if (preg_match_all('/<a[^>]*href=["\'](?<url>http[^"\']*)["\']/i', $email_html_content, $matches)) {
            foreach ($matches[0] as $key => $href) {
                $url = $matches['url'][$key];

                $newUrl = str_replace('URL', StringHelper::base64UrlEncode($url), Setting::get('url_click_track'));
                $newUrl = str_replace('MESSAGE_ID', StringHelper::base64UrlEncode($msgId), $newUrl);
                $newHref = str_replace($url, $newUrl, $href);

                // if the link contains UNSUBSCRIBE URL tag
                if (strpos($href, '{UNSUBSCRIBE_URL}') !== false) {
                    // just do nothing
                } else if (preg_match("/{[A-Z0-9_]+}/", $href)) {
                    // just skip if the url contains a tag. For example: {UPDATE_PROFILE_URL}
                    // @todo: do we track these clicks?
                } else {
                    $email_html_content = str_replace($href, $newHref, $email_html_content);
                }
            }
        }
        return $email_html_content;
    }

    /**
     * Type of campaigns.
     *
     * @return object
     */
    public static function types()
    {
        return [
            'regular' => [
                'icon' => 'icon-magazine'
            ],
            'plain-text' => [
                'icon' => 'icon-file-text2'
            ],
        ];
    }

    /**
     * Copy new campaign.
     */
    public function copy($name)
    {
        $copy = $this->replicate(['cache', 'last_error', 'run_at']);
        $copy->name = $name;
        $copy->created_at = \Carbon\Carbon::now();
        $copy->updated_at = \Carbon\Carbon::now();
        $copy->status = self::STATUS_NEW;
        $copy->custom_order = 0;
        $copy->save();

        // Lists segments
        foreach ($this->listsSegments as $lists_segment) {
            $new_lists_segment = $lists_segment->replicate();
            $new_lists_segment->campaign_id = $copy->id;
            $new_lists_segment->save();
        }

        // update cache
        $copy->updateCache();
    }

    /**
     * Convert html to inline.
     * @todo not very OOP here, consider moving this to a Helper instead
     *
     */
    public function inlineHtml($html)
    {
        // Convert to inline css if template source is builder
        if ($this->template_source == 'builder') {
            $cssToInlineStyles = new CssToInlineStyles();

            $css = file_get_contents(public_path("css/res_email.css"));

            // output
            $html = $cssToInlineStyles->convert(
                $html,
                $css
            );
        }

        return $html;
    }

    /**
     * Send a test email for testing campaign
     */
    public function sendTestEmail($email)
    {
        try {
            MailLog::info('Sending test email for campaign `' . $this->name . '`');
            MailLog::info('Sending test email to `' . $email . '`');

            // @todo: only send a test message when campaign sufficient information is available

            // build a temporary subscriber oject used to pass through the sending methods
            $subscriber = $this->createStdClassSubscriber(['email' => $email]);

            // Pick up an available sending server
            // Throw exception in case no server available
            $server = $this->pickSendingServer();

            // build the message from campaign information
            list($message, $msgId) = $this->prepareEmail($subscriber, $server);

            // actually send
            // @todo consider using queue here
            $result = $server->send($message);

            // examine the result from sending server
            if(array_has($result, 'error')) {
                throw new \Exception($result['error']);
            }

            return [
                "status" => "success",
                "message" => trans('messages.campaign.test_sent')
            ];
        } catch (\Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    /**
     * Get the delay time before sending
     *
     */
    public function getDelayInSeconds()
    {
        $now = Carbon::now();

        if ($now->gte($this->run_at)) {
            return 0;
        } else {
            return $this->run_at->diffInSeconds($now);
        }
    }

    /**
     * Queue campaign for sending
     *
     */
    public function queue($trigger = null, $subscribers = null, $delay = 0)
    {
        $this->ready();
        $job = (new \Acelle\Jobs\RunCampaignJob($this))->delay($this->getDelayInSeconds());
        dispatch($job);
    }

    /**
     * Queue campaign for sending
     *
     */
    public function clearAllJobs()
    {
        // cleanup jobs and system_jobs
        // @todo data should be a JSON field instead
        $systemJobs = SystemJob::where('name', 'Acelle\Jobs\RunCampaignJob')->where('data', $this->id)->get();
        foreach($systemJobs as $systemJob) {
            // @todo what if jobs were already started? check `reserved` field?
            $systemJob->clear();
        }
    }

    /**
     * Re-queue the campaign for sending
     *
     */
    public function requeue()
    {
        // clear all campaign's sendign jobs which are being queue
        $this->clearAllJobs();

        // and queue again
        $this->queue();
    }

    /**
     * Overwrite the delete() method to also clear the pending jobs
     *
     */
    public function delete()
    {
        $this->clearAllJobs();
        parent::delete();
    }

    /**
     * Create a stdClass subscriber (for sending a campaign test email)
     * The campaign sending functions take a subscriber object as input
     * However, a test email address is not yet a subscriber object, so we have to build a fake stdClass object
     * which can be used as a real subscriber
     *
     * @param array $subscriber
     */
    public function createStdClassSubscriber($subscriber)
    {
        // default attributes that are required
        $jsonObj = [
            'uid' => uniqid()
        ];

        // append the customer specified attributes and build a stdClass object
        $stdObj = json_decode(json_encode(array_merge($jsonObj, $subscriber)));

        return $stdObj;
    }

    /**
     * Check if the given variable is a subscriber object (for actually sending a campaign)
     * Or a stdClass subscriber (for sending test email)
     *
     * @param Object $object
     */
    public function isStdClassSubscriber($object) {
        return (get_class($object) == 'stdClass');
    }

    /**
     * Get information from mail list
     *
     * @param void
     */
    public function getInfoFromMailList($list) {
        $this->from_name = !empty($this->from_name) ? $this->from_name : $list->from_name;
        $this->from_email = !empty($this->from_email) ? $this->from_email : $list->from_email;
        $this->subject = !empty($this->subject) ? $this->subject : $list->default_subject;
    }

    /**
     * Check if auto campaign designed.
     *
     * @return object
     */
    public function autoCampaignDesigned()
    {
        $cond = (!empty($this->name) && !empty($this->subject) && !empty($this->from_name)
            && !empty($this->from_email) && !empty($this->reply_to));

        $cond = $cond && ((!empty($this->html) || $this->type == 'plain-text') && !empty($this->plain) && $this->unsubscribe_url_valid());

        return $cond;
    }

    /**
     * Get type select options.
     *
     * @return array
     */
    public static function getTypeSelectOptions()
    {
        return [
            ['text' => trans('messages.' . self::TYPE_REGULAR), 'value' => self::TYPE_REGULAR],
            ['text' => trans('messages.' . self::TYPE_PLAIN_TEXT), 'value' => self::TYPE_PLAIN_TEXT]
        ];
    }

    /**
     * Check if campaign is automated.
     *
     * @return boolean
     */
    public function isAuto()
    {
        return $this->is_auto;
    }

    /**
     * The validation rules for automation trigger.
     *
     * @var array
     */
    public function recipientsRules($params = [])
    {
        $rules = [
            'lists_segments' => 'required'
        ];

        if(isset($params['lists_segments'])) {
            foreach ($params['lists_segments'] as $key => $param) {
                $rules['lists_segments.'.$key.'.mail_list_uid'] = 'required';
            }
        }

        return $rules;
    }

    /**
     * Fill recipients by params.
     *
     * @var void
     */
    public function fillRecipients($params=[])
    {
        if(isset($params['lists_segments'])) {
            foreach ($params['lists_segments'] as $key => $param) {
                $mail_list = null;

                if(!empty($param['mail_list_uid'])) {
                    $mail_list = MailList::findByUid($param['mail_list_uid']);

                    // default mail list id
                    if(isset($param['is_default']) && $param['is_default'] == 'true') {
                        $this->default_mail_list_id = $mail_list->id;
                    }
                }

                if(!empty($param['segment_uids'])) {
                    foreach ($param['segment_uids'] as $segment_uid) {
                        $segment = Segment::findByUid($segment_uid);

                        $lists_segment = new CampaignsListsSegment();
                        $lists_segment->campaign_id = $this->id;
                        if(is_object($mail_list)) {
                            $lists_segment->mail_list_id = $mail_list->id;
                        }
                        $lists_segment->segment_id = $segment->id;
                        $this->listsSegments->push($lists_segment);
                    }
                } else {
                    $lists_segment = new CampaignsListsSegment();
                    $lists_segment->campaign_id = $this->id;
                    if(is_object($mail_list)) {
                        $lists_segment->mail_list_id = $mail_list->id;
                    }
                    $this->listsSegments->push($lists_segment);
                }
            }
        }
    }

    /**
     * Save Recipients.
     *
     * @var void
     */
    public function saveRecipients($params=[])
    {
        // Empty current data
        $this->listsSegments = collect([]);
        // Fill params
        $this->fillRecipients($params);

        $lists_segments_groups = $this->getListsSegmentsGroups();

        $data = [];
        foreach($lists_segments_groups as $lists_segments_group) {
            if(!empty($lists_segments_group['segment_uids'])) {
                foreach($lists_segments_group['segment_uids'] as $segment_uid) {
                    $segment = Segment::findByUid($segment_uid);
                    $data[] = [
                        'campaign_id' => $this->id,
                        'mail_list_id' => $lists_segments_group['list']->id,
                        'segment_id' => $segment->id,
                    ];
                }
            } else {
                $data[] = [
                    'campaign_id' => $this->id,
                    'mail_list_id' => $lists_segments_group['list']->id,
                    'segment_id' => NULL,
                ];
            }
        }

        // Empty old data
        $this->listsSegments()->delete();

        // Insert Data
        CampaignsListsSegment::insert($data);

        // Save campaign with default list id
        $campaign = Campaign::find($this->id);
        $campaign->default_mail_list_id = $this->default_mail_list_id;
        $campaign->save();

        // Trigger the CampaignUpdate event to update the campaign cache information
        // The second parameter of the constructor function is false, meanining immediate update
        event(new \Acelle\Events\CampaignUpdated($campaign, false));
    }

    /**
     * Display Recipients.
     *
     * @var array
     */
    public function displayRecipients()
    {
        if(!is_object($this->defaultMailList)) {
            return "";
        }

        $lines = [];
        foreach($this->getListsSegmentsGroups() as $lists_segments_group) {
            $list_name = $lists_segments_group['list']->name;

            $segment_names = [];
            if(!empty($lists_segments_group['segment_uids'])) {
                foreach($lists_segments_group['segment_uids'] as $segment_uid) {
                    $segment = Segment::findByUid($segment_uid);
                    $segment_names[] = $segment->name;
                }
            }

            if(empty($segment_names)) {
                $lines[] = $list_name;
            } else {
                $lines[] = implode(': ', [$list_name, implode(', ', $segment_names)]);
            }
        }

        return implode(' | ', $lines);
    }

    /**
     * Check if campaign is paused.
     *
     * @return boolean
     */
    public function isPaused()
    {
        return $this->status == self::STATUS_PAUSED;
    }

    /**
     * Update Campaign cached data
     *
     * @return void
     */
    public function updateCache($key = null)
    {
        // cache indexes
        $index = [
            'SubscriberCount' => function(&$campaign) {
                return $campaign->subscribersCount();
            },
            'DeliveredRate' => function(&$campaign) {
                return $campaign->deliveredRate();
            },
            'DeliveredCount' => function(&$campaign) {
                return $campaign->deliveredCount();
            },
            'ClickedRate' => function(&$campaign) {
                return $campaign->clickedEmailsRate();
            },
            'UniqOpenRate' => function(&$campaign) {
                return $campaign->openUniqRate();
            },
            'UniqOpenCount' => function(&$campaign) {
                return $campaign->openUniqCount();
            },
        ];

        // retrieve cached data
        $cache = json_decode($this->cache, true);
        if (is_null($cache)) {
            $cache = [];
        }

        if (is_null($key)) {
            // update all cache
            foreach($index as $key => $callback) {
                $cache[$key] = $callback($this);
            }
        } else {
            // update specific key
            $callback = $index[$key];
            $cache[$key] = $callback($this);
        }

        // write back to the DB
        $this->cache = json_encode($cache);
        $this->save();
    }

    /**
     * Retrieve Campaign cached data
     *
     * @return mixed
     */
    public function readCache($key, $default = null)
    {
        $cache = json_decode($this->cache, true);
        if (is_null($cache)) {
            return $default;
        }
        if (array_key_exists($key, $cache)) {
            if (is_null($cache[$key])) {
                return $default;
            } else {
                return $cache[$key];
            }
        } else {
            return null;
        }
    }

    /**
     * Count sent success rate (success / subscribers count).
     *
     * @return number
     */
    public function sentRate()
    {
        $subscribers_count = $this->subscribers()->count();

        if ($subscribers_count == 0) {
            return 0;
        }

        return round(($this->deliveredCount() / $subscribers_count) * 100, 0);
    }

    /**
     * Count subscribers.
     *
     * @return integer
     */
    public function subscribersCount()
    {
        return $this->subscribers()->count('subscribers.email');
    }

    /**
     * Count unique open by hour.
     *
     * @return number
     */
    public function openUniqHours($start = null, $end = null)
    {
        $query = $this->openLogs()->select('open_logs.created_at');
        if (isset($start)) {
            $query = $query->where('open_logs.created_at', '>=', $start);
        }
        if (isset($end)) {
            $query = $query->where('open_logs.created_at', '<=', $end);
        }

        return $query->orderBy('open_logs.created_at', 'asc')->get()->groupBy(function($date) {
            return \Acelle\Library\Tool::dateTime($date->created_at)->format('H'); // grouping by hours
        });
    }

    /**
     * Count click group by hour.
     *
     * @return number
     */
    public function clickHours($start = null, $end = null)
    {
        $query = $this->clickLogs()->select('click_logs.created_at', 'tracking_logs.subscriber_id');

        if (isset($start)) {
            $query = $query->where('click_logs.created_at', '>=', $start);
        }
        if (isset($end)) {
            $query = $query->where('click_logs.created_at', '<=', $end);
        }

        return $query->orderBy('click_logs.created_at', 'asc')->get()->groupBy(function($date) {
            return \Acelle\Library\Tool::dateTime($date->created_at)->format('H'); // grouping by hours
        });
    }
}
