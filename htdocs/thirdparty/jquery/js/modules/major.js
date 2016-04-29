/**
 * Created by Administrator on 2015/11/30.
 */

function statusFormate(status){
    switch (parseInt(status)){
        case 0:return '已查看';break;
        case 1:return '通过';break;
        case 2:return '未通过';break;
        case 3:return '未查看';break;
    }
}