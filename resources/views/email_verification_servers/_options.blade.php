@if ($server->type)
    <div class="row">
        <div class="col-md-4">
            @include('helpers.form_control', [
                'type' => 'text',
                'class' => '',
                'name' => 'options[api_key]',
                'value' => isset($options['api_key']) ? $options['api_key'] : '',
                'label' => trans('messages.verification_api_key'),
                'help_class' => 'email_verification_server',
                'rules' => $server->rules()
            ])
        </div>
        <div class="col-md-4">
            @if (in_array('api_secret_key', $server->getConfig()["fields"]))
                @include('helpers.form_control', [
                    'type' => 'text',
                    'class' => '',
                    'name' => 'options[api_secret_key]',
                    'value' => isset($options['api_secret_key']) ? $options['api_secret_key'] : '',
                    'label' => trans('messages.verification_api_secret_key'),
                    'help_class' => 'email_verification_server',
                    'rules' => $server->rules()
                ])
            @endif
        </div>
    </div>
@endif
