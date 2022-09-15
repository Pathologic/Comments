(function (window, $, FileAPI) {
    function Comments(options) {
        var defaults = {
            connector: 'assets/snippets/Comments/ajax.php',
            commentsWrapperId: 'comments',
            commentClass: 'comment',
            commentPublishedClass: 'published',
            commentUnpublishedClass: 'unpublished',
            commentDeletedClass: 'deleted',
            commentsCountWrapperClass: 'comments-count-wrap',
            commentsCountClass: 'comments-count',
            noCommentsClass: 'no-comments',
            ratedClass: 'rated',
            positiveRatingClass: 'positive',
            negativeRatingClass: 'negative',
            ratingCountClass: 'rating-count',
            likeCountClass: 'like-count',
            dislikeCountClass: 'dislike-count',
            likeBtnClass: 'like',
            dislikeBtnClass: 'dislike',
            fileDeleteBtnClass: 'attachment-delete',
            subscribeWrapperClass: 'comments-subscription',
            formWrapperClass: 'comments-form-wrap',
            captchaWrapperClass: 'captcha-wrapper',
            replyBtnClass: 'comment-reply',
            cancelReplyBtnClass: 'comment-reply-cancel',
            updateBtnClass: 'comment-update',
            cancelUpdateBtnClass: 'comment-update-cancel',
            deleteBtnClass: 'comment-delete',
            undeleteBtnClass: 'comment-undelete',
            publishBtnClass: 'comment-publish',
            unpublishBtnClass: 'comment-unpublish',
            removeBtnClass: 'comment-remove',
            editBtnClass: 'comment-edit',
            previewWrapperClass: 'comment-preview-wrap',
            previewBtnClass: 'comment-preview',
            editableClass: 'editable',
            formDisabledClass: 'disabled',
            formPosition: 'append',
            saveFormFields: ['name', 'email'],
            thread: 0,
            lastComment: 0,
            notifyOptions: {},
            notyfyTimeout: 1000,
            lexicon: {
                server_error: 'Ошибка сервера: ',
                parse_error: 'Не удалось обработать ответ сервера',
                yes: 'Да',
                no: 'Нет',
                remove_confirm: 'Комментарий и все ответы на него будут безвозвратно удалены. Продолжить?'
            },
            uploader: false,
            allowedImages: ['jpg', 'jpeg', 'gif', 'png', 'svg'],
            maxImageSize: 10 * 1024 * 1024,
            uploaderClass: 'uploader'
        };
        this._options = $.extend({}, defaults, options);
        this._options = $.extend(this._options, {
            commentsWrapper: '#' + this._options.commentsWrapperId,
            comment: '.' + this._options.commentClass,
            commentsCountWrapper: '.' + this._options.commentsCountWrapperClass,
            commentsCount: '.' + this._options.commentsCountClass,
            noComments: '.' + this._options.noCommentsClass,
            subscribeWrapper: '.' + this._options.subscribeWrapperClass,
            formWrapper: '.' + this._options.formWrapperClass,
            captchaWrapper: '.' + this._options.captchaWrapperClass,
            replyBtn: '.' + this._options.replyBtnClass,
            cancelReplyBtn: '.' + this._options.cancelReplyBtnClass,
            previewWrapper: '.' + this._options.previewWrapperClass,
            updateBtn: '.' + this._options.updateBtnClass,
            cancelUpdateBtn: '.' + this._options.cancelUpdateBtnClass,
            previewBtn: '.' + this._options.previewBtnClass,
            deleteBtn: '.' + this._options.deleteBtnClass,
            undeleteBtn: '.' + this._options.undeleteBtnClass,
            publishBtn: '.' + this._options.publishBtnClass,
            unpublishBtn: '.' + this._options.unpublishBtnClass,
            removeBtn: '.' + this._options.removeBtnClass,
            editBtn: '.' + this._options.editBtnClass,
            likeBtn: '.' + this._options.likeBtnClass,
            dislikeBtn: '.' + this._options.dislikeBtnClass,
            fileDeleteBtn: '.' + this._options.fileDeleteBtnClass,
            uploaderArea: '.' + this._options.uploaderClass
        });
        if (typeof window.commentsLexicon === 'object') {
            this._options.lexicon = window.commentsLexicon;
        }

        return this.init();
    }

    Comments.prototype = {
        store: {},
        init: function () {
            var self = this;
            if (parseInt(self._options.thread) === 0 || $('form', self._options.formWrapper).length === 0) return;
            $(self._options.commentsWrapper).on('click', self._options.replyBtn, function (e) {
                e.preventDefault();
                $(self._options.formWrapper).remove();
                var form = self.createForm();
                var commentId = self.getCommentId(this);
                self.injectInput(form, {
                    parent: commentId,
                    action: 'reply'
                });
                $('#comment-' + commentId).append(form);
                self.initForm(form);
            }).on('click', self._options.cancelReplyBtn, function (e) {
                e.preventDefault();
                self.cancelReply();
            }).on('click', self._options.updateBtn, function (e) {
                e.preventDefault();
                $(self._options.formWrapper).remove();
                self.loadForm(self.getCommentId(this), 'update');
            }).on('click', self._options.publishBtn, function (e) {
                e.preventDefault();
                self.changeCommentProperty('publish', self.getCommentId(this), self._options.commentPublishedClass, self._options.commentUnpublishedClass);
            }).on('click', self._options.unpublishBtn, function (e) {
                e.preventDefault();
                self.changeCommentProperty('unpublish', self.getCommentId(this), self._options.commentUnpublishedClass, self._options.commentPublishedClass);
            }).on('click', self._options.deleteBtn, function (e) {
                e.preventDefault();
                self.changeCommentProperty('delete', self.getCommentId(this), self._options.commentDeletedClass);
            }).on('click', self._options.undeleteBtn, function (e) {
                e.preventDefault();
                self.changeCommentProperty('undelete', self.getCommentId(this), '', self._options.commentDeletedClass);
            }).on('click', self._options.removeBtn, function (e) {
                e.preventDefault();
                self.remove(self.getCommentId(this));
            }).on('click', self._options.editBtn, function (e) {
                e.preventDefault();
                $(self._options.formWrapper).remove();
                self.loadForm(self.getCommentId(this), 'edit');
            }).on('click', self._options.likeBtn, function(e) {
                e.preventDefault();
                var commentId = self.getCommentId(this);
                self.rate(commentId, 'like');
            }).on('click', self._options.dislikeBtn, function(e) {
                e.preventDefault();
                var commentId = self.getCommentId(this);
                self.rate(commentId, 'dislike');
            });
            $(self._options.subscribeWrapper).on('change', 'input[name="subscribe"]', function(){
                if ($(this).is(':checked')) {
                    self.changeSubscription('subscribe');
                } else {
                    self.changeSubscription('unsubscribe');
                }
            });
            var form = $(self._options.formWrapper);
            self._form = form.html();
            self.initForm(form);
            self.startEditExpiration();
        },
        createForm: function () {
            var form = $('<div/>', {
                class: this._options.formWrapperClass
            });
            form.html(this._form);
            this.updateCaptcha(undefined, form);

            return form;
        },
        getCommentId: function (element) {
            var parent = $(element).parents(this._options.comment);

            return parseInt(parent.data('id'));
        },
        cancelReply: function () {
            var self = this;
            $(self._options.formWrapper).remove();
            var form = self.createForm();
            if (self._options.formPosition === 'append') {
                form.insertAfter(self._options.commentsWrapper);
            } else {
                form.insertBefore(self._options.commentsWrapper);
            }
            self.initForm(form);
        },
        saveData: function (data) {
            if (typeof data === 'object') {
                this.store = data;
            }
        },
        loadData: function(form) {
            var fields = this._options.saveFormFields;
            for (var i = 0; i < fields.length; i++) {
                var field = fields[i];
                var el = $('input[name="' + field + '"]', form);
                if (el.length && typeof this.store[field] !== 'undefined') {
                    el.val(this.store[field]);
                }
            }
        },
        initForm: function (form) {
            var self = this;
            if (typeof form === 'undefined') {
                form = $(self._options.formWrapper);
            }
            $(self._options.previewWrapper, form).remove();
            form.off().on('submit', 'form', function (e) {
                e.preventDefault();
                var form = $(this);
                var action = $('input[name="action"]', form).val();
                switch (action) {
                    case 'reply':
                        self.reply(form);
                        break;
                    case 'update':
                        self.update(form, 'update');
                        break;
                    case 'edit':
                        self.update(form, 'edit');
                        break;
                    default:
                        self.create(form);
                }
                $(self._options.previewWrapper, self._options.formWrapper).remove();
            }).on('click', self._options.previewBtn, function (e) {
                e.preventDefault();
                $(self._options.previewWrapper, self._options.formWrapper).remove();
                var form = $('form', self._options.formWrapper);
                self.preview(form);
            }).on('click', self._options.fileDeleteBtn, function(e){
                e.preventDefault();
                var attachment = $(this).parents('.attachment');
                self.deleteAttachment(attachment);
            });
            this.loadData(form);
            if (self._options.uploader) {
                var placeholder = $(self._options.uploaderArea, form);
                window.FileAPI.event.dnd(placeholder.get(0), function (over) {
                    if (over) {
                        placeholder.addClass('dnd');
                    } else {
                        placeholder.removeClass('dnd');
                    }
                }, function (files) {
                    window.FileAPI.filterFiles(files, function (file) {
                        var ext = file.name.split('.').pop();
                        return self._options.allowedImages.includes(ext);
                    }, function (files) {
                        self.uploadAttachments(placeholder, files);
                    });
                });
            }
            if (typeof self._options.onInitFormCallback === 'function') {
                setTimeout(function () {
                    self._options.onInitFormCallback(self);
                }, 0);
            }
            /*
            FileAPI.event.on(upload[0], 'change', function (evt){
                var files = FileAPI.getFiles(evt); // Retrieve file list
                FileAPI.filterFiles(files, function (file){
                    console.log(file.type);
                    return /jpeg|gif|png$/.test(file.type);
                }, function (files){
                    uploader.upload(files);
                });
            })
            */
        },
        uploadAttachments: function(placeholder, files) {
            var self = this;
            if( files.length ){
                var options = {
                    url: self._options.connector,
                    files: { file: files },
                    data: {
                        action: 'uploadAttachment'
                    },
                    upload: function() {
                        //uploadBtn.addClass('disabled');
                        //progress.show();
                    },
                    progress: function (evt){
                        //var pr = evt.loaded/evt.total * 100;
                        //progress.css('width',pr+'%');
                    },
                    filecomplete: function (err, xhr){
                        //progress.css('width', 0).hide();
                        //uploadBtn.removeClass('disabled');
                        //uploader.clear();
                        if (!err) {
                            var response = JSON.parse(xhr.response);
                            if (response.status !== undefined && response.status == true) {
                                placeholder.append(response.attachment);
                            } else {
                                self.alert('error', response.messages);
                            }
                        }
                    },
                };
                window.FileAPI.upload(options);
            }
        },
        deleteAttachment: function(attachment) {
            var self = this;
            var id = $('input[name="attachment[]"]', attachment).val();
            if (id !== '') {
                var data = {
                    action: 'deleteAttachment',
                    id: id
                };
                $.post(self._options.connector,
                    data,
                    function (response) {
                        if (response.status) {
                            attachment.remove();
                        } else {
                            self.alert('error', response.messages);
                        }
                    },
                    'json'
                ).fail(function (xhr) {
                    self.handleAjaxError(xhr);
                });
            }
        },
        updateCaptcha: function (captcha, form) {
            var self = this;
            if (typeof form === 'undefined') {
                form = self._options.formWrapper;
            }
            if (typeof captcha === 'undefined') {
                if (typeof self._captcha !== 'undefined') {
                    captcha = self._captcha;
                } else {
                    return;
                }
            }
            self._captcha = captcha;
            var wrapper = $(self._options.captchaWrapper, form);
            if (captcha !== '' && wrapper.length > 0) {
                if (typeof self._options.onUpdateCaptchaCallback === 'function') {
                    self._options.onUpdateCaptchaCallback(self, wrapper, captcha);
                } else {
                    if (wrapper.get(0).nodeName === 'IMG') {
                        wrapper.attr('src', captcha);
                    } else {
                        wrapper.html(captcha);
                    }
                }
            }
        },
        create: function (form) {
            var self = this;
            var data = self.getFormData(form, {
                action: 'create',
                thread: self._options.thread
            });
            self.disableForm(form);
            $.post(self._options.connector,
                data,
                function (response) {
                    self.enableForm();
                    if (response.status) {
                        self.saveData(response.fields);
                        self.cancelReply();
                        if (response.hasOwnProperty('captcha')) {
                            self.updateCaptcha(response.captcha);
                        }
                        if (response.messages.length > 0) {
                            self.alert('success', response.messages);
                        }
                        if (typeof self._options.onCommentCreatedCallback === 'function') {
                            setTimeout(function () {
                                self._options.onCommentCreatedCallback(self, response);
                            }, 0);
                        } else {
                            self.load();
                        }
                    } else {
                        if (response.hasOwnProperty('errors') && Object.keys(response.errors).length > 0) {
                            $(self._options.formWrapper).html(response.output);
                            if (response.hasOwnProperty('captcha')) {
                                self.updateCaptcha(response.captcha);
                            }
                        } else {
                            self.alert('error', response.messages);
                        }
                    }
                },
                'json'
            ).fail(function (xhr) {
                self.enableForm();
                self.handleAjaxError(xhr);
            });
        },
        reply: function (form) {
            var self = this;
            var data = self.getFormData(form, {
                thread: self._options.thread
            });
            self.disableForm(form);
            $.post(self._options.connector,
                data,
                function (response) {
                    self.enableForm();
                    self.updateCaptcha(response.captcha);
                    if (response.status) {
                        self.saveData(response.fields);
                        self.cancelReply();
                        if (response.messages.length > 0) {
                            self.alert('success', response.messages);
                        }
                        if (typeof self._options.onCommentCreatedCallback === 'function') {
                            setTimeout(function () {
                                self._options.onCommentCreatedCallback(self, response);
                            }, 0);
                        } else {
                            self.load();
                        }
                    } else {
                        if (response.hasOwnProperty('errors') && Object.keys(response.errors).length > 0) {
                            var form = $(self._options.formWrapper);
                            form.html(response.output);
                            self.injectInput(form, {
                                parent: response.fields.parent,
                                action: 'reply'
                            });
                        } else {
                            self.alert('error', response.messages);
                        }
                    }
                },
                'json'
            ).fail(function (xhr) {
                self.enableForm();
                self.handleAjaxError(xhr);
            });
        },
        loadForm: function (commentId, action) {
            var self = this;
            var data = {
                action: action,
                thread: self._options.thread,
                id: commentId
            };
            $.post(self._options.connector,
                data,
                function (response) {
                    if (response.messages.length > 0) {
                        self.alert('error', response.messages);
                    } else {
                        var form = $('<div/>', {
                            class: self._options.formWrapperClass
                        });
                        form.html(response.output);
                        self.injectInput(form, {
                            id: commentId,
                            action: action
                        });
                        $('#comment-' + commentId).append(form);
                        self.initForm(form);
                    }
                },
                'json'
            ).fail(function (xhr) {
                self.handleAjaxError(xhr);
            });
        },
        update: function (form, action) {
            var self = this;
            var commentId = parseInt($('input[name="id"]', form).val());
            var data = self.getFormData(form, {
                thread: self._options.thread,
                id: commentId
            });
            self.enableForm();
            $.post(self._options.connector,
                data,
                function (response) {
                    self.enableForm();
                    if (action === 'update') {
                        self.updateCaptcha(response.captcha);
                    }
                    if (response.status) {
                        self.cancelReply();
                        if (response.messages.length > 0) {
                            self.alert('success', response.messages);
                        }
                        var callbackName = action === 'update' ? 'onCommentUpdatedCallback' : 'onCommentEditedCallback';
                        if (typeof self._options[callbackName] === 'function') {
                            setTimeout(function () {
                                self._options[callbackName](self, response, commentId);
                            }, 0);
                        } else {
                            self.loadComment(commentId);
                        }
                    } else {
                        if (response.hasOwnProperty('errors') && Object.keys(response.errors).length > 0) {
                            var form = $(self._options.formWrapper);
                            form.html(response.output);
                            self.injectInput(form, {
                                id: commentId,
                                action: action
                            });
                        } else {
                            self.alert('error', response.messages);
                        }
                    }
                },
                'json'
            ).fail(function (xhr) {
                self.enableForm();
                self.handleAjaxError(xhr);
            });
        },
        loadComment: function (commentId) {
            var self = this;
            var data = {
                action: 'loadComment',
                thread: self._options.thread,
                id: commentId
            };
            $.post(self._options.connector,
                data,
                function (response) {
                    if (response.comments) {
                        var comment = Object.values(response.comments)[0];
                        $('#comment-' + commentId).replaceWith(comment);
                    }
                },
                'json'
            ).fail(function (xhr) {
                self.handleAjaxError(xhr);
            });
        },
        load: function (lastComment) {
            var self = this;
            var data = {
                action: 'load',
                thread: self._options.thread,
                lastComment: typeof lastComment !== 'undefined' ? lastComment : self._options.lastComment
            };
            $.post(self._options.connector,
                data,
                function (response) {
                    if (response.count) {
                        $(self._options.noComments, self._options.commentsWrapper).addClass('hidden');
                        $(self._options.commentsCountWrapper, self._options.commentsWrapper).removeClass('hidden');
                        var counter = $(self._options.commentsCount, self._options.commentsWrapper);
                        var count = parseInt(counter.text());
                        counter.text(count + parseInt(response.count));
                        self._options.lastComment = response.lastComment;
                        Object.keys(response.comments).forEach(function (id) {
                            var comment = $(response.comments[id]);
                            self.startEditExpiration(comment);
                            if (parseInt(id) === 0) {
                                $(self._options.commentsWrapper).append(comment);
                            } else {
                                var element = $('#comment-' + id);
                                element.removeClass(self._options.editableClass);
                                var level = element.data('level');
                                var _level = 0;
                                var _element = element;
                                do {
                                    _element = _element.next(self._options.comment);
                                    _level = _element.length > 0 ? _element.data('level') : 0;
                                } while (_level > level);
                                if (_element.length) {
                                    comment.insertBefore(_element);
                                } else {
                                    $(self._options.commentsWrapper).append(comment);
                                }
                            }
                        });
                    }
                },
                'json'
            ).fail(function (xhr) {
                self.handleAjaxError(xhr);
            });
        },
        rate: function(commentId, action) {
            var self = this;
            var data = {
                action: action === 'like' ? 'like' : 'dislike',
                thread: self._options.thread,
                id: commentId
            };
            var comment = $('#comment-' + commentId);
            var ratingCount = $('.' + self._options.ratingCountClass, comment);
            var likeCount = $('.' + self._options.likeCountClass, comment);
            var dislikeCount = $('.' + self._options.dislikeCountClass, comment);
            if (comment.hasClass(self._options.ratedClass)) {
                return;
            }
            $.post(self._options.connector,
                data,
                function (response) {
                    if (response.status) {
                        comment.addClass(self._options.ratedClass).removeClass(self._options.positiveRatingClass, self._options.negativeRatingClass);
                        var count = response.count;
                        var like = response.like;
                        var dislike = response.dislike;
                        if (response.count > 0) {
                            count = '+' + count;
                            comment.addClass(self._options.positiveRatingClass);
                        } else if (response.count < 0) {
                            comment.addClass(self._options.negativeRatingClass);
                        }
                        ratingCount.text(count);
                        likeCount.text(like);
                        dislikeCount.text(dislike);
                    }
                    if (response.messages.length > 0) {
                        self.alert(response.status ? 'success' : 'error', response.messages);
                    }
                },
                'json'
            ).fail(function (xhr) {
                self.handleAjaxError(xhr);
            });
        },
        changeSubscription: function(action) {
            var self = this;
            var data = {
                action: action === 'subscribe' ? 'subscribe' : 'unsubscribe',
                thread: self._options.thread
            };
            $.post(self._options.connector,
                data,
                function (response) {
                    if (response.status) {
                        self.alert('success', self.translate('subscription_updated'));
                    } else {
                        $('input[name="subscribe"]', self._options.subscribeWrapper).prop('checked', action !== 'subscribe');
                    }
                },
                'json'
            ).fail(function (xhr) {
                self.handleAjaxError(xhr);
            });
        },
        startEditExpiration: function (comments) {
            var self = this;
            if (typeof comments === 'undefined') {
                comments = $('.' + self._options.editableClass, self._options.commentsWrapper);
            }
            comments.each(function () {
                var that = $(this);
                var ttl = parseInt(that.data('edit-ttl'));
                if (that.hasClass(self._options.editableClass) && typeof ttl !== 'undefined') {
                    setTimeout(function () {
                        that.removeClass(self._options.editableClass);
                    }, ttl * 1000);
                }
            });
        },
        getFormData: function (form, add) {
            var data = form.serializeArray();
            if (typeof add === 'object') {
                Object.keys(add).forEach(function (key) {
                    data.push({
                        name: key,
                        value: add[key]
                    });
                });
            }

            return data;
        },
        injectInput: function (form, input) {
            if (typeof input === 'object') {
                var _form = $('form', form);
                Object.keys(input).forEach(function (key) {
                    var el = $('input[name="' + key + '"]', _form);
                    if (el.length === 0) {
                        $('<input/>', {
                            type: 'hidden',
                            name: key,
                            value: input[key]
                        }).prependTo(_form);
                    } else {
                        el.val(input[key]);
                    }
                });
            }
        },
        preview: function (form) {
            var self = this;
            var data = self.getFormData(form, {
                action: 'preview',
                thread: self._options.thread
            });
            self.disableForm(form);
            $.post(self._options.connector,
                data,
                function (response) {
                    self.enableForm();
                    if (response.status) {
                        var preview = $('<div/>', {
                            html: response.fields.content,
                            class: self._options.previewWrapperClass
                        });
                        $(self._options.formWrapper).append(preview);
                    } else {
                        self.alert('error', response.messages);
                    }
                },
                'json'
            ).fail(function (xhr) {
                self.enableForm();
                self.handleAjaxError(xhr);
            });
        },
        changeCommentProperty: function (action, commentId, addClass, removeClass) {
            var self = this;
            var data = {
                thread: self._options.thread,
                action: action,
                id: commentId
            };
            $.post(self._options.connector,
                data,
                function (response) {
                    if (response.status) {
                        var el = $('#comment-' + commentId);
                        if (typeof removeClass !== 'undefined' && removeClass !== '') {
                            el.removeClass(removeClass);
                        }
                        if (typeof addClass !== 'undefined' && addClass !== '') {
                            el.addClass(addClass);
                        }
                        if (response.messages.length > 0) {
                            self.alert('success', response.messages);
                        }
                        var counter = $(self._options.commentsCount, self._options.commentsWrapper);
                        var count = parseInt(response.count);
                        counter.text(count);
                    } else {
                        self.alert('error', response.messages);
                    }
                },
                'json'
            ).fail(function (xhr) {
                self.handleAjaxError(xhr);
            });
        },
        remove: function (commentId) {
            var self = this;
            self.confirm(self.translate('remove_confirm'), function () {
                var data = {
                    action: 'remove',
                    thread: self._options.thread,
                    id: commentId
                };
                $.post(self._options.connector,
                    data,
                    function (response) {
                        if (response.status) {
                            var element = $('#comment-' + commentId);
                            var level = element.data('level');
                            var _level = 0;
                            var _element = element;
                            do {
                                _element = element.next(self._options.comment);
                                _level = _element.length > 0 ? _element.data('level') : 0;
                                if (_level > level) {
                                    element.remove();
                                    element = _element;
                                }
                            } while (_level > level);
                            element.remove();
                            if (response.messages.length > 0) {
                                self.alert('success', response.messages);
                            }
                            var counter = $(self._options.commentsCount, self._options.commentsWrapper);
                            var count = parseInt(response.count);
                            counter.text(count);
                        } else {
                            self.alert('error', response.messages);
                        }
                    },
                    'json'
                ).fail(function (xhr) {
                    self.handleAjaxError(xhr);
                });
            });
        },
        handleAjaxError: function (xhr) {
            var message = xhr.status == 200 ? this.translate('parse_error') : this.translate('server_error') + xhr.status + ' ' + xhr.statusText;
            this.alert('error', message);
        },
        disableForm: function (form) {
            form.addClass(this._options.formWrapperClass);
        },
        enableForm: function () {
            $('form', this._options.formWrapper).removeClass(this._options.formWrapperClass);
        },
        alert: function (type, messages) {
            var self = this;
            if (typeof messages === 'object' && messages.constructor === Array) {
                if (messages.length > 0) {
                    messages.forEach(function (item) {
                        self.alert(type, item);
                    });
                }
            } else {
                var options = $.extend({}, this._options.notifyOptions);
                if (typeof Noty === 'function') {
                    options.timeout = type === 'error' ? 0 : self._options.notyfyTimeout;
                    options.type = type;
                    options.text = messages;
                    new Noty(options).show();
                } else {
                    alert(messages);
                }
            }
        },
        confirm: function (message, callback) {
            var self = this;
            var options = $.extend({}, this._options.notifyOptions);
            if (typeof Noty === 'function') {
                Noty.closeAll();
                options.timeout = 0;
                options.text = message;
                options.type = 'warning';
                options.closeWith = 'button';
                options.buttons = [
                    Noty.button(self.translate('yes'), 'btn btn-danger', function () {
                        if (typeof callback === 'function') {
                            callback();
                        }
                        n.close();
                    }),
                    Noty.button(self.translate('no'), 'btn btn-success', function () {
                        n.close();
                    })
                ];
                var n = new Noty(options).show();
            } else {
                confirm(message);
            }
        },
        translate: function (key) {
            if (typeof this._options.lexicon[key] !== 'undefined') {
                return this._options.lexicon[key];
            }
        }
    };
    window.Comments = Comments;
})(window, jQuery);
