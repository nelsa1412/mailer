<h4>{{ trans('messages.delete_list_confirm_warning') }}</h4>
<ul class="modern-listing">
    @foreach ($lists->get() as $list)
        <li>
            <i class="icon-cancel-circle2 text-danger"></i>
            <h4 class="text-danger">{{ $list->name }}</h4>
            <p>
                @if ($list->subscribers()->count())
                    <span class="text-bold text-danger">{{ $list->subscribers()->count() }}</span> {{ trans('messages.subscribers') }}<pp>,</pp>
                @endif
                @if ($list->segments()->count())
                    <span class="text-bold text-danger">{{ $list->segments()->count() }}</span> {{ trans('messages.segments') }}<pp>,</pp>
                @endif
                @if ($list->campaigns()->count())
                    <span class="text-bold text-danger">{{ $list->campaigns()->count() }}</span> {{ trans('messages.campaigns') }}<pp>,</pp>
                @endif
            </p>                        
        </li>
    @endforeach
</ul>