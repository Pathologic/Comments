<form method="post">
    <input type="hidden" name="formid" value="comment-edit">
    <input type="hidden" name="id" value="{{ data.id }}">
    <input type="hidden" name="thread" value="{{ data.thread }}">
    <input type="hidden" name="parent" value="{{ data.parent }}">
    <input type="hidden" name="context" value="{{ data.context }}">
    <div class="form-group">
        <label for="comment">{{ FormLister.translate('module.form.comment') }}</label>
        <textarea class="form-control" id="comment" placeholder="{{ FormLister.translate('module.form.comment_placeholder') }}" rows="10" name="comment" style="resize: none;">{{ data.comment }}</textarea>
        {{ plh['comment.error'] | raw }}
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="published" value="1" {{ data.published ? 'checked' : '' }}> {{ FormLister.translate('module.form.published') }}
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="deleted" value="1" {{ data.deleted ? 'checked' : '' }}> {{ FormLister.translate('module.form.deleted') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    {% if data.createdby == 0 %}
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">{{ FormLister.translate('module.form.name') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ data.name }}">
                    {{ plh['name.error'] | raw }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="text" name="email" class="form-control" value="{{ data.email }}">
                    {{ plh['email.error'] | raw }}
                </div>
            </div>
        </div>
    </div>
    {% endif %}
    <div class="container-fluid">
        <div class="row form-group">
            <div class="col-md-6">
                <b>{{ FormLister.translate('module.form.context') }}:</b> {{ data.context }}
            </div>
            <div class="col-md-6">
                <b>{{ FormLister.translate('module.form.resource') }}:</b> {{ data.thread }} {% if data.context == 'site_content' and data.resource %}(<a href="{{ constant('MODX_MANAGER_URL') ~ 'index.php?a=27&id=' ~ data.thread }}" target="main">{{ data.resource }}</a>){% endif %}
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <b>{{ FormLister.translate('module.form.createdon') }}:</b><br>{{ data.createdon }}<br>
                {% if data['user.username.createdby'] %}
                    <a class="user" href="{{ constant('MODX_MANAGER_URL') ~ 'index.php?a=88&id=' ~ data.createdby }}" target="main">{{ data['user.username.createdby'] }}</a>
                {% elseif  data.createdby == 0 %}
                    <span class="guest">{{ FormLister.translate('module.form.guest') }} {{ data.name }}</span>
                {% else %}
                    {{ data.createdby }}
                {% endif %}
            </div>
            {% if data.updatedby != 0 %}
                <div class="col-md-4">
                    <b>{{ FormLister.translate('module.form.updatedon') }}:</b><br>{% if data.updatedon %}{{ data.updatedon }} <br>{% endif %}
                    {% if data['user.username.updatedby'] %}
                    <a class="user" href="{{ constant('MODX_MANAGER_URL') ~ 'index.php?a=88&id=' ~ data.updatedby }}" target="main">{{ data['user.username.updatedby'] }}</a>
                    {% else %}
                        {{ data.updatedby }}
                    {% endif %}
                </div>
            {% endif %}
            {% if data.deletedby != 0 %}
                <div class="col-md-4">
                    <b>{{ FormLister.translate('module.form.deletedon') }}:</b><br>{% if data.deletedon %}{{ data.deletedon }} <br>{% endif %}{% if data['user.username.deletedby'] %}
                    <a class="user" href="{{ constant('MODX_MANAGER_URL') ~ 'index.php?a=88&id=' ~ data.deletedby }}" target="main">{{ data['user.username.deletedby'] }}</a>
                    {% else %}
                        {{ data.deletedby }}
                    {% endif %}
                </div>
            {% endif %}
        </div>
    </div>
    {% if data.attachments %}
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <p><br><b>Файлы</b></p>
            </div>
            <div class="attachments col-md-12">
                {% for item in data.attachments %}
                    <div class="attachment">
                        <a href="{{ constant('MODX_SITE_URL') ~ item.file }}" target="_blank"><img src="{{ constant('MODX_SITE_URL') ~ item.thumb }}"></a>
                        <div class="controls">
                            <a href="#" class="attachment-delete btn btn-sm btn-danger" data-id="{{ item.id }}"><i class="fa fa-trash"></i></a>
                        </div>
                    </div>
                {% endfor %}
            </div>
        </div>
    </div>
    {% endif %}
</form>
