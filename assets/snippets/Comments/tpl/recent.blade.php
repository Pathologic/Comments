@if ($data['createdby'] == '-1'])
    @php $author = $DocLister->translate('admin') @endphp
@elseif ($data['createdby'] == '0')
    @php $author = $DocLister->translate('guest').' '.$data['name'] @endphp
@else
    @php
        $author = empty($data['user.fullname.createdby']) ?
            $DocLister->translate('deleted_user') :
            $data['user.fullname.createdby'];
    @endphp
@endif
<div class="recent-comments">
@if (count($data))
    @foreach($data as $item)
        <div class="recent-comment">
            <div class="recent-head">
                <span class="username">{{ $author }}</span> <span class="createdon">
                    {{ empty($item['createdon']) ? date($DocLister->translate('dateFormat')) : $item['createdon'] }}
                </span>
            </div>
            <div class="recent-body">
                <a href="{{ $modx->makeUrl($item['thread']) . '#comment-' . $item['id']}}">{{ $item['summary'] }}</a>
            </div>
            <div class="recent-footer">
                {{ $item['pagetitle'] }} ({{ $item['comments_count'] }})
            </div>
        </div>
    @endforeach
@else
    {{ $DocLister->translate('nothing_commented') }}
@endif
</div>

