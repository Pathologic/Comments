<div class="recent-comments">
{% for index, item in data%}
    {% if item.createdby == '-1' %}
        {% set author = DocLister.translate('admin') %}
    {% elseif item.createdby == '0' %}
        {% set author = DocLister.translate('guest') ~ ' ' ~  item.name %}
    {% else %}
        {% set author = item['user.fullname.createdby'] | default(item['user.username.createdby']) | default(DocLister.translate('deleted_user')) %}
    {% endif %}
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

