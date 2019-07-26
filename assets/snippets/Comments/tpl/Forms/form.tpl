<h2>Написать комментарий</h2>
<form method="post">
    <input type="hidden" name="formid" value="comments-form">
    <div class="form-group">
        <label for="comment">Комментарий</label>
        <textarea class="form-control" id="comment" placeholder="Напишите сообщение" rows="10" name="comment">[+comment.value+]</textarea>
        [+comment.error+]
    </div>
    <div class="text-right"><button class="btn comment-reply-cancel btn-info btn-lg">Отменить</button> <button class="btn btn-warning btn-lg" type="submit">Отправить</button> <button class="btn comment-preview btn-secondary btn-lg">Просмотр</button></div>
</form>
