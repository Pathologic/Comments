{% if data.createdby == '-1' %}
    {% set author = DocLister.translate('admin') %}
{% elseif data.createdby == '0' %}
    {% set author = DocLister.translate('guest') ~ ' ' ~  data.name %}
{% else %}
    {% set author = data['user.fullname.createdby'] | default(DocLister.translate('deleted_user')) %}
{% endif %}
<div class="recent-comments">
{% for index, item in data%}
    <div class="recent-comment">
        <div class="recent-head">
            <span class="username">{{ author }}</span> <span class="createdon">{{ item.createdon | date(DocLister.translate('dateFormat')) }}</span>
        </div>
        <div class="recent-body">
            <a href="{{ makeUrl(item.thread) ~ '#comment-' ~ item.id}}">{{ item.summary }}</a>
        </div>
        <div class="recent-footer">
            {{ item.pagetitle }} ({{ item.comments_count }})
        </div>
    </div>
{% else %}
    {{ DocLister.translate('nothing_commented') }}
{% endfor %}
</div>

