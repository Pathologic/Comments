<div class="comment {{ data.classes | join(' ') }}" data-id="{{ data.id }}" data-level="{{ data.level }}" id="comment-{{ data.id }}" {% if data['edit-ttl'] %}data-edit-ttl="{{ data['edit-ttl'] }}"{% endif %}>
    <div class="comment-wrap">
    {% if data.deleted and not DocLister.isModerator() %}
        <div class="comment-body">
            {{ DocLister.translate('deleted_comment') }}
        </div>
    {% elseif data.published == 0 and not DocLister.isModerator() %}
        <div class="comment-body">
            {{ DocLister.translate('unpublished_comment') }}
        </div>
    {% else %}
        <div class="comment-head">
            <span class="username">{{ data.createdby ? data['user.fullname.createdby'] : DocLister.translate('guest') ~ ' ' ~  data.name }}{% if DocLister.isModerator() %} ({{ data.email|default(data['user.email.createdby']) }}){% endif %}</span> <span class="createdon">{{ data.createdon | date(DocLister.translate('dateFormat')) }}</span> <span class="comment-link"><a href="{{ makeUrl(data.thread) ~ '#comment-' ~ data.id}}">#</a> {% if data.idNearestAncestor %}<a href="{{ makeUrl(data.thread) ~ '#comment-' ~ data.idNearestAncestor}}">↑</a>{% endif %}</span>
        </div>
        <div class="comment-body">
            {{ data.content | raw }}
            {% if data.updatedon != '0000-00-00 00:00:00' %}
                <div class="small">{{ DocLister.translate('edited_by') }} {{ data['user.fullname.updatedby'] }} {{ data.updatedon | date(DocLister.translate('dateFormat')) }}</div>
            {% endif %}
        </div>
        {% if DocLister.isModerator() %}
        <div class="comment-moderation">
            {% if DocLister.hasPermission('comments_publish') %}<a href="#" class="comment-publish btn btn-xs btn-primary" data-id="{{ data.id }}">{{ DocLister.translate('publish') }}</a>{% endif %}
            {% if DocLister.hasPermission('comments_unpublish') %}<a href="#" class="comment-unpublish btn btn-xs btn-default" data-id="{{ data.id }}">{{ DocLister.translate('unpublish') }}</a>{% endif %}
            {% if DocLister.hasPermission('comments_delete') %}<a href="#" class="comment-delete btn btn-xs btn-warning" data-id="{{ data.id }}">{{ DocLister.translate('delete') }}</a>{% endif %}
            {% if DocLister.hasPermission('comments_undelete') %}<a href="#" class="comment-undelete btn btn-xs btn-info" data-id="{{ data.id }}">{{ DocLister.translate('undelete') }}</a>{% endif %}
            {% if DocLister.hasPermission('comments_remove') %}<a href="#" class="comment-remove btn btn-xs btn-danger" data-id="{{ data.id }}">{{ DocLister.translate('remove') }}</a>{% endif %}
            {% if DocLister.hasPermission('comments_edit') %}<a href="#" class="comment-edit btn btn-xs btn-success" data-id="{{ data.id }}">{{ DocLister.translate('edit') }}</a>{% endif %}
        </div>
        {% endif %}
        {% if DocLister.isNotGuest() %}
        <div class="comment-footer">
            <a href="#" class="comment-reply btn btn-primary" data-id="{{ data.id }}">{{ DocLister.translate('reply') }}</a>
            {% if data.editable %}
                <a href="#" class="comment-update btn btn-success" data-id="{{ data.id }}">{{ DocLister.translate('change') }}</a>
            {% endif %}
        </div>
        {% endif %}
    {% endif %}
    </div>
</div>
