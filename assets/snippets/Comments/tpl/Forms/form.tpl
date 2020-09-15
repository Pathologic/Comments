{% if FormLister.isGuest() and not FormLister.isGuestEnabled() %}
<div class="alert alert-primary" role="alert">Только зарегистрированные пользователи могут оставлять комментарии.</div>
{% else %}
<h2>Написать комментарий</h2>
<form method="post">
    <input type="hidden" name="formid" value="comments-form">
    {% if FormLister.isGuest() %}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="phone">Ваше имя</label>
                    <input type="text" class="form-control" name="name" value="{{ data.name }}">
                    {{ plh['name.error'] | raw }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email">Ваш e-mail</label>
                    <input type="text" class="form-control" name="email" value="{{ data.email }}">
                    {{ plh['email.error'] | raw }}
                </div>
            </div>
        </div>
    {% endif %}
    <div class="form-group">
        <label for="comment">Комментарий</label>
        <textarea class="form-control" id="comment" placeholder="Напишите сообщение" rows="10" name="comment">{{ data.comment }}</textarea>
        {{ plh['comment.error'] | raw }}
    </div>
    <div class="text-right"><button class="btn comment-reply-cancel btn-info btn-lg">Отменить</button> <button class="btn btn-warning btn-lg" type="submit">Отправить</button> <button class="btn comment-preview btn-secondary btn-lg">Просмотр</button></div>
</form>
{% endif %}
