<form method="post">
    <input type="hidden" name="formid" value="comment-edit">
    <input type="hidden" name="id" value="{{ data.id }}">
    <input type="hidden" name="thread" value="{{ data.thread }}">
    <input type="hidden" name="parent" value="{{ data.parent }}">
    <input type="hidden" name="context" value="{{ data.context }}">
    <div class="form-group">
        <label for="comment">{{ FormLister.translate('module.form.comment') }}</label>
        <textarea class="form-control" id="comment" placeholder="{{ FormLister.translate('module.form.comment_placeholder') }}" rows="10" name="comment" style="resize: none;">{{ data.comment }}</textarea>
        {% set error = FormLister.getErrorMessage('comment') %}
        {% if error %}
            <div class="invalid-feedback">{{ error | join('<br>') }}</div>
        {% endif %}
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-xs-6">
                <div class="form-group">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="published" value="1" {{ data.published ? 'checked' : '' }}> {{ FormLister.translate('module.form.published') }}
                    </label>
                </div>
            </div>
            <div class="col-xs-6">
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
            <div class="col-xs-6">
                <div class="form-group">
                    <label for="name">{{ FormLister.translate('module.form.name') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ data.name }}">
                    {% set error = FormLister.getErrorMessage('name') %}
                    {% if error %}
                        <div class="invalid-feedback">{{ error | join('<br>') }}</div>
                    {% endif %}
                </div>
            </div>
            <div class="col-xs-6">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="text" name="email" class="form-control" value="{{ data.email }}">
                    {% set error = FormLister.getErrorMessage('email') %}
                    {% if error %}
                        <div class="invalid-feedback">{{ error | join('<br>') }}</div>
                    {% endif %}
                </div>
            </div>
        </div>
    </div>
    {% endif %}
    <div class="container-fluid">
        <div class="row form-group">
            <div class="col-xs-6">
                <b>{{ FormLister.translate('module.form.context') }}:</b> {{ data.context }}
            </div>
            <div class="col-xs-6">
                <b>{{ FormLister.translate('module.form.resource') }}:</b> {{ data.thread }} {% if data.context == 'site_content' and data.resource %}(<a href="{{ constant('MODX_MANAGER_URL') ~ 'index.php?a=27&id=' ~ data.thread }}" target="main">{{ data.resource }}</a>){% endif %}
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-xs-4">
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
                <div class="col-xs-4">
                    <b>{{ FormLister.translate('module.form.updatedon') }}:</b><br>{% if data.updatedon != '0000-00-00 00:00:00' %}{{ data.updatedon }} <br>{% endif %}
                    {% if data['user.username.updatedby'] %}
                    <a class="user" href="{{ constant('MODX_MANAGER_URL') ~ 'index.php?a=88&id=' ~ data.updatedby }}" target="main">{{ data['user.username.updatedby'] }}</a>
                    {% else %}
                        {{ data.updatedby }}
                    {% endif %}
                </div>
            {% endif %}
            {% if data.deletedby != 0 %}
                <div class="col-xs-4">
                    <b>{{ FormLister.translate('module.form.deletedon') }}:</b><br>{% if data.deletedon != '0000-00-00 00:00:00' %}{{ data.deletedon }} <br>{% endif %}{% if data['user.username.deletedby'] %}
                    <a class="user" href="{{ constant('MODX_MANAGER_URL') ~ 'index.php?a=88&id=' ~ data.deletedby }}" target="main">{{ data['user.username.deletedby'] }}</a>
                    {% else %}
                        {{ data.deletedby }}
                    {% endif %}
                </div>
            {% endif %}
        </div>
    </div>
</form>
