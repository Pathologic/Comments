<!DOCTYPE html>
<html>
<head>
    <title>[%module.title%]</title>
    <link rel="stylesheet" type="text/css" href="[+manager_url+]media/style/[+theme+]/style.css" />
    <link rel="stylesheet" href="[+manager_url+]media/style/common/font-awesome/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/js/easy-ui/themes/modx/easyui.css"/>
    <script type="text/javascript" src="[+site_url+]assets/modules/Comments/js/tabpane.js"></script>
    <script type="text/javascript" src="[+manager_url+]media/script/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/Comments/js/linkbutton.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/locale/easyui-lang-en.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/locale/easyui-lang-[+lang+].js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/Comments/js/lang/en.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/Comments/js/lang/[+lang+].js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/Comments/js/comments-grid.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/Comments/js/threads-grid.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/Comments/js/module.js"></script>
    <script type="text/javascript">
        var _commentsGrid = false;
        var _threadsGrid = false;
        var Config = {
            url:'[+connector+]',
            site_url: '[+site_url+]',
            manager_url: '[+manager_url+]'
        };
        var CommentsGridColumns = [
            {
                field: 'select',
                checkbox:true
            },
            {
                field: 'id',
                title: 'ID',
                sortable: true,
                fixed: true,
                width:50
            },
            {
                field: 'comment',
                title: commentsModuleLexicon.grid_comment,
                width:300,
                formatter: function(value, row) {
                    value += '<br><small><b>';
                    var state = [];
                    if (row.published !== '1') {
                        state.push(commentsModuleLexicon.grid_isunpublished);
                    }
                    if (row.deleted === '1') {
                        state.push(commentsModuleLexicon.grid_isdeleted);
                    }
                    if (row.updatedon !== '0000-00-00 00:00:00') {
                        state.push(commentsModuleLexicon.grid_ischanged + row.updatedon + '</b>');
                    }
                    value += state.join('; ');
                    value += '</b></small>';

                    return value;
                },
                styler: function(value,row){
                    if (row.deleted === '1' && row.published === '0') {
                        return 'color:magenta;'
                    } else {
                        if (row.deleted === '1') {
                            return 'color:red;';
                        }
                        if (row.published === '0') {
                            return 'color:purple;';
                        }
                    }
                }
            },
            {
                field: 'createdby',
                title: commentsModuleLexicon.grid_author,
                sortable: true,
                width: 100,
                formatter: function(value, row) {
                    value = parseInt(value);
                    if (value > 0 && typeof row.username !== 'undefined') {
                        value = '<a href="[+manager_url+]index.php?a=88&id=' + value + '" target="main">' + sanitize(row.username) + '</a>';
                    } else if (value === 0 && row.name != null) {
                        value = sanitize(row.name);
                    }

                    return value;
                }
            },
            {
                field: 'thread',
                title: commentsModuleLexicon.grid_thread,
                sortable: true,
                fixed: true,
                width: 70,
                formatter: sanitize
            },
            {
                field: 'title',
                title: commentsModuleLexicon.grid_thread_title,
                width:150,
                formatter: sanitize
            },
            {
                field: 'context',
                title: commentsModuleLexicon.grid_context,
                sortable: true,
                width:90,
                fixed:true
            },
            {
                field: 'createdon',
                width: 85,
                fixed: true,
                align: 'center',
                title: commentsModuleLexicon.grid_createdon,
                sortable: true,
                formatter: dateFormatter
            },
            {
                field: 'action',
                width: 70,
                title: '',
                align: 'center',
                fixed: true,
                formatter: function (value, row) {
                    var actions = '';
                    if (row.deleted === '1') {
                        actions += '<a class="action undelete" href="' + row.id + '" title="' + commentsModuleLexicon.undelete_btn + '"><i class="fa fa-undo fa-lg"></i></a>';
                    } else {
                        actions += '<a class="action delete" href="' + row.id + '" title="' + commentsModuleLexicon.delete_btn + '"><i class="fa fa-ban fa-lg"></i></a>';
                    }
                    if (row.published === '1') {
                        actions += '<a class="action unpublish" href="' + row.id + '" title="' + commentsModuleLexicon.unpublish_btn + '"><i class="fa fa-arrow-down fa-lg"></i></a>';
                    } else {
                        actions += '<a class="action publish" href="' + row.id + '" title="' + commentsModuleLexicon.publish_btn + '"><i class="fa fa-arrow-up fa-lg"></i></a>';
                    }
                    actions += '<a class="action remove" href="' + row.id + '" title="' + commentsModuleLexicon.remove_btn + '"><i class="fa fa-trash fa-lg"></i></a>';

                    return actions;
                }
            }
        ];
        var ThreadCommentGridColumns = CommentsGridColumns.filter(function(value){
            return value.field !== 'thread' && value.field !== 'context' && value.field !== 'title';
        });
        var ThreadsGridColumns = [
            {
                field: 'thread',
                title: 'ID',
                sortable: true,
                fixed: true,
                width:50
            },
            {
                field: 'context',
                title: commentsModuleLexicon.grid_context,
                sortable: true,
                fixed: true,
                width:50
            },
            {
                field: 'title',
                title: commentsModuleLexicon.grid_thread_title,
                sortable: true,
                width:150,
                fixed:true,
                formatter: sanitize
            },
            {
                field: 'comments_count',
                title: commentsModuleLexicon.grid_comments_count,
                sortable: true,
                width: 50,
                formatter: sanitize
            }
        ];
    </script>
    <style>
        body {
            overflow-y: scroll;
        }
        a.action {
            margin-right:5px;
        }
        a.action:last-child {
            margin-right:0;
        }
        a.action.delete {
            color:crimson;
        }
        a.action.undelete, .btn-green  {
            color:green;
        }
        a.action.publish {
            color:orange;
        }
        a.action.unpublish {
            color:grey;
        }
        a.action.remove, .btn-red {
            color:red;
        }
        .comment-wnd, .preview-wnd {
            padding:15px;
        }
        .comment-wnd textarea {
            resize:none;
        }
        .comment-wnd label {
            font-weight:bold;
        }
        .comment-wnd .invalid-feedback {
            font-size:0.8em;
            color:red;
        }
        a.user {
            color:green;
        }
        a.guest {
            color:blue;
        }
    </style>
</head>
<body>
<h1 class="pagetitle">
  <span class="pagetitle-icon">
    <i class="fa fa-comments-o"></i>
  </span>
    <span class="pagetitle-text">
    [%module.title%]
  </span>
</h1>
<div id="actions">
    <ul class="actionButtons">
        <li><a href="#" onclick="document.location.href='index.php?a=106';">[%module.close_btn%]</a></li>
    </ul>
</div>
<div class="sectionBody">
    <div class="dynamic-tab-pane-control tab-pane" id="commentsPane">
        <script type="text/javascript">
            tpResources = new WebFXTabPane(document.getElementById('commentsPane'), false);
        </script>

        <div class="tab-page" id="comments">
            <h2 class="tab"><i class="fa fa-comments-o"></i> [%module.comments%]</h2>
            <script type="text/javascript">
                tpResources.addTabPage(document.getElementById('comments'), function(){
                    if (_commentsGrid !== false) {
                         _commentsGrid.reload();
                    } else {
                        _commentsGrid = new CommentsGrid('comments-grid', {
                            url: Config.url,
                            site_url: Config.site_url,
                            manager_url: Config.manager_url,
                            columns: [CommentsGridColumns]
                        }, commentsModuleLexicon);
                    }
                });
            </script>
            <div class="tab-body">
                <table id="comments-grid"></table>
            </div>
        </div>
        <!-- div class="tab-page" id="threads">
            <h2 class="tab"><i class="fa fa-newspaper-o"></i> [%module.documents%]</h2>
            <script type="text/javascript">
                tpResources.addTabPage(document.getElementById('threads'), function(){
                    if (_threadsGrid !== false) {
                        _threadsGrid.reload();
                    } else {
                        return;
                        _threadsGrid = new CommentsGrid('comments-grid', {
                            url: Config.url,
                            columns: [ThreadsGridColumns],
                        }, commentsModuleLexicon);
                    }
                });</script>
            <div class="tab-body">
                <table id="threads-grid"></table>
            </div>
        </div -->
    </div>
</div>
</body>
</html>
