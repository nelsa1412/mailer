                        @if ($items->count() > 0)
							<table class="table table-box pml-table table-log"
                                current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
                            >
								<tr>
									<th>{{ trans('messages.email') }}</th>
									<th>{{ trans('messages.created_at') }}</th>
									<th class="text-right">{{ trans('messages.action') }}</th>
								</tr>
								@foreach ($items as $key => $item)
									<tr>
										<td>
											<span class="no-margin kq_search">{{ $item->email }}</span>
											<span class="text-muted second-line-mobile">{{ trans('messages.email') }}</span>
										</td>										
										<td>
											<span class="no-margin kq_search">{{ Tool::formatDateTime($item->created_at) }}</span>
											<span class="text-muted second-line-mobile">{{ trans('messages.created_at') }}</span>
										</td>
										<td class="text-right">
											<a link-confirm="{{ trans('messages.remove_blacklist_confirm') }}" href="{{ action('Admin\BlacklistController@delete', ["emails" => $item->email]) }}" type="button" class="btn bg-grey-600 btn-icon">
												<i class="icon icon-minus2"></i> {{ trans('messages.remove_from_blacklist') }}</i>
											</a>											
										</td>
									</tr>
								@endforeach
							</table>
                            @include('elements/_per_page_select', ["items" => $items])
							{{ $items->links() }}
						@elseif (!empty(request()->keyword) || !empty(request()->filters["campaign_uid"]))
							<div class="empty-list">
								<i class="glyphicon glyphicon-minus-sign"></i>
								<span class="line-1">
									{{ trans('messages.no_search_result') }}
								</span>
							</div>
						@else					
							<div class="empty-list">
								<i class="glyphicon glyphicon-minus-sign"></i>
								<span class="line-1">
									{{ trans('messages.blacklist_empty_line_1') }}
								</span>
							</div>
						@endif