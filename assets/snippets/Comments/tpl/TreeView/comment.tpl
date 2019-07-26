<div class="comment {{ data.classes | join(' ') }}" data-id="{{ data.id }}" data-level="{{ data.level }}" id="comment-{{ data.id }}" {% if data['edit-ttl'] %}data-edit-ttl="{{ data['edit-ttl'] }}"{% endif %}>
    <div class="comment-wrap">
    {% if data.deleted and not DocLister.isModerator() %}
        <div class="comment-body">
            Комментарий удален
        </div>
    {% elseif data.published == 0 and not DocLister.isModerator() %}
        <div class="comment-body">
            Комментарий скрыт
        </div>
    {% else %}
        <div class="comment-head">
            <span class="username">{{ data.createdby ? data['user.fullname.createdby'] : 'Гость ' ~  data.name }}{% if DocLister.isModerator() %} ({{ data.email|default(data['user.email.createdby']) }}){% endif %}</span> <span class="createdon">{{ data.createdon | date('d.m.Y в H:i:s') }}</span> <span class="comment-link"><a href="{{ makeUrl(data.thread) ~ '#comment-' ~ data.id}}">#</a> {% if data.idNearestAncestor %}<a href="{{ makeUrl(data.thread) ~ '#comment-' ~ data.idNearestAncestor}}">↑</a>{% endif %}</span>
        </div>
        <div class="comment-body">
            {{ data.content | raw }}
            {% if data.updatedon != '0000-00-00 00:00:00' %}
                <div class="small">Редактировалось пользователем {{ data['user.fullname.updatedby'] }} {{ data.updatedon }}</div>
            {% endif %}
        </div>
        {% if DocLister.isModerator() %}
        <div class="comment-moderation">
            {% if DocLister.hasPermission('comments_publish') %}<a href="#" class="comment-publish btn btn-xs btn-primary" data-id="{{ data.id }}">Опубликовать</a>{% endif %}
            {% if DocLister.hasPermission('comments_unpublish') %}<a href="#" class="comment-unpublish btn btn-xs btn-default" data-id="{{ data.id }}">Скрыть</a>{% endif %}
            {% if DocLister.hasPermission('comments_delete') %}<a href="#" class="comment-delete btn btn-xs btn-warning" data-id="{{ data.id }}">Удалить</a>{% endif %}
            {% if DocLister.hasPermission('comments_undelete') %}<a href="#" class="comment-undelete btn btn-xs btn-info" data-id="{{ data.id }}">Восстановить</a>{% endif %}
            {% if DocLister.hasPermission('comments_remove') %}<a href="#" class="comment-remove btn btn-xs btn-danger" data-id="{{ data.id }}">Уничтожить</a>{% endif %}
            {% if DocLister.hasPermission('comments_edit') %}<a href="#" class="comment-edit btn btn-xs btn-success" data-id="{{ data.id }}">Редактировать</a>{% endif %}
        </div>
        {% endif %}
        {% if DocLister.isNotGuest() %}
        <div class="comment-footer">
            <a href="#" class="comment-reply btn btn-primary" data-id="{{ data.id }}">Ответить</a>
            {% if data.editable %}
                <a href="#" class="comment-update btn btn-success" data-id="{{ data.id }}">Изменить</a>
            {% endif %}
        </div>
        {% endif %}
    {% endif %}
    </div>
</div>
