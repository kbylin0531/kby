/**
 * Created by Lin on 15-04-20.
 */

var qhw     = {q:'缺考',h:'缓考',w:'违纪',Q:'缺考',H:'缓考',W:'违纪'};
var scores5 = {A:'优秀',B:'良好',C:'中等',D:'及格',E:'不及格'}  ;
/**
 * 正在编辑的行，全局共享
 * @type {{}}
 */
var currentrow = {
    index:null,
    field:null
};

//归类
function classify(){
    var obj =  (arguments.length === 1)?qhw:arguments[1];
    return obj.hasOwnProperty($.trim(arguments[0]));
}

//识别值转编辑值
function visual2edit(cj){
    switch ($.trim(cj)){
        case '不合格':return 'E';
        case '合格':return 'D';
        case '优秀':return 'A';
        case '良好':return  'B';
        case '中等':return  'C';
        case '及格':return  'D';
        case '不及格':return  'E';
        case '缺考':return 'Q';
        case '缓考':return  'H';
        case '违纪':return  'W';
        default :return cj;
    }
}
function scoreTypeFormatter(val){
    switch($.trim(val)){
        case "ten":return '百分制';break;
        case 'five':return '五级制';break;
        case 'two':return '二级制';break;
        default:return '未知';
    }
}
function lockFormatter(value){
    return parseInt(value) > 0?'锁定':'开放';
}
//编辑值转识别值
function edit2visual(cj,scoretype){
    //全部大小写统一
    scoretype = $.trim(scoretype).toLowerCase();
    cj = $.trim(cj).toUpperCase();
    //确认是否输入异常状态
    if(classify(cj,qhw)) return qhw[cj];
    //非异常状态下检测对应的分数制
    if(scoretype==='ten'){
        //console.log('___',cj,!isNaN(cj),parseInt(cj) >=0 ,parseInt(cj) <= 100 );
        if(!isNaN(cj) && parseInt(cj) >=0 && parseInt(cj) <= 100 ){
            //必须是数字且范围是0 到 100
            return cj;
        }
        throw "ERROR_TEN";
    }else if(scoretype==='five' || scoretype==='two'){
        if( isNaN(cj) ){
            //五级制下必须是字母
            if(classify(cj,scores5)){
                return scores5[cj];
            }
        }
        throw "ERROR_FIVE";
    }
    return '';
}

/**
 * 统计人数
 */
var scores = {
    youxiu:0,
    lianghao:0,
    zhongdeng:0,
    jige:0,
    bujige:0
};
function scoreCount(){
    if(arguments.length){
        var score = arguments[0];
        var temp = parseInt(score);
        if(!isNaN(parseInt(score)) ){
            score = temp;
        }else{
            score = $.trim(score);
        }
        if(score === '优秀' || score >= 90){
            scores.youxiu ++;
        }else if(score === '良好' || score >= 80){
            scores.lianghao ++;
        }else if(score === '中等' || score >= 70){
            scores.zhongdeng ++;
        }else if(score === '及格' || score >= 60){
            scores.jige ++;
        }else if(score === '不及格' || score < 60){
            scores.bujige++;
        }else{}
    }
    return scores;
}
/**
 * 类型为未定义时不自动转化换
 * @param str
 * @returns {string}
 */
function transOnUndefined(str){
    return typeof str === 'undefined'?'': str;
}
/**
 * 数字转百分比
 * @param $num
 * @param $total
 * @returns {string}
 */
function count2Percent($num,$total){
    return formatFloat($num/$total*100,2)+'%'
}

/**
 * 处理识别值是否是错误的
 * true === 则正确
 * @funtion 处理识别值
 * @param val 成绩的显示值
 * @return boolean 如果识别值不是期望值，弹出提示框并返回FALSE
 */
function checkVisualValue(val){
    switch (val){
        case 'ERROR_TEN':
            val =  '百分制请输入0-100的数字';
            break;
        case 'ERROR_FIVE':
            val = '五级制输入时成绩为数字1-5或者字母(q、h、w)\n';
            break;
        case 'ERROR_TWO':
            val = '二级制输入时总评成绩1-0或者使用字母(q、h、w)\n';
            break;
        default :
            return true;
    }
    return val;
}


/**
 * 编辑过的行
 * @type {Array}
 */
var updatedRows = [];

/**
 * 初始化表格 表格编辑行为
 * 由于需要进行显示值和输入值的转换，现在仅用于成绩输入模块
 * @param selector 选择器或者jquery对象
 * @param fields 编辑域
 * @param checkLock 检查是否锁定的回调函数
 * @param scoretype 分数制
 */
function initDataGridEditor(selector,fields,checkLock,scoretype) {
    selector = string2Jquery(selector);

    selector.datagrid({
        onBeforeEdit:function(rowIndex, rowData){
            if(!isArray(fields)){
                fields = [fields];
            }
            for(var x in fields){
                rowData[fields[x]] = visual2edit(rowData[fields[x]]);
            }
        },
        onClickCell:function(index, field){
            if(checkLock(index, field)) return ;
            if((currentrow.index !== null ) && (currentrow.index !== index)){
                //如果没有正在编辑的行，过
                selector.datagrid('endEdit', currentrow.index);//结束上一次的编辑
            }
            console.log(field,fields)
            if(-1 !== $.inArray(field,fields)){
                currentrow.index = index; //初始化参数
                currentrow.field = field;
                selector.datagrid('startEditing',currentrow);
            }
        },
        onAfterEdit:function(index,dataRow,changes){
            console.log(index,dataRow,isEmptyObject(changes));
            if(!isArray(fields)){
                fields = [fields];
            }
            for(var x in fields){
                try{
                    dataRow[fields[x]] = edit2visual(dataRow[fields[x]],scoretype);
                }catch (e){
                    dataRow[fields[x]] = '';
                }
            }
            if(!isEmptyObject(changes)){
                updatedRows[index] = true;
            }
            $(this).datagrid('refreshRow', index);
        }
    });

    $(document).keydown(function(event) {
        if (currentrow.index!=null){    //如果正在编辑
            if(event.which==9 || event.which==13){  //如果是tab键或回车键
                if(checkLock()) return; //如果锁定课程
                event.preventDefault();
                if(selector.datagrid('getRows').length > currentrow.index+1){     //如果编辑下一个的下标小于列表长度
                    currentrow.index++; //下标+1
                    selector.datagrid('endEdit',currentrow.index-1);   //结束上一行的编辑

                    selector.datagrid('startEditing',currentrow);  //开始编辑下一行
                    selector.datagrid('selectRow',currentrow.index);  //设置选中下一行
                }else if(selector.datagrid('getRows').length-1 == currentrow.index){  //如果编辑下标处于最后一条位置
                    selector.datagrid('endEdit',currentrow.index);    //结束编辑
                    currentrow.index=null;  //设置下标为空
                }
            }
        }
    });

}
/**
 * 获取表格修改后的数据
 * (如果是增加的或者删除的行则待扩展)
 * @param dgrid 表格选择器或者对象
 * @param fields 选择的表格字段
 * @returns {*}
 */
function getDataFromGrid(dgrid,fields){
    dgrid = string2Jquery(dgrid);
    //先结束正在编辑的行
    if(currentrow.index !== null){
        dgrid.datagrid('endEdit', currentrow.index);
    }
    var rowlist = [];
    for(var x in updatedRows){
        var row = dgrid.datagrid('getData').rows[x];
        rowlist.push(row);
    }

    //获取全部更新过后的行
    console.log(rowlist);
    var resultlist = [];
    //检查非空
    for(var i=0; i<rowlist.length; i++){
        var result = {};
        dgrid.datagrid('endEdit',i);
        if(!isArray(fields)){
            fields = [fields];
        }
        for(var x in fields){
            var value = $.trim(rowlist[i][fields[x]]);
            //检查是否有错误信息
            if((value instanceof Object) && (value['_error'] === true)){
                //console.log(temp);
                return Messager.showMessage(value['_info']);
            }
            result[fields[x]] = value;//代表这个字段域 加工后的 结果
            result['_origin'] = rowlist[i];//原始值
        }
        if('_origin' in result){
            resultlist[i]=result;
        }
    }

    console.log(resultlist);
    return resultlist;
}

