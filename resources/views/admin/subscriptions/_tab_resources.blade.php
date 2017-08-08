<!--<h3 class="text-teal-800">{{ trans('messages.list_or_subscriber_or_segment_or_campaign') }}</h3>-->
<div class="row">
    <div class="boxing col-md-3">
        @include('helpers.form_control', [
            'type' => 'text',
            'class' => 'numeric',
            'name' => 'options[email_max]',
            'value' => $options['email_max'],
            'label' => trans('messages.max_emails'),
            'help_class' => 'subscription',
            'options' => ['true', 'false'],
            'rules' => $subscription->rules()
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
            'help_class' => 'subscription',
            'options' => ['true', 'false'],
            'rules' => $subscription->rules()
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
            'help_class' => 'subscription',
            'options' => ['true', 'false'],
            'rules' => $subscription->rules()
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
            'help_class' => 'subscription',
            'rules' => $subscription->rules()
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
            'help_class' => 'subscription',
            'rules' => $subscription->rules()
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
            'help_class' => 'subscription',
            'rules' => $subscription->rules()
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
            'help_class' => 'subscription',
            'rules' => $subscription->rules()
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
                'help_class' => 'subscription',
                'rules' => $subscription->rules()
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
                'help_class' => 'subscription',
                'rules' => $subscription->rules()
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
                'help_class' => 'subscription',
                'rules' => $subscription->rules()
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
                'help_class' => 'subscription',
                'rules' => $subscription->rules()
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
                'help_class' => 'subscription',
                'rules' => $subscription->rules()
            ])
        </span>
    </div>
</div>
