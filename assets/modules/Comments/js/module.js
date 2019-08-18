var sanitize = function(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/>/g, '&gt;')
        .replace(/</g, '&lt;')
        .replace(/"/g, '&quot;');
};
var dateFormatter = function(value) {
    var sql = value.split(/[- :]/);
    var d = new Date(sql[0], sql[1] - 1, sql[2], sql[3], sql[4], sql[5]);
    var year = d.getFullYear();
    var month = d.getMonth() + 1;
    var day = d.getDate();
    var hour = d.getHours();
    var min = d.getMinutes();
    return ('0' + day).slice(-2) + '.' + ('0' + month).slice(-2) + '.' + year + '<br>' + ('0' + hour).slice(-2) + ':' + ('0' + min).slice(-2);
};

