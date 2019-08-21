{{ extends 'Forms/form.tpl' }}
{% block moderation %}
    <div class="form-group">
        <label class="checkbox-inline">
            <input type="checkbox" name="published" value="1" {{ data.published == 1 ? 'checked' : '' }}> Опубликован
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="deleted" value="1" {{ data.deleted == 1 ? 'checked' : '' }}>> Удален
        </label>
    </div>
{% endblock %}
