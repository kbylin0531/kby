var t = $('#example').DataTable();





① Add row:

    t.row.add( [
        counter +'.1',
        counter +'.2',
        counter +'.3',
        counter +'.4',
        counter +'.5'
    ] ).draw( false );

    t.rows.add( [ {
            "name":       "Tiger Nixon",
            "position":   "System Architect",
            "salary":     "$3,120",
            "start_date": "2011/04/25",
            "office":     "Edinburgh",
            "extn":       "5421"
        }, {
            "name": "Garrett Winters",
            "position": "Director",
            "salary": "$5,300",
            "start_date": "2011/07/25",
            "office": "Edinburgh",
            "extn": "8422"
        } ] ).draw();


PS: draw() - 重新绘制表格以显示表格商发生的变化


② Search API :
    //全局搜索
    $('input.global_filter').on( 'keyup click', function () {
        $('#example').DataTable().search(
            $('#global_filter').val(),
            $('#global_regex').prop('checked'),
            $('#global_smart').prop('checked')
        ).draw();
    } );

    $('input.column_filter').on( 'keyup click', function () {
        $('#example').DataTable().column( $(this).parents('tr').attr('data-column') ).search(
            $('#col'+i+'_filter').val(),
            $('#col'+i+'_regex').prop('checked'),
            $('#col'+i+'_smart').prop('checked')
        ).draw();
    } );