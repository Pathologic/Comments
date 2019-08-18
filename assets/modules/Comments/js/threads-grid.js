(function($, window){
    function ThreadsGrid(gridId, options) {
        var defaults = {
            url: '',
            fitColumns:true,
            pagination:true,
            pageSize:50,
            pageList: [ 50, 100, 150, 200 ],
            idField:'id',
            nowrap:false,
            singleSelect:true,
            striped:true,
            checkOnSelect:true,
            selectOnCheck:false,
            sortName:'id',
            sortOrder:'desc',
            columns: [],
            queryParams: {
                action:'threads/listing'
            },
            onSelect: function (index) {
                $(this).datagrid('unselectRow', index);
            }
        };
        this.grid = '#' + gridId;
        this._options = $.extend({}, defaults, options);
        $(this.grid).datagrid(this._options);
    }
    ThreadsGrid.prototype = {
        openComments: function(id) {

        },
        destroyWindow: function (wnd) {
            var mask = $('.window-mask');
            wnd.window('destroy', true);
            $('.window-shadow,.window-mask').remove();
            $('body').css('overflow', 'auto').append(mask);
        },
        handleAjaxError: function(xhr) {
            var message = xhr.status == 200 ? 'Не удалось обработать ответ сервера' : 'Ошибка сервера: ' + xhr.status + ' ' + xhr.statusText;
            $.messager.alert('Ошибка', message, 'error');
        }
    };

    window.ThreadsGrid = ThreadsGrid;
})(jQuery, window);
