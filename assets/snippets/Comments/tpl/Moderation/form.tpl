<h2>Редактирование комментария</h2>
<form method="post">
    <input type="hidden" name="formid" value="moderation-form">
    <div class="form-group">
        <label for="comment">Комментарий</label>
        <textarea class="form-control" id="comment" placeholder="Напишите сообщение" rows="10" name="comment">[+comment.value+]</textarea>
        [+comment.error+]
    </div>
    <div class="form-group">
        <label class="checkbox-inline">
            <input type="checkbox" name="published" value="1" [+c.published.1+]> Опубликован
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="deleted" value="1" [+c.deleted.1+]> Удален
        </label>
    </div>
    <div class="text-right"><button class="btn comment-reply-cancel btn-info btn-lg">Отменить</button> <button class="btn btn-warning btn-lg" type="submit">Сохранить</button> <button class="btn comment-preview btn-secondary btn-lg">Просмотр</button></div>
</form>
