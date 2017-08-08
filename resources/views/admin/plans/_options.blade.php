<h2><i class="icon-gear"></i> {{ trans('messages.options') }}</h2>
<div class="tabbable">
    <ul class="nav nav-tabs nav-tabs-top">
        <li class="active text-semibold"><a href="#top-tab1" data-toggle="tab">
            <i class="icon-database"></i> {{ trans('messages.resources_quota') }}</a>
        </li>
        <li class="text-semibold"><a href="#top-tab2" data-toggle="tab">
            <i class="icon-upload"></i> {{ trans('messages.file_upload') }}</a>
        </li>
        <li class="text-semibold"><a href="#top-tab3" data-toggle="tab">
            <i class="icon-stats-bars4"></i> {{ trans('messages.sending_quota') }}</a>
        </li>
        <li class="text-semibold"><a href="#top-tab4" data-toggle="tab">
            <i class="icon-server"></i> {{ trans('messages.sending_servers') }}</a>
        </li>
        <li class="text-semibold"><a href="#top-tab5" data-toggle="tab">
            <i class="icon-earth"></i> {{ trans('messages.sending_domains') }}</a>
        </li>
        <li class="text-semibold"><a href="#top-tab6" data-toggle="tab">
            <i class="icon-database-check"></i> {{ trans('messages.email_verification_servers') }}</a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="top-tab1">
            <!--<h3 class="text-teal-800">{{ trans('messages.list_or_subscriber_or_segment_or_campaign') }}</h3>-->
            <div class="row">
                <div class="boxing col-md-3">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[email_max]',
                        'value' => $options['email_max'],
                        'label' => trans('messages.max_emails'),
                        'help_class' => 'plan',
                        'options' => ['true', 'false'],
                        'rules' => $plan->rules()
                    ])
                    <div class="checkbox inline unlimited-check text-semibold">
                        <label>
                            <input{{ $options['email_max']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                            {{ trans('messages.unlimited') }}
                        </label>
                    </div>
                </div>
                <div class="boxing col-md-3">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[list_max]',
                        'value' => $options['list_max'],
                        'label' => trans('messages.max_lists'),
                        'help_class' => 'plan',
                        'options' => ['true', 'false'],
                        'rules' => $plan->rules()
                    ])
                    <div class="checkbox inline unlimited-check text-semibold">
                        <label>
                            <input{{ $options['list_max']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                            {{ trans('messages.unlimited') }}
                        </label>
                    </div>
                </div>
                <div class="boxing col-md-3">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[subscriber_max]',
                        'value' => $options['subscriber_max'],
                        'label' => trans('messages.max_subscribers'),
                        'help_class' => 'plan',
                        'options' => ['true', 'false'],
                        'rules' => $plan->rules()
                    ])
                    <div class="checkbox inline unlimited-check text-semibold">
                        <label>
                            <input{{ $options['subscriber_max']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                            {{ trans('messages.unlimited') }}
                        </label>
                    </div>
                </div>
                <div class="boxing col-md-3">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[subscriber_per_list_max]',
                        'value' => $options['subscriber_per_list_max'],
                        'label' => trans('messages.max_subscribers_per_list'),
                        'help_class' => 'plan',
                        'rules' => $plan->rules()
                    ])
                    <div class="checkbox inline unlimited-check text-semibold">
                        <label>
                            <input{{ $options['subscriber_per_list_max']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                            {{ trans('messages.unlimited') }}
                        </label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="boxing col-md-3">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[segment_per_list_max]',
                        'value' => $options['segment_per_list_max'],
                        'label' => trans('messages.segment_per_list_max'),
                        'help_class' => 'plan',
                        'rules' => $plan->rules()
                    ])
                    <div class="checkbox inline unlimited-check text-semibold">
                        <label>
                            <input{{ $options['segment_per_list_max']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                            {{ trans('messages.unlimited') }}
                        </label>
                    </div>
                </div>
                <div class="boxing col-md-3">
                    @include('helpers.form_control', ['type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[campaign_max]',
                        'value' => $options['campaign_max'],
                        'label' => trans('messages.max_campaigns'),
                        'help_class' => 'plan',
                        'rules' => $plan->rules()
                    ])
                    <div class="checkbox inline unlimited-check text-semibold">
                        <label>
                            <input{{ $options['campaign_max']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                            {{ trans('messages.unlimited') }}
                        </label>
                    </div>
                </div>
                <div class="boxing col-md-3">
                    @include('helpers.form_control', ['type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[automation_max]',
                        'value' => $options['automation_max'],
                        'label' => trans('messages.max_automations'),
                        'help_class' => 'plan',
                        'rules' => $plan->rules()
                    ])
                    <div class="checkbox inline unlimited-check text-semibold">
                        <label>
                            <input{{ $options['automation_max']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                            {{ trans('messages.unlimited') }}
                        </label>
                    </div>
                </div>
                <div class="boxing col-md-3">
                    <label class="text-semibold">{{ trans('messages.unsubscribe_url_required') }} <span class="text-danger">*</span></label>
                    <br />
                    <span class="notoping">
                        @include('helpers.form_control', ['type' => 'checkbox',
                            'class' => '',
                            'name' => 'options[unsubscribe_url_required]',
                            'value' => $options['unsubscribe_url_required'],
                            'label' => '',
                            'options' => ['no','yes'],
                            'help_class' => 'plan',
                            'rules' => $plan->rules()
                        ])
                    </span>
                </div>
            </div>
            <div class="row">
                <div class="boxing col-md-3">
                    <label class="text-semibold">{{ trans('messages.access_when_offline') }} <span class="text-danger">*</span></label>
                    <br />
                    <span class="notoping">
                        @include('helpers.form_control', ['type' => 'checkbox',
                            'class' => '',
                            'name' => 'options[access_when_offline]',
                            'value' => $options['access_when_offline'],
                            'label' => '',
                            'options' => ['no','yes'],
                            'help_class' => 'plan',
                            'rules' => $plan->rules()
                        ])
                    </span>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="mt-0 mb-5 text-semibold">{{ trans('messages.max_number_of_processes') }}</label>
                        @include('helpers.form_control', ['type' => 'select',
                            'name' => 'options[max_process]',
                            'value' => $options['max_process'],
                            'label' => '',
                            'options' => \Acelle\Model\Plan::multiProcessSelectOptions(),
                            'help_class' => 'plan',
                            'rules' => $plan->rules()
                        ])
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="text-semibold">{{ trans('messages.can_import_list') }} <span class="text-danger">*</span></label>
                    <br />
                    <span class="notoping">
                        @include('helpers.form_control', ['type' => 'checkbox',
                            'class' => '',
                            'name' => 'options[list_import]',
                            'value' => $options['list_import'],
                            'label' => '',
                            'options' => ['no','yes'],
                            'help_class' => 'plan',
                            'rules' => $plan->rules()
                        ])
                    </span>
                </div>
                <div class="col-md-3">
                    <label class="text-semibold">{{ trans('messages.can_export_list') }} <span class="text-danger">*</span></label>
                    <br />
                    <span class="notoping">
                        @include('helpers.form_control', ['type' => 'checkbox',
                            'class' => '',
                            'name' => 'options[list_export]',
                            'value' => $options['list_export'],
                            'label' => '',
                            'options' => ['no','yes'],
                            'help_class' => 'plan',
                            'rules' => $plan->rules()
                        ])
                    </span>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="top-tab2">
            <!--<h3 class="text-teal-800">{{ trans('messages.file_upload') }}</h3>-->
            <div class="row">
                <div class="boxing col-md-3">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[max_size_upload_total]',
                        'value' => $options['max_size_upload_total'],
                        'label' => trans('messages.max_size_upload_total'),
                        'help_class' => 'plan',
                        'rules' => $plan->rules()
                    ])
                </div>
                <div class="boxing col-md-3">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[max_file_size_upload]',
                        'value' => $options['max_file_size_upload'],
                        'label' => trans('messages.max_file_size_upload'),
                        'help_class' => 'plan',
                        'rules' => $plan->rules()
                    ])
                </div>
            </div>
        </div>
        <div class="tab-pane" id="top-tab3">
            <!--<h3 class="text-teal-800">{{ trans('messages.sending_quota') }}</h3>-->
            <div class="row boxing">
                <div class="col-md-12">
                    <p>{!! trans('messages.options.wording') !!}</p>
                </div>
                <div class="col-md-4">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[sending_quota]',
                        'value' => $options['sending_quota'],
                        'label' => trans('messages.sending_quota'),
                        'help_class' => 'plan',
                        'rules' => $plan->rules()
                    ])
                    <div class="checkbox inline unlimited-check text-semibold">
                        <label>
                            <input{{ $options['sending_quota']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                            {{ trans('messages.unlimited') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => 'numeric',
                        'name' => 'options[sending_quota_time]',
                        'value' => $options['sending_quota_time'],
                        'label' => trans('messages.quota_time'),
                        'help_class' => 'plan',
                        'rules' => $plan->rules()
                    ])
                </div>
                <div class="col-md-4">
                    @include('helpers.form_control', ['type' => 'select',
                        'name' => 'options[sending_quota_time_unit]',
                        'value' => $options['sending_quota_time_unit'],
                        'label' => trans('messages.quota_time_unit'),
                        'options' => Acelle\Model\Plan::quotaTimeUnitOptions(),
                        'include_blank' => trans('messages.choose'),
                        'help_class' => 'plan',
                        'rules' => $plan->rules()
                    ])
                </div>
            </div>
        </div>
        <div class="tab-pane" id="top-tab4">
            <h4 class="text-teal-800 text-semibold">{{ trans('messages.sending_servers_settings') }}</h4>

            <div class="row">
                <div class="col-md-5">
                    <span class="text-semibold">{{ trans('messages.allow_customer_create_sending_servers') }}</span> &nbsp;&nbsp;&nbsp;
                    <span class="notoping">
                        @include('helpers.form_control', ['type' => 'checkbox',
                            'class' => '',
                            'name' => 'options[create_sending_servers]',
                            'value' => $options['create_sending_servers'],
                            'label' => '',
                            'options' => ['no','yes'],
                            'help_class' => 'plan',
                            'rules' => $plan->rules()
                        ])
                    </span>
                </div>
            </div>
            <hr>

            <div class="sending-servers-yes">
                <div class="row">
                    <div class="col-md-4">
                        <div class="boxing">
                            @include('helpers.form_control', [
                                'type' => 'text',
                                'class' => 'numeric',
                                'name' => 'options[sending_servers_max]',
                                'value' => $options['sending_servers_max'],
                                'label' => trans('messages.max_sending_servers'),
                                'help_class' => 'plan',
                                'options' => ['true', 'false'],
                                'rules' => $plan->rules()
                            ])
                            <div class="checkbox inline unlimited-check text-semibold">
                                <label>
                                    <input{{ $options['sending_servers_max']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                                    {{ trans('messages.unlimited') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sending-servers-no">
                <h5 class="text-semibold">{{ trans('messages.setting_up_sending_servers_for_plan') }}</h5>
                <div class="row">
                    <div class="col-md-3">
                        {{ trans('messages.use_all_sending_servers') }}&nbsp;&nbsp;&nbsp;
                        <span class="notoping">
                            @include('helpers.form_control', ['type' => 'checkbox',
                                'class' => '',
                                'name' => 'options[all_sending_servers]',
                                'value' => $options['all_sending_servers'],
                                'label' => '',
                                'options' => ['no','yes'],
                                'help_class' => 'plan',
                                'rules' => $plan->rules()
                            ])
                        </span>

                    </div>
                </div>
                @if(!Acelle\Model\SendingServer::getAllAdminActive()->count())
                    <div class="empty-list">
                        <i class="icon-server"></i>
                        <span class="line-1">
                            {{ trans('messages.sending_server_no_active') }}
                        </span>
                    </div>
                @endif
                <br />
                <div class="row sending-servers">
                    @foreach (Acelle\Model\SendingServer::getAllAdminActive()->orderBy("name")->get() as $server)
                        <div class="col-md-6">
                            <h5 class="mt-0 mb-5 text-semibold text-teal-600">{{ $server->name }}</h5>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="mt-0 mb-5 text-semibold">{{ trans('messages.choose') }}</label>
                                        @include('helpers.form_control', [
                                            'type' => 'checkbox',
                                            'name' => 'sending_servers[' . $server->uid . '][check]',
                                            'value' => $plan->plansSendingServers->contains('sending_server_id', $server->id),
                                            'label' => '',
                                            'options' => [false, true],
                                            'help_class' => 'plan',
                                            'rules' => $plan->rules()
                                        ])
                                    </div>
                                    <br><br>
                                </div>
                                <div class="col-md-9">
                                    @include('helpers.form_control', [
                                        'type' => 'text',
                                        'class' => 'numeric',
                                        'name' => 'sending_servers[' . $server->uid . '][fitness]',
                                        'label' => trans('messages.fitness'),
                                        'value' => (is_object($plan->plansSendingServers()->where('sending_server_id', $server->id)->first()) ? $plan->plansSendingServers()->where('sending_server_id', $server->id)->first()->fitness : "100"),
                                        'help_class' => 'plan',
                                        'rules' => $plan->rules()
                                    ])
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="tab-pane" id="top-tab5">
            <h4 class="text-teal-800 text-semibold">{{ trans('messages.sending_domains_settings') }}</h4>

            <div class="row">
                <div class="col-md-5">
                    <span class="text-semibold">{{ trans('messages.allow_customer_create_sending_domains') }}</span> &nbsp;&nbsp;&nbsp;
                    <span class="notoping">
                        @include('helpers.form_control', ['type' => 'checkbox',
                            'class' => '',
                            'name' => 'options[create_sending_domains]',
                            'value' => $options['create_sending_domains'],
                            'label' => '',
                            'options' => ['no','yes'],
                            'help_class' => 'plan',
                            'rules' => $plan->rules()
                        ])
                    </span>
                </div>
            </div>
            <div class="sending-domains-yes">
            <hr />
                <div class="row">
                    <div class="col-md-4">
                        <div class="boxing">
                            @include('helpers.form_control', [
                                'type' => 'text',
                                'class' => 'numeric',
                                'name' => 'options[sending_domains_max]',
                                'value' => $options['sending_domains_max'],
                                'label' => trans('messages.max_sending_domains'),
                                'help_class' => 'plan',
                                'options' => ['true', 'false'],
                                'rules' => $plan->rules()
                            ])
                            <div class="checkbox inline unlimited-check text-semibold">
                                <label>
                                    <input{{ $options['sending_domains_max']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                                    {{ trans('messages.unlimited') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane" id="top-tab6">
            <h4 class="text-teal-800 text-semibold">{{ trans('messages.email_verification_servers_settings') }}</h4>

            <div class="row">
                <div class="col-md-5">
                    <span class="text-semibold">{{ trans('messages.allow_customer_create_email_verification_servers') }}</span> &nbsp;&nbsp;&nbsp;
                    <span class="notoping">
                        @include('helpers.form_control', ['type' => 'checkbox',
                            'class' => '',
                            'name' => 'options[create_email_verification_servers]',
                            'value' => $options['create_email_verification_servers'],
                            'label' => '',
                            'options' => ['no','yes'],
                            'help_class' => 'plan',
                            'rules' => $plan->rules()
                        ])
                    </span>
                </div>
            </div>
            <hr>

            <div class="email-verification-servers-yes">
                <div class="row">
                    <div class="col-md-4">
                        <div class="boxing">
                            @include('helpers.form_control', [
                                'type' => 'text',
                                'class' => 'numeric',
                                'name' => 'options[email_verification_servers_max]',
                                'value' => $options['email_verification_servers_max'],
                                'label' => trans('messages.max_email_verification_servers'),
                                'help_class' => 'plan',
                                'options' => ['true', 'false'],
                                'rules' => $plan->rules()
                            ])
                            <div class="checkbox inline unlimited-check text-semibold">
                                <label>
                                    <input{{ $options['email_verification_servers_max']  == -1 ? " checked=checked" : "" }} type="checkbox" class="styled">
                                    {{ trans('messages.unlimited') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="email-verification-servers-no">
                <h5 class="text-semibold">{{ trans('messages.setting_up_email_verification_servers_for_plan') }}</h5>
                <div class="row">
                    <div class="col-md-3">
                        {{ trans('messages.use_all_email_verification_servers') }}&nbsp;&nbsp;&nbsp;
                        <span class="notoping">
                            @include('helpers.form_control', ['type' => 'checkbox',
                                'class' => '',
                                'name' => 'options[all_email_verification_servers]',
                                'value' => $options['all_email_verification_servers'],
                                'label' => '',
                                'options' => ['no','yes'],
                                'help_class' => 'plan',
                                'rules' => $plan->rules()
                            ])
                        </span>

                    </div>
                </div>
                @if(!Acelle\Model\EmailVerificationServer::getAllAdminActive()->count())
                    <div class="empty-list">
                        <i class="icon-database-check"></i>
                        <span class="line-1">
                            {{ trans('messages.email_verification_server_no_active') }}
                        </span>
                    </div>
                @endif
                <br />
                <div class="row email-verification-servers">
                    @foreach (Acelle\Model\EmailVerificationServer::getAllAdminActive()->orderBy("name")->get() as $server)
                        <div class="col-md-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <h5 class="mt-0 mb-5 text-semibold text-teal-600">{{ $server->name }}</h5>
                                        @include('helpers.form_control', [
                                            'type' => 'checkbox',
                                            'name' => 'email_verification_servers[' . $server->uid . '][check]',
                                            'value' => $plan->plansEmailVerificationServers->contains('server_id', $server->id),
                                            'label' => '',
                                            'options' => [false, true],
                                            'help_class' => 'plan',
                                            'rules' => $plan->rules()
                                        ])
                                    </div>
                                    <br><br>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
	$(document).ready(function() {
		// all sending servers checking
		$(document).on("change", "input[name='options[all_sending_servers]']", function(e) {
			if($("input[name='options[all_sending_servers]']:checked").length) {
				$(".sending-servers").find("input[type=checkbox]").each(function() {
					if($(this).is(":checked")) {
						$(this).parents(".form-group").find(".switchery").eq(1).click();
					}
				});
				$(".sending-servers").hide();
			} else {
				$(".sending-servers").show();
			}
		});
		$("input[name='options[all_sending_servers]']").trigger("change");


		// Sending servers checking setting
		$(document).on("change", "input[name='options[create_sending_servers]']", function(e) {
			if($('input[name="options[create_sending_servers]"]:checked').val() == 'yes') {
				$(".sending-servers-yes").show();
				$(".sending-servers-no").hide();
			} else {
				$(".sending-servers-no").show();
				$(".sending-servers-yes").hide();
			}
		});
		$('input[name="options[create_sending_servers]"]').trigger("change");

        // Sending domains checking setting
		$(document).on("change", "input[name='options[create_sending_domains]']", function(e) {
			if($('input[name="options[create_sending_domains]"]:checked').val() == 'yes') {
				$(".sending-domains-yes").show();
				$(".sending-domains-no").hide();
			} else {
				$(".sending-domains-no").show();
				$(".sending-domains-yes").hide();
			}
		});
		$('input[name="options[create_sending_domains]"]').trigger("change");

        // all email verification servers checking
		$(document).on("change", "input[name='options[all_email_verification_servers]']", function(e) {
			if($("input[name='options[all_email_verification_servers]']:checked").length) {
				$(".email-verification-servers").find("input[type=checkbox]").each(function() {
					if($(this).is(":checked")) {
						$(this).parents(".form-group").find(".switchery").eq(1).click();
					}
				});
				$(".email-verification-servers").hide();
			} else {
				$(".email-verification-servers").show();
			}
		});
		$("input[name='options[all_email_verification_servers]']").trigger("change");


		// Email verification servers checking setting
		$(document).on("change", "input[name='options[create_email_verification_servers]']", function(e) {
			if($('input[name="options[create_email_verification_servers]"]:checked').val() == 'yes') {
				$(".email-verification-servers-yes").show();
				$(".email-verification-servers-no").hide();
			} else {
				$(".email-verification-servers-no").show();
				$(".email-verification-servers-yes").hide();
			}
		});
		$('input[name="options[create_email_verification_servers]"]').trigger("change");
	});
</script>
