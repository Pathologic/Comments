<div class="recent-comments">
{% for index, item in data%}
    <div class="recent-comment">
        <div class="recent-head">
            <span class="username">{{ item.createdby ? item['user.fullname.createdby'] : 'Гость ' ~ item.name }}</span> <span class="createdon">{{ item.createdon | date('d.m.Y в H:i:s') }}</span>
        </div>
        <div class="recent-body">
            <a href="{{ makeUrl(item.thread) ~ '#comment-' ~ item.id}}">{{ item.summary }}</a>
        </div>
        <div class="recent-footer">
            {{ item.pagetitle }} ({{ item.comments_count }})
        </div>
    </div>
{% else %}
    Пока нет комментариев.
{% endfor %}
</div>

