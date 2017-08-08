<?php

/**
 * Api class.
 *
 * Misc helper tool
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   Acelle Library
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

namespace Acelle\Library;

class ApiHelper
{
    /**
     * Docs content.
     *
     * @var string
     */
    public static function docs()
    {
        $docs = [];

        // LIST
        $docs[] = [
            'view' => 'frontend',
            'title' => trans('messages.LISTS'),
            'functions' => [
                [
                    'method' => 'POST',
                    'uri' => '/api/v1/lists',
                    'description' => trans('messages.add_a_new_list'),
                    'parameters' => [
                        ['name' => '$name', 'description' => 'List\'s name'],
                        ['name' => '$from_email', 'description' => 'Default From email address'],
                        ['name' => '$from_name', 'description' => 'Default From name'],
                        ['name' => '$default_subject', 'description' => 'Default email subject', 'optional' => true],
                        ['name' => 'divider', 'description' => 'Contact information'],
                        ['name' => '$contact[company]', 'description' => 'Name'],
                        ['name' => '$contact[state]', 'description' => 'State / Province / Region'],
                        ['name' => '$contact[address_1]', 'description' => 'Address 1'],
                        ['name' => '$contact[address_2]', 'description' => 'Address 2'],
                        ['name' => '$contact[city]', 'description' => 'City'],
                        ['name' => '$contact[zip]', 'description' => 'Zip / Postal code'],
                        ['name' => '$contact[phone]', 'description' => 'Phone'],
                        ['name' => '$contact[country_id]', 'description' => 'Country id'],
                        ['name' => '$contact[email]', 'description' => 'Email'],
                        ['name' => '$contact[url]', 'description' => 'Home page', 'optional' => true],
                        ['name' => '$subscribe_confirmation', 'description' => 'Send subscription confirmation email (Double Opt-In)'],
                        ['name' => '$send_welcome_email', 'description' => 'Send a final welcome email'],
                        ['name' => '$unsubscribe_notification', 'description' => 'Send unsubscribe notification to subscribers'],
                        ['name' => '$send_welcome_email', 'description' => 'Send a final welcome email'],
                    ],
                    'returns' => 'Creation messages in json',
                    'example' => 'curl -X POST -H "accept:application/json" -G \
' . action("Api\MailListController@store") . '? \
-d api_token=' . \Auth::user()->api_token . ' \
-d name=List+1 \
-d from_email=admin@abccorp.org \
-d from_name=ABC+Corp. \
-d default_subject=Welcome+to+ABC+Corp. \
-d contact[company]=ABC+Corp. \
-d contact[state]=Armagh \
-d contact[address_1]=14+Tottenham+Court+Road+London+England \
-d contact[address_2]=44-46+Morningside+Road+Edinburgh+Scotland+EH10+4BF \
-d contact[city]=Noname \
-d contact[zip]=80000 \
-d contact[phone]=123+456+889 \
-d contact[country_id]={{ \Acelle\Model\Country::first()->id }} \
-d contact[email]=info@abccorp.org \
-d contact[url]=http://www.abccorp.org \
-d subscribe_confirmation=1 \
-d send_welcome_email=1 \
-d unsubscribe_notification=1',
                ],

                [
                    'method' => 'GET',
                    'uri' => '/api/v1/lists',
                    'description' => 'Get information about all lists',
                    'parameters' => [],
                    'returns' => 'List of all user\'s mail lists in json',
                    'example' => 'curl -X GET -H "accept:application/json" -G \
' . action("Api\MailListController@index") . '? \
-d api_token=' . \Auth::user()->api_token,
                ],

                [
                    'method' => 'GET',
                    'uri' => '/api/v1/lists/{uid}',
                    'description' => 'Get information about a specific list',
                    'parameters' => [],
                    'returns' => 'All list informations in json',
                    'example' => 'curl -X GET -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", action("Api\MailListController@show", "-ID-")) . '? \
-d api_token=' . \Auth::user()->api_token,
                ],
            ],
        ];

        // CAMPAIGN
        $docs[] = [
            'view' => 'frontend',
            'title' => trans('messages.CAMPAIGNS'),
            'functions' => [
                [
                    'method' => 'GET',
                    'uri' => '/api/v1/campaigns',
                    'description' => 'Get information about all campaigns',
                    'parameters' => [],
                    'returns' => 'List of all user\'s campaigns in json',
                    'example' => 'curl -X POST -H "accept:application/json" -G \
' . action("Api\CampaignController@index") . '? \
-d api_token=' . \Auth::user()->api_token,
                ],

                [
                    'method' => 'GET',
                    'uri' => '/api/v1/campaigns/{uid}',
                    'description' => 'Get information about a specific campaign',
                    'parameters' => [],
                    'returns' => 'List of list\'s subscribers in json',
                    'example' => 'curl -X GET -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", action("Api\CampaignController@show", "-ID-")) . '? \
-d api_token=' . \Auth::user()->api_token,
                ],
            ],
        ];

        // SUBSCRIBER
        $docs[] = [
            'view' => 'frontend',
            'title' => trans('messages.SUBSCRIBERS'),
            'functions' => [
                [
                    'method' => 'GET',
                    'uri' => '/api/v1/lists/{list_uid}/subscribers',
                    'description' => 'Display list\'s subscribers',
                    'parameters' => [
                        ['name' => '$list_uid', 'description' => 'List\'s uid'],
                        ['name' => '$per_page', 'description' => 'Number of subscribers per page', 'optional' => true, 'default' => 25],
                        ['name' => '$page', 'description' => 'Page number'],
                    ],
                    'returns' => 'List of all list\'s subscribers in json',
                    'example' => 'curl -X POST -H "accept:application/json" -G \
' . str_replace("-LIST_ID-", "<redbold>{list_uid}</redbold>", action("Api\SubscriberController@index", ["list_id" => "-LIST_ID-"])) . '? \
-d api_token=' . \Auth::user()->api_token . ' \
-d per_page=20 \
-d page=2',
                ],

                [
                    'method' => 'GET',
                    'uri' => '/api/v1/lists/{list_uid}/subscribers/{uid}',
                    'description' => 'Get information about a specific subscriber',
                    'parameters' => [
                        ['name' => '$uid', 'description' => 'Subsciber\'s uid or email'],
                    ],
                    'returns' => 'All subscriber information in json',
                    'example' => 'curl -X GET -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", str_replace("-LIST_ID-", "<redbold>{list_uid}</redbold>", action("Api\SubscriberController@show", ["list_id" => "-LIST_ID-",  "id" => "-ID-"]))) . '? \
-d api_token=' . \Auth::user()->api_token,
                ],

                [
                    'method' => 'GET',
                    'uri' => '/api/v1/subscribers/{email}',
                    'description' => 'Find subscribers with email',
                    'parameters' => [
                        ['name' => '$email', 'description' => 'Subsciber\'s email'],
                    ],
                    'returns' => 'All subscribers with the email',
                    'example' => 'curl -X GET -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{email}</redbold>", str_replace("-LIST_ID-", "<redbold>{list_uid}</redbold>", action("Api\SubscriberController@showByEmail", ["email" => "-ID-"]))) . '? \
-d api_token=' . \Auth::user()->api_token,
                ],

                [
                    'method' => 'POST',
                    'uri' => '/api/v1/lists/{list_uid}/subscribers/store',
                    'description' => 'Create subscriber for a mail list',
                    'parameters' => [
                        ['name' => '$EMAIL', 'description' => 'Subscriber\'s email'],
                        ['name' => '$[OTHER_FIELDS...]', 'description' => 'All subscriber\'s other fields: FIRST_NAME (?), LAST_NAME (?),... (depending on the list fields configuration)', 'optional' => true],
                    ],
                    'returns' => 'Creation messages in json',
                    'example' => 'curl -X POST -H "accept:application/json" -G \
' . str_replace("-LIST_ID-", "<redbold>{list_uid}</redbold>", action("Api\SubscriberController@store", ["list_id" => "-LIST_ID-"])) . '? \
-d api_token=' . \Auth::user()->api_token . ' \
-d EMAIL=test@gmail.com \
-d FIRST_NAME=Marine \
-d LAST_NAME=Joze',
                ],

                [
                    'method' => 'PATCH',
                    'uri' => '/api/v1/lists/{list_uid}/subscribers',
                    'description' => 'Update subscriber for a mail list',
                    'parameters' => [
                        ['name' => '$EMAIL', 'description' => 'Subscriber\'s email'],
                        ['name' => '$[OTHER_FIELDS...]', 'description' => 'All subscriber\'s other fields: FIRST_NAME (?), LAST_NAME (?),... (depending on the list fields configuration)', 'optional' => true],
                    ],
                    'returns' => 'Update messages in json',
                    'example' => 'curl -X PATCH -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", str_replace("-LIST_ID-", "<redbold>{list_uid}</redbold>", action("Api\SubscriberController@update", ["list_uid" => "-LIST_ID-",  "uid" => "-ID-"]))) . '? \
-d api_token=' . \Auth::user()->api_token . ' \
-d EMAIL=test@gmail.com \
-d FIRST_NAME=Marine \
-d LAST_NAME=Joze',
                ],

                [
                    'method' => 'PATCH',
                    'uri' => '/api/v1/lists/{list_uid}/subscribers/{uid}/subscribe',
                    'description' => 'Subscribe a subscriber',
                    'parameters' => [
                        ['name' => '$list_uid', 'description' => 'List\'s uid'],
                        ['name' => '$uid', 'description' => 'Subsciber\'s uid'],
                    ],
                    'returns' => 'Result messages in json',
                    'example' => 'curl -X PATCH -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", str_replace("-LIST_ID-", "<redbold>{list_uid}</redbold>", action("Api\SubscriberController@subscribe", ["list_id" => "-LIST_ID-",  "id" => "-ID-"]))) . '? \
-d api_token=' . \Auth::user()->api_token,
                ],

                [
                    'method' => 'PATCH',
                    'uri' => '/api/v1/lists/{list_uid}/subscribers/{uid}/unsubscribe',
                    'description' => 'Unsubscribe a subscriber',
                    'parameters' => [
                        ['name' => '$list_uid', 'description' => 'List\'s uid'],
                        ['name' => '$uid', 'description' => 'Subsciber\'s uid'],
                    ],
                    'returns' => 'Result messages in json',
                    'example' => 'curl -X PATCH -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", str_replace("-LIST_ID-", "<redbold>{list_uid}</redbold>", action("Api\SubscriberController@unsubscribe", ["list_id" => "-LIST_ID-",  "id" => "-ID-"]))) . '? \
-d api_token=' . \Auth::user()->api_token,
                ],

                [
                    'method' => 'DELETE',
                    'uri' => '/api/v1/lists/{list_uid}/subscribers/{uid}/delete',
                    'description' => 'Delete a subscriber',
                    'parameters' => [
                        ['name' => '$list_uid', 'description' => 'List\'s uid'],
                        ['name' => '$uid', 'description' => 'Subsciber\'s uid'],
                    ],
                    'returns' => 'Result messages in json',
                    'example' => 'curl -X DELETE -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", str_replace("-LIST_ID-", "<redbold>{list_uid}</redbold>", action("Api\SubscriberController@delete", ["list_id" => "-LIST_ID-",  "id" => "-ID-"]))) . '? \
-d api_token=' . \Auth::user()->api_token,
                ],
            ],
        ];

        // PLAN
        $docs[] = [
            'view' => 'backend',
            'title' => trans('messages.plan'),
            'functions' => [
                [
                    'method' => 'GET',
                    'uri' => '/api/v1/plans',
                    'description' => 'Get information about all plans',
                    'parameters' => [],
                    'returns' => 'List of all plans in json',
                    'example' => 'curl -X GET -H "accept:application/json" -G \
' . action("Api\PlanController@index") . '? \
-d api_token=' . \Auth::user()->api_token,
                ],
            ],
        ];

        // SENDING SERVER
        $docs[] = [
            'view' => 'backend',
            'title' => trans('messages.SENDING_SERVERS'),
            'functions' => [
                [
                    'method' => 'GET',
                    'uri' => '/api/v1/sending_servers',
                    'description' => 'Get information about all sending servers',
                    'parameters' => [],
                    'returns' => 'List of all sending servers in json',
                    'example' => 'curl -X GET -H "accept:application/json" -G \
' . action("Api\SendingServerController@index") . '? \
-d api_token=' . \Auth::user()->api_token,
                ],
            ],
        ];

        // CUSTOMER
        $docs[] = [
            'view' => 'backend',
            'title' => trans('messages.customer'),
            'functions' => [
                [
                    'method' => 'POST',
                    'uri' => '/api/v1/customers',
                    'description' => 'Add new customer',
                    'parameters' => [
                    ],
                    'returns' => 'Creation messages in json',
                    'example' => 'curl -X POST -H "accept:application/json" -G \
' . action("Api\CustomerController@store") . '? \
-d api_token=' . \Auth::user()->api_token . '  \
-d email=user_name@gmail.com \
-d first_name=Luan \
-d last_name=Pham \
-d timezone=America/Godthab \
-d language_id=1 \
-d password=123456',
                ],

                [
                    'view' => 'backend',
                    'method' => 'GET',
                    'uri' => '/api/v1/customers/{uid}',
                    'description' => 'Get information about a specific customer',
                    'parameters' => [
                        ['name' => '$uid', 'description' => 'Customer\'s uid'],
                    ],
                    'returns' => 'Customer information in json (profile, contact, sunscriptions,... )',
                    'example' => 'curl -X GET -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", action("Api\CustomerController@update", "-ID-")) . '? \
-d api_token=' . \Auth::user()->api_token,
                ],

                [
                    'method' => 'PATCH',
                    'uri' => '/api/v1/customers',
                    'description' => 'Update customer',
                    'parameters' => [
                        ['name' => '$uid', 'description' => 'Customer\'s uid'],
                    ],
                    'returns' => 'Creation messages in json',
                    'example' => 'curl -X PATCH -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", action("Api\CustomerController@update", "-ID-")) . '? \
-d api_token=' . \Auth::user()->api_token . '  \
-d email=user_name@gmail.com \
-d first_name=Luan \
-d last_name=Pham \
-d timezone=America/Godthab \
-d language_id=1 \
-d password=123456',
                ],

                [
                    'method' => 'PATCH',
                    'uri' => '/api/v1/customers/{uid}/enable',
                    'description' => 'Enable customer',
                    'parameters' => [
                        ['name' => '$uid', 'description' => 'Customer\'s uid'],
                    ],
                    'returns' => 'Action messages in json',
                    'example' => 'curl -X PATCH -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", action("Api\CustomerController@enable", "-ID-")) . '? \
-d api_token=' . \Auth::user()->api_token,
                ],

                [
                    'method' => 'PATCH',
                    'uri' => '/api/v1/customers/{uid}/disable',
                    'description' => 'Disable customer',
                    'parameters' => [
                        ['name' => '$uid', 'description' => 'Customer\'s uid'],
                    ],
                    'returns' => 'Action messages in json',
                    'example' => 'curl -X PATCH -H "accept:application/json" -G \
' . str_replace("-ID-", "<redbold>{uid}</redbold>", action("Api\CustomerController@disable", "-ID-")) . '? \
-d api_token=' . \Auth::user()->api_token,
                ],
            ],
        ];

        // SUBSCRIPTION
        $docs[] = [
            'view' => 'backend',
            'title' => trans('messages.subscription'),
            'functions' => [
                [
                    'method' => 'POST',
                    'uri' => '/api/v1/subscriptions',
                    'description' => 'Subscribe customer to a plan',
                    'parameters' => [
                        ['name' => '$customer_uid', 'description' => 'Customer\'s uid'],
                        ['name' => '$plan_uid', 'description' => 'Plan\'s uid'],
                        ['name' => '$start_at', 'description' => 'Subscription\'s start date', 'optional' => true, 'default' => 'Next customer\'s subscription start date'],
                        ['name' => '$end_at', 'description' => 'Subscription\'s end date', 'optional' => true, 'default' => 'End date of the plan from start_at date'],
                    ],
                    'returns' => 'Action messages in json',
                    'example' => 'curl -X POST -H "accept:application/json" -G \
' . action("Api\SubscriptionController@store") . '? \
-d api_token=' . \Auth::user()->api_token . '  \
-d customer_uid={customer_uid} \
-d plan_uid={plan_uid}',
                ],
            ],
        ];

        // File
        $docs[] = [
            'view' => 'frontend',
            'title' => trans('messages.file'),
            'functions' => [
                [
                    'method' => 'POST',
                    'uri' => '/api/v1/file/upload',
                    'description' => 'Upload a file to customer\'s storage',
                    'parameters' => [
                        ['name' => '$file_url', 'description' => 'File url'],
                        ['name' => '$subdirectory', 'description' => 'Custom subdirectory', 'optional' => true, 'default' => 'user root directory']
                    ],
                    'returns' => 'Upload result message',
                    'example' => 'curl -X POST -H "accept:application/json" -G \
' . action("Api\FileController@upload") . '? \
-d api_token=' . \Auth::user()->api_token . '  \
-d file_url=http://demo.acellemail.com/images/logo_big.png \
-d subdirectory=\'path/to/file\'',
                ],
            ],
        ];

        return $docs;
    }
}
