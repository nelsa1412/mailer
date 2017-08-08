<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Acelle\Model\MailList;
use Acelle\Model\EmailVerificationServer;

class MailListController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('auth', [
            'except' => [
                'embeddedFormSubscribe',
                'embeddedFormCaptcha',
            ]
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $customer = $request->user()->customer;
        $lists = $customer->lists;

        return view('lists.index', [
            'lists' => $lists,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        $lists = \Acelle\Model\MailList::search($request)->paginate($request->per_page);

        return view('lists._list', [
            'lists' => $lists,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // Generate info
        $customer = $request->user()->customer;
        $list = new \Acelle\Model\MailList(['all_sending_servers' => true]);
        $list->contact = new \Acelle\Model\Contact();

        if (is_object($customer->contact)) {
            $list->contact->fill($customer->contact->toArray());
            $list->send_to = $customer->contact->email;
        } else {
            $list->send_to = $customer->user->email;
        }

        // default values
        $list->subscribe_confirmation = true;
        $list->send_welcome_email = true;
        $list->unsubscribe_notification = true;

        // authorize
        if (\Gate::denies('create', $list)) {
            return $this->noMoreItem();
        }

        // Get old post values
        if (null !== $request->old()) {
            $list->fill($request->old());
        }
        if (isset($request->old()['contact'])) {
            $list->contact->fill($request->old()['contact']);
        }

        // Sending servers
        if (isset($request->old()['sending_servers'])) {
            $list->mailListsSendingServers = collect([]);
            foreach ($request->old()['sending_servers'] as $key => $param) {
                if ($param['check']) {
                    $server = \Acelle\Model\SendingServer::findByUid($key);
                    $row = new \Acelle\Model\MailListsSendingServer();
                    $row->mail_list_id = $list->id;
                    $row->sending_server_id = $server->id;
                    $row->fitness = $param['fitness'];
                    $list->mailListsSendingServers->push($row);
                }
            }
        }

        return view('lists.create', [
            'list' => $list,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Generate info
        $customer = $request->user()->customer;
        $list = new \Acelle\Model\MailList();

        // authorize
        if (\Gate::denies('create', $list)) {
            return $this->noMoreItem();
        }

        // validate and save posted data
        if ($request->isMethod('post')) {
            $this->validate($request, \Acelle\Model\MailList::$rules);

            $rules = [];
            if (isset($request->sending_servers)) {
                foreach ($request->sending_servers as $key => $param) {
                    if ($param['check']) {
                        $rules['sending_servers.'.$key.'.fitness'] = 'required';
                    }
                }
            }
            $this->validate($request, $rules);

            // Save contact
            $contact = \Acelle\Model\Contact::create($request->all()['contact']);
            $list->fill($request->all());
            $list->customer_id = $customer->id;
            $list->contact_id = $contact->id;
            $list->save();

            // For sending servers
            if (isset($request->sending_servers)) {
                $list->updateSendingServers($request->sending_servers);
            }

            // Trigger updating related campaigns cache
            $list->updateCachedInfo();

            // Log
            $list->log('created', $request->user()->customer);

            // Redirect to my lists page
            $request->session()->flash('alert-success', trans('messages.list.created'));

            return redirect()->action('MailListController@index');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $uid)
    {
        // Generate info
        $customer = $request->user()->customer;
        $list = \Acelle\Model\MailList::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $list)) {
            return $this->notAuthorized();
        }

        // Get old post values
        if (null !== $request->old()) {
            $list->fill($request->old());
        }
        if (isset($request->old()['contact'])) {
            $list->contact->fill($request->old()['contact']);
        }

        // Sending servers
        if (isset($request->old()['sending_servers'])) {
            $list->mailListsSendingServers = collect([]);
            foreach ($request->old()['sending_servers'] as $key => $param) {
                if ($param['check']) {
                    $server = \Acelle\Model\SendingServer::findByUid($key);
                    $row = new \Acelle\Model\MailListsSendingServer();
                    $row->mail_list_id = $list->id;
                    $row->sending_server_id = $server->id;
                    $row->fitness = $param['fitness'];
                    $list->mailListsSendingServers->push($row);
                }
            }
        }

        return view('lists.edit', [
            'list' => $list,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Generate info
        $customer = $request->user()->customer;
        $list = \Acelle\Model\MailList::findByUid($request->uid);

        // authorize
        if (\Gate::denies('update', $list)) {
            return $this->notAuthorized();
        }

        // validate and save posted data
        if ($request->isMethod('patch')) {
            $this->validate($request, \Acelle\Model\MailList::$rules);

            $rules = [];
            if (isset($request->sending_servers)) {
                foreach ($request->sending_servers as $key => $param) {
                    if ($param['check']) {
                        $rules['sending_servers.'.$key.'.fitness'] = 'required';
                    }
                }
            }
            $this->validate($request, $rules);

            // Save contact
            $list->contact->fill($request->all()['contact']);
            $list->contact->save();
            $list->fill($request->all());
            $list->save();

            // For sending servers
            if (isset($request->sending_servers)) {
                $list->updateSendingServers($request->sending_servers);
            }

            // Log
            $list->log('updated', $request->user()->customer);

            // Redirect to my lists page
            $request->session()->flash('alert-success', trans('messages.list.updated'));

            return redirect()->action('MailListController@edit', $list->uid);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }

    /**
     * Custom sort items.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function sort(Request $request)
    {
        $sort = json_decode($request->sort);
        foreach ($sort as $row) {
            $list = \Acelle\Model\MailList::findByUid($row[0]);

            // authorize
            if (\Gate::denies('update', $list)) {
                return $this->notAuthorized();
            }

            $list->custom_order = $row[1];
            $list->save();
        }

        echo trans('messages.lists.custom_order.updated');
    }

    /**
     * Delete confirm message.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteConfirm(Request $request)
    {
        $lists = \Acelle\Model\MailList::whereIn('uid', explode(',', $request->uids));

        return view('lists.delete_confirm', [
            'lists' => $lists,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        if (isSiteDemo()) {
            echo trans('messages.operation_not_allowed_in_demo');
            return;
        }

        $lists = \Acelle\Model\MailList::whereIn('uid', explode(',', $request->uids));

        foreach ($lists->get() as $item) {
            // authorize
            if (\Gate::allows('delete', $item)) {
                $item->delete();

                // not needed as the related campaigns will be deleted as well
                // $item->updateCachedInfo();

                // Log
                $item->log('deleted', $request->user()->customer);
            }
        }

        // Redirect to my lists page
        echo trans('messages.lists.deleted');
    }

    /**
     * List overview.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function overview(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->uid);

        event(new \Acelle\Events\MailListUpdated($list));

        // authorize
        if (\Gate::denies('read', $list)) {
            return $this->notAuthorized();
        }

        return view('lists.overview', [
            'list' => $list,
        ]);
    }

    /**
     * List growth chart content.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function listGrowthChart(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->uid);

        if (is_object($list)) {
            $list_id = $list->id;
        } else {
            $list_id = null;
            $list = new \Acelle\Model\MailList();
            $list->customer_id = $request->user()->customer->id;
        }

        // authorize
        if (\Gate::denies('read', $list)) {
            return $this->notAuthorized();
        }

        $result = [
            'columns' => [],
            'data' => [],
            'bar_names' => [trans('messages.subscriber_growth')],
        ];

        // columns
        for ($i = 2; $i >= 0; --$i) {
            $result['columns'][] = \Carbon\Carbon::now()->subMonthsNoOverflow($i)->format('m/Y');
        }

        // datas
        foreach ($result['bar_names'] as $bar) {
            $data = [];
            for ($i = 2; $i >= 0; --$i) {
                $data[] = \Acelle\Model\Customer::subscribersCountByTime(
                    \Carbon\Carbon::now()->subMonthsNoOverflow($i)->startOfMonth(),
                    \Carbon\Carbon::now()->subMonthsNoOverflow($i)->endOfMonth(),
                    $request->user()->customer->id,
                    $list_id
                );
            }

            $result['data'][] = [
                'name' => $bar,
                'type' => 'bar',
                'data' => $data,
                'itemStyle' => [
                    'normal' => [
                        'label' => [
                            'show' => true,
                            'textStyle' => [
                                'fontWeight' => 500,
                            ],
                        ],
                    ],
                ],
            ];
        }

        return json_encode($result);
    }

    /**
     * Chart statistics chart.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function statisticsChart(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->uid);

        if (is_object($list)) {
            $list_id = $list->id;
        } else {
            $list_id = null;
            $list = new \Acelle\Model\MailList();
            $list->customer_id = $request->user()->customer->id;
        }

        // authorize
        if (\Gate::denies('read', $list)) {
            return $this->notAuthorized();
        }

        $result = [
            'title' => '', // trans('messages.statistics') . " (" . $list->subscribers()->count() ." " . trans('messages.subscribers') .")",
            'columns' => [],
            'data' => [],
            'bar_names' => [],
        ];

        $datas = [];
        if (isset($list->id)) {
            // create data
            if ($list->subscribeCount()) {
                $result['bar_names'][] = trans('messages.subscribed');
                $datas[] = ['value' => $list->subscribeCount(), 'name' => trans('messages.subscribed')];
            }
            // create data
            if ($list->unsubscribeCount()) {
                $result['bar_names'][] = trans('messages.unsubscribed');
                $datas[] = ['value' => $list->unsubscribeCount(), 'name' => trans('messages.unsubscribed')];
            }
            // create data
            if ($list->unconfirmedCount()) {
                $result['bar_names'][] = trans('messages.unconfirmed');
                $datas[] = ['value' => $list->unconfirmedCount(), 'name' => trans('messages.unconfirmed')];
            }
            // create data
            if ($list->blacklistedCount()) {
                $result['bar_names'][] = trans('messages.blacklisted');
                $datas[] = ['value' => $list->blacklistedCount(), 'name' => trans('messages.blacklisted')];
            }
            // create data
            if ($list->spamReportedCount()) {
                $result['bar_names'][] = trans('messages.spam_reported');
                $datas[] = ['value' => $list->spamReportedCount(), 'name' => trans('messages.spam_reported')];
            }
        } else {
            // create data
            if (\Acelle\Model\MailList::subscribersCountByStatus('subscribed', $request->user()->customer)) {
                $result['bar_names'][] = trans('messages.subscribed');
                $datas[] = ['value' => \Acelle\Model\MailList::subscribersCountByStatus('subscribed', $request->user()->customer), 'name' => trans('messages.subscribed')];
            }
            // create data
            if (\Acelle\Model\MailList::subscribersCountByStatus('unsubscribed', $request->user()->customer)) {
                $result['bar_names'][] = trans('messages.unsubscribed');
                $datas[] = ['value' => \Acelle\Model\MailList::subscribersCountByStatus('unsubscribed', $request->user()->customer), 'name' => trans('messages.unsubscribed')];
            }
            // create data
            if (\Acelle\Model\MailList::subscribersCountByStatus('unconfirmed', $request->user()->customer)) {
                $result['bar_names'][] = trans('messages.unconfirmed');
                $datas[] = ['value' => \Acelle\Model\MailList::subscribersCountByStatus('unconfirmed', $request->user()->customer), 'name' => trans('messages.unconfirmed')];
            }
            // create data
            if (\Acelle\Model\MailList::subscribersCountByStatus('blacklisted', $request->user()->customer)) {
                $result['bar_names'][] = trans('messages.blacklisted');
                $datas[] = ['value' => \Acelle\Model\MailList::subscribersCountByStatus('blacklisted', $request->user()->customer), 'name' => trans('messages.blacklisted')];
            }
            // create data
            if (\Acelle\Model\MailList::subscribersCountByStatus('spam-reported', $request->user()->customer)) {
                $result['bar_names'][] = trans('messages.spam_reported');
                $datas[] = ['value' => \Acelle\Model\MailList::subscribersCountByStatus('spam-reported', $request->user()->customer), 'name' => trans('messages.spam_reported')];
            }
        }

        // datas
        $result['data'][] = [
            'name' => trans('messages.statistics'),
            'type' => 'pie',
            'radius' => '70%',
            'center' => ['50%', '57.5%'],
            'data' => $datas
        ];

        $result['pie'] = 1;

        return json_encode($result);
    }

    /**
     * Quick view.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function quickView(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->uid);

        if (!is_object($list)) {
            $list = new \Acelle\Model\MailList();
            $list->uid = '000';
            $list->customer_id = $request->user()->customer->id;
        }

        // authorize
        if (\Gate::denies('read', $list)) {
            return $this->notAuthorized();
        }

        return view('lists._quick_view', [
            'list' => $list,
        ]);
    }

    /**
     * Copy list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function copy(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->copy_list_uid);

        // authorize
        if (\Gate::denies('update', $list)) {
            return $this->notAuthorized();
        }

        $list->copy($request->copy_list_name);

        echo trans('messages.list.copied');
    }

    /**
     * Embedded Forms.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function embeddedForm(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->uid);

        // authorize
        if (\Gate::denies('read', $list)) {
            return $this->notAuthorized();
        }

        return view('lists.embedded_form', [
            'list' => $list,
        ]);
    }

    /**
     * Embedded Forms.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function embeddedFormFrame(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->uid);

        // authorize
        if (\Gate::denies('read', $list)) {
            return $this->notAuthorized();
        }

        return view('lists.embedded_form_frame', [
            'list' => $list,
        ]);
    }

    /**
     * reCaptcha check.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function embeddedFormCaptcha(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->uid);

        $request->session()->set('form_url', \URL::previous());

        return view('lists.embedded_form_captcha', [
            'list' => $list,
        ]);
    }

    /**
     * Subscribe user from embedded Forms.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function embeddedFormSubscribe(Request $request)
    {
        if (\Acelle\Model\Setting::get('embedded_form_recaptcha') == 'yes') {
            $success = \Acelle\Library\Tool::checkReCaptcha($request);
        } else {
            $success = true;
        }

        $list = \Acelle\Model\MailList::findByUid($request->uid);

        if (!$success) {
            $url = $request->session()->pull('form_url');
            $errs = [trans("messages.invalid_captcha")];
            return view('lists.embedded_form_captcha_invalid', [
                'errors' => $errs,
                'list' => $list,
                'back_link' => $url,
            ]);
        }

        // Create subscriber
        if ($request->isMethod('post')) {
            $subscriber = new \Acelle\Model\Subscriber($request->all());
            $subscriber->mail_list_id = $list->id;
            $subscriber->status = 'unconfirmed';
            $subscriber->from = 'embedded-form';

            // Validation
            $validator = \Validator::make($request->all(), $subscriber->getRules());

            if ($validator->fails()) {
                $url = $request->session()->pull('form_url');
                // $validator->errors()
                $errs = [];
                foreach($validator->errors()->toArray() as $key => $error) {
                    $errs[] = $key . ": " . $error[0];
                }

                if (strpos($url, '?') !== false) {
                    $url = $url . "&" . implode('&', $errs);
                } else {
                    $url = $url . "?" . implode('&', $errs);
                }

                // return redirect()->away($url);
                return view('lists.embedded_form_errors', [
                    'errors' => $errs,
                    'list' => $list,
                    'back_link' => $url,
                ]);
            }

            $subscriber->email = $request->EMAIL;
            $subscriber->ip = $request->ip();
            $subscriber->save();
            // Update field
            $subscriber->updateFields($request->all());

            if($list->subscribe_confirmation) {
                // SEND subscription confirmation email
                $list->sendSubscriptionConfirmationEmail($subscriber);

                return redirect()->action('PageController@signUpThankyouPage', $list->uid);
            } else {
                // change status to subscribed
                $subscriber->updateStatus('subscribed');

                // Send welcome email
                if($list->send_welcome_email) {
                    // SEND subscription confirmation email
                    $list->sendSubscriptionWelcomeEmail($subscriber);
                }

                return redirect()->action('PageController@signUpConfirmationThankyou', [
                        'list_uid' => $list->uid,
                        'uid' => $subscriber->uid,
                        'code' => 'empty',
                    ]
                );
            }
        }
    }

    /**
     * Mail list emails verification main page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function verification(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->uid);

        return view('lists.email_verification', [
            'list' => $list,
        ]);
    }

    /**
     * Start the verification process
     *
     */
    public function startVerification(Request $request)
    {
        $list = MailList::findByUid($request->uid);
        $server = EmailVerificationServer::findByUid($request->email_verification_server_id);
        Log::info("Trying to start verification process for list " . $list->id);
        $list->queueForVerification($server->id);
        return redirect()->action('MailListController@verification', $list->uid);
    }

    /**
     * Stop the verification process
     *
     */
    public function stopVerification(Request $request)
    {
        $list = MailList::findByUid($request->uid);
        $list->stopVerification();
        return redirect()->action('MailListController@verification', $list->uid);
    }

    /**
     * Reset the verification data
     *
     */
    public function resetVerification(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->uid);
        $list->resetVerification();
        return redirect()->action('MailListController@verification', $list->uid);
    }

    /**
     * Check verification progress
     *
     */
    public function verificationProgress(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->uid);
        $percent = $list->getVerifiedSubscribersPercentage();

        if (!$list->isVerificationRunning()) {
            echo 'done';
            $request->session()->flash('alert-success', trans('messages.verification.done'));
            return;
        }

        return view('lists.email_verification_progress', [
            'list' => $list,
        ]);
    }
}
