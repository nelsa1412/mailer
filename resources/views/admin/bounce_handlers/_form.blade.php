					<div class="row">
						<div class="col-sm-6 col-md-4">							
							@include('helpers.form_control', [
								'type' => 'text',
								'class' => '',
								'name' => 'name',
								'value' => $server->name,
								'help_class' => 'bounce_handler',
								'rules' => Acelle\Model\BounceHandler::rules()
							])
						</div>
						<div class="col-sm-6 col-md-4">
							@include('helpers.form_control', [
								'type' => 'text',
								'class' => '',
								'name' => 'host',
								'value' => $server->host,
								'help_class' => 'bounce_handler',
								'rules' => Acelle\Model\BounceHandler::rules()
							])
						</div>
						<div class="col-sm-6 col-md-4">
							@include('helpers.form_control', [
								'type' => 'text',
								'class' => '',
								'name' => 'port',
								'value' => $server->port,
								'help_class' => 'bounce_handler',
								'rules' => Acelle\Model\BounceHandler::rules()
							])
						</div>
					</div>
					<div class="row">
						<div class="col-sm-6 col-md-4">
							@include('helpers.form_control', [
								'type' => 'text',
								'class' => '',
								'name' => 'email',
								'value' => $server->email,
								'help_class' => 'bounce_handler',
								'rules' => Acelle\Model\BounceHandler::rules()
							])
						</div>
						<div class="col-sm-6 col-md-4">
							@include('helpers.form_control', [
								'type' => 'text',
								'class' => '',
								'name' => 'username',
								'value' => $server->username,
								'help_class' => 'bounce_handler',
								'rules' => Acelle\Model\BounceHandler::rules()
							])
						</div>
						<div class="col-sm-6 col-md-4">
							@include('helpers.form_control', [
								'type' => 'text',
								'class' => '',
								'name' => 'password',
								'value' => $server->password,
								'help_class' => 'bounce_handler',
								'rules' => Acelle\Model\BounceHandler::rules()
							])
						</div>
					</div>
					<div class="row">
						<div class="col-sm-6 col-md-4">
							@include('helpers.form_control', [
								'type' => 'select',
								'class' => '',
								'name' => 'protocol',
								'value' => $server->protocol,
								'options' => Acelle\Model\BounceHandler::protocolSelectOptions(),
								'help_class' => 'bounce_handler',
								'rules' => Acelle\Model\BounceHandler::rules()
							])
						</div>
						<div class="col-sm-6 col-md-4">
							@include('helpers.form_control', [
								'type' => 'select',
								'class' => '',
								'name' => 'encryption',
								'value' => $server->encryption,
								'options' => Acelle\Model\BounceHandler::encryptionSelectOptions(),
								'help_class' => 'bounce_handler',
								'rules' => Acelle\Model\BounceHandler::rules()
							])
						</div>
					</div>
					<hr>
					<div class="text-left">
						<button class="btn bg-teal"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
					</div>