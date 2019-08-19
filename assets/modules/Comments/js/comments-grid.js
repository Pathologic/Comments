(function($, window){
    function CommentsGrid(gridId, options) {
        var self = this;
        var defaults = {
            url: '',
            fitColumns:true,
            pagination:true,
            pageSize:50,
            pageList: [ 50, 100, 150, 200 ],
            idField:'id',
            nowrap:true,
            singleSelect:true,
            striped:true,
            checkOnSelect:false,
            selectOnCheck:false,
            sortName:'id',
            sortOrder:'desc',
            columns: CommentsGridColumns,
            queryParams: {
                action:'comments/listing'
            },
            onSelect: function (index) {
                $(this).datagrid('unselectRow', index);
            },
            onBeforeLoad: function() {
                $(this).datagrid('clearChecked');
                $('.btn-extra').hide();
            },
            onCheck: function() {
                $('.btn-extra').show();
            },
            onUncheck: function() {
                var rows = $(this).datagrid('getChecked');
                if (!rows.length) $('.btn-extra').hide();
            },
            onCheckAll: function() {
                $('.btn-extra').show();
            },
            onUncheckAll: function() {
                $('.btn-extra').hide();
            },
            onDblClickRow: function(index, row) {
                self.edit(row.id);
            }
        };
        this.grid = '#' + gridId;
        var grid = $(this.grid);
        this._options = $.extend({}, defaults, options);
        grid.datagrid(this._options);
        var panel = grid.datagrid('getPanel');
        var pager = grid.datagrid('getPager');
        pager.pagination({
            buttons:[
                {
                    cls: 'btn-extra action delete',
                    iconCls:'fa fa-ban fa-lg',
                    title: 'Удалить'
                },
                {
                    cls: 'btn-extra action undelete',
                    title: 'Восстановить',
                    iconCls:'fa fa-undo fa-lg'
                },
                {
                    cls: 'btn-extra action publish',
                    title: 'Опубликовать',
                    iconCls:'fa fa-arrow-up fa-lg'
                },
                {
                    cls: 'btn-extra action unpublish',
                    title: 'Скрыть',
                    iconCls:'fa fa-arrow-down fa-lg'
                },
                {
                    cls: 'btn-extra action remove',
                    title: 'Уничтожить',
                    iconCls:'fa fa-trash fa-lg'
                }
            ]
        });
        $('.btn-extra').hide();
        panel.on('click', 'a.action', function(e){
            e.preventDefault();
            var el = $(this);
            var id = el.hasClass('btn-extra') ? undefined : parseInt(el.attr('href'));
            var actions = ['publish', 'unpublish', 'delete', 'undelete', 'remove'];
            for (var i = 0; i < actions.length; i++) {
                if (el.hasClass(actions[i])) {
                    self.changeProperty(actions[i], id);
                    break;
                }
            }
        });
    }
    CommentsGrid.prototype = {
        reload: function() {
            $(this.grid).datagrid('reload');
        },
        getSelected: function() {
            var ids = [];
            var rows = $(this.grid).datagrid('getChecked');
            if (rows.length) {
                $.each(rows, function(i, row) {
                    ids.push(row.id);
                });
            }

            return ids;
        },
        changeProperty: function(property, id) {
            if (typeof property !== 'string' || property === '') return;
            var action = 'comments/' + property;
            var ids;
            if (typeof id === 'undefined') {
                ids = this.getSelected();
            } else {
                ids = [id];
            }
            var self = this;
            $.post(
                this._options.url,
                {
                    action: action,
                    ids: ids
                },
                function (response){
                    if (response.status) {
                        if (response.messages.length > 0) {
                            self.alert('', response.messages);
                        }
                        self.reload();
                    } else {
                        self.alert('error', response.messages);
                    }
                }, 'json'
            ).fail(function(xhr){
                self.handleAjaxError(xhr);
            });
        },
        remove: function(id) {
            var ids;
            if (typeof id === 'undefined') {
                ids = this.getSelected();
            } else {
                ids = [id];
            }
            var self = this;
            $.messager.confirm('Удаление', 'Комментарии и ответы на них будут удалены без возможности восстановления. Продолжить?', function (r) {
                if (r && ids.length > 0) {
                    $.post(
                        this._options.url,
                        {
                            action: action,
                            ids: ids
                        },
                        function (response) {
                            if (response.status) {
                                if (response.messages.length > 0) {
                                    self.alert('', response.messages);
                                }
                                self.reload();
                            } else {
                                self.alert('error', response.messages);
                            }
                        }, 'json'
                    ).fail(function(xhr){
                        self.handleAjaxError(xhr);
                    });
                }
            })
        },
        reply: function(commentId) {

        },
        edit: function(commentId) {
            if ($('#comment-' + commentId).length) return;
            var wndTpl = $('<div class="comment-wnd" id="comment-' + commentId + '"></div>');
            var self = this;
            $.post(
                this._options.url,
                {
                    action: 'comments/edit',
                    id: commentId
                },
                function (response) {
                    wndTpl.html(response.output);
                    self.createEditDialog(wndTpl, commentId);
                }, 'json'
            ).fail(function(xhr){
                self.handleAjaxError(xhr);
            });
        },
        createEditDialog: function(content, commentId) {
            var self = this;
            content.dialog({
                title: 'Редактировать комментарий ' + commentId,
                width:600,
                resizable: true,
                buttons:[{
                    iconCls: 'btn-red fa fa-ban fa-lg',
                    text:'Отмена',
                    handler:function(){
                        content.dialog('close');
                    }
                },
                {
                    iconCls: 'fa fa-eye fa-lg',
                    text: 'Предпросмотр',
                    handler:function(){
                        var form = $('form', content);
                        var data = form.serializeArray();
                        data.push({
                            name:'action',
                            value: 'comments/preview'
                        });
                        $.post(
                            self._options.url,
                            data,
                            function (response) {
                                if (response.status) {
                                    var preview = $('<div/>', {
                                        html: response.fields.content,
                                        class: 'preview-wnd'
                                    });
                                    preview.window({
                                        title:'Предпросмотр комментария',
                                        width: 400,
                                        collapsible: false,
                                        minimizable: false,
                                        maximizable: false,
                                        modal: true
                                    });
                                } else {
                                    self.alert('error', response.messages);
                                }
                            }, 'json'
                        ).fail(function(xhr){
                            self.handleAjaxError(xhr);
                        });
                    }
                },
                {
                    iconCls: 'btn-green fa fa-check fa-lg',
                    text:'Сохранить',
                    handler:function(){
                        var form = $('form', content);
                        var data = form.serializeArray();
                        data.push({
                            name:'action',
                            value: 'comments/edit'
                        });
                        $.post(
                            self._options.url,
                            data,
                            function (response) {
                                if (response.status) {
                                    if (response.messages.length > 0) {
                                        self.alert('', response.messages);
                                    }
                                    content.dialog('close');
                                    self.reload();
                                } else {
                                    if (response.hasOwnProperty('errors') && Object.keys(response.errors).length > 0) {
                                        content.html(response.output);
                                    } else {
                                        self.alert('error', response.messages);
                                    }
                                }
                            }, 'json'
                        ).fail(function(xhr){
                            self.handleAjaxError(xhr);
                        });
                    }
                }],
                onClose: function() {
                    $('#comment-' + commentId).remove();
                }
            });
        },
        handleAjaxError: function(xhr) {
            var message = xhr.status == 200 ? 'Не удалось обработать ответ сервера' : 'Ошибка сервера: ' + xhr.status + ' ' + xhr.statusText;
            this.alert('error', message);
        },
        alert: function(type, messages) {
            if (typeof messages === 'object' && messages.constructor === Array) {
                messages = messages.join('<br>');
            }
            $.messager.alert('&nbsp;', messages, type);
        }
    };

    window.CommentsGrid = CommentsGrid;
})(jQuery, window);
