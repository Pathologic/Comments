<h2>Написать комментарий</h2>
<form method="post">
    <input type="hidden" name="formid" value="comments-form">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="phone">Ваше имя</label>
                <input type="text" class="form-control[+name.classname+]" name="name" value="[+name.value+]">
                [+name.error+]
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="email">Ваш e-mail</label>
                <input type="text" class="form-control[+email.classname+]" name="email" value="[+email.value+]">
                [+email.error+]
            </div>
        </div>
    </div>
    <div class="form-group">
        <label for="comment">Комментарий</label>
        <textarea class="form-control" id="comment" name="comment" placeholder="Текст комментария" rows="10">[+comment.value+]</textarea>
        [+comment.error+]
    </div>
    <div class="text-right"><button class="btn comment-reply-cancel btn-info btn-lg">Отменить</button> <button class="btn btn-warning btn-lg" type="submit">Отправить</button> <button class="btn comment-preview btn-secondary btn-lg">Просмотр</button></div>
</form>
