                    <h4 class="mt-0 text-semibold">{{ trans('messages.' . request()->type) }}</h4>

                    <div class="row">
                        <div class="col-sm-6 col-md-4">
                            @include('helpers.form_control', [
                                'type' => 'text',
                                'class' => '',
                                'name' => 'name',
                                'value' => $server->name,
                                'help_class' => 'feedback_loop_handler',
                                'rules' => Acelle\Model\FeedbackLoopHandler::rules()
                            ])
                        </div>
                        <div class="col-sm-6 col-md-4">
                            @include('helpers.form_control', [
                                'type' => 'text',
                                'class' => '',
                                'name' => 'host',
                                'value' => $server->host,
                                'help_class' => 'feedback_loop_handler',
                                'rules' => Acelle\Model\FeedbackLoopHandler::rules()
                            ])
                        </div>
                        <div class="col-sm-6 col-md-4">
                            @include('helpers.form_control', [
                                'type' => 'text',
                                'class' => '',
                                'name' => 'port',
                                'value' => $server->port,
                                'help_class' => 'feedback_loop_handler',
                                'rules' => Acelle\Model\FeedbackLoopHandler::rules()
                            ])
                        </div>
                        <div class="col-sm-6 col-md-4">
                            @include('helpers.form_control', [
                                'type' => 'text',
                                'class' => '',
                                'name' => 'username',
                                'value' => $server->username,
                                'help_class' => 'feedback_loop_handler',
                                'rules' => Acelle\Model\FeedbackLoopHandler::rules()
                            ])
                        </div>
                        <div class="col-sm-6 col-md-4">
                            @include('helpers.form_control', [
                                'type' => 'text',
                                'class' => '',
                                'name' => 'password',
                                'value' => $server->password,
                                'help_class' => 'feedback_loop_handler',
                                'rules' => Acelle\Model\FeedbackLoopHandler::rules()
                            ])
                        </div>
                        <div class="col-sm-6 col-md-4">
                            @include('helpers.form_control', [
                                'type' => 'select',
                                'class' => '',
                                'name' => 'protocol',
                                'value' => $server->protocol,
                                'options' => Acelle\Model\FeedbackLoopHandler::protocolSelectOptions(),
                                'help_class' => 'feedback_loop_handler',
                                'rules' => Acelle\Model\FeedbackLoopHandler::rules()
                            ])
                        </div>
                    </div>
                    <hr>
                    <div class="text-left">
                        <button class="btn bg-teal"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
                    </div>
