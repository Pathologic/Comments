## Comments
Набор инструментов для создания комментариев с помощью DocLister и FormLister. В основу положено решение [TreeClosureTable](https://github.com/drandin/TreeClosureTable).

### Установка
1. Установить FormLister версии 1.9.0 или выше.
2. Установить DocLister из GIT.
3. Установить плагин EvoTwig. Если плагин уже был установлен, то выполнить команду "composer update".
4. Создать необходимые таблицы:
```
<?php
include_once(MODX_BASE_PATH . 'assets/snippets/Comments/autoload.php');
$data = new Comments\Comments($modx);
$data->createTable();
```
5. Подключить скрипты и стили:
```
<link href="assets/snippets/Comments/css/comments.css" rel="stylesheet">
<link href="assets/snippets/Comments/js/noty/noty.css" rel="stylesheet">
<script src="assets/snippets/Comments/js/noty/noty.js" type="text/javascript"></script>
<script src="assets/snippets/Comments/js/comments.js"></script>
<script>
  new Comments({
    thread:[*id*],
    lastComment:[+lastComment+]
  });
</script>
```
6. Разместить вызовы сниппетов в шаблоне:
```
[!Comments!]
<div class="comments-form-wrap">
[!CommentsForm!]
</div>
```
