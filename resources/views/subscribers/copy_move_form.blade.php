<!-- Basic modal -->
<div id="copy-move-subscribers-form" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
            <form action="{{ action("SubscriberController@" . request()->action) }}" method="POST" class="form-validate-jquery">
                {{ csrf_field() }}
                
				<div class="modal-header bg-teal">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h2 class="modal-title">
                        {{ trans('messages.' . request()->action . '_subscriber') }}
                    </h2>
				</div>

				<div class="modal-body">
                    <h4>{{ trans('messages.subscribers_' . request()->action . '_message', ['number' => $subscribers->count()]) }}</h4>
                
                    <input type="hidden" name="action" value="{{ request()->action }}" />
                    <input type="hidden" name="uids" value="{{ request()->uids }}" />
				
					@include('helpers.form_control', [
						'type' => 'select',
						'name' => 'list_uid',
						'class' => '',
                        'required' => true,
						'label' => trans('messages.select_the_target_list'),
						'value' => '',
                        'include_blank' => trans('messages.choose'),
						'options' => Acelle\Model\MailList::getSelectOptions(\Auth::user()->customer, ['other_list_of' => $list->id]),
						'rules' => []
					])
					
					@include('helpers.form_control', [
						'type' => 'radio',
						'name' => 'type',
						'class' => '',
						'label' => trans('messages.action_when_email_exist'),
						'value' => 'update',
						'options' => Acelle\Model\Subscriber::copyMoveExistSelectOptions(),
						'rules' => []
					])
					
				</div>

				<div class="modal-footer">
					<button type="submit" class="btn btn-primary bg-teal">{{ trans('messages.submit') }}</button>
					<button type="button" class="btn btn-white" data-dismiss="modal">{{ trans('messages.cancel') }}</button>
				</div>
		</div>
	</div>
</div>
<!-- /basic modal -->