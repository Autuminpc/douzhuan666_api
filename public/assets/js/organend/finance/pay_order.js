define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'finance/pay_order/index' + location.search,
                    add_url: 'finance/pay_order/add',
                    edit_url: 'finance/pay_order/edit',
                    del_url: 'finance/pay_order/del',
                    multi_url: 'finance/pay_order/multi',
                    table: 'pay_order',
                }
            });

            var table = $("#table");

            //当表格数据加载完成时
            table.on('load-success.bs.table', function (e, data) {
                //这里我们手动设置底部的值
                $("#money").text(data.extend.total_amount);
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate:false},
                        {field: 'order_type', title: __('Order_type'), searchList: {"0":__('Order_type 0'),"1":__('Order_type 1'),"2":__('Order_type 2'),"3":__('Order_type 3')}, formatter: Table.api.formatter.normal},
                        // {
                        //     field: 'user_id', 
                        //     title: __('User_id'),
                        //     formatter: function(index,row,value){
                        //         return row.user.username;
                        //     },
                        // },
                        {field: 'user_id', title: __('用户ID'), visible:false},
                        {field: 'user.username', title: __('User.username')},
                        {
                            field: 'meal.name', 
                            title: __('Meal.name'), 
                            operate: false,
                            formatter: function(index,row,value){
                                if(row.order_type == '1'){
                                    return row.meal.name;
                                }else if(row.order_type == '3'){
                                    return row.storemeal.name;
                                }else{
                                    return '';
                                }
                            },
                        },
                        // {
                        //     field: 'meal_id', 
                        //     title: __('Meal_id'),
                        //     formatter: function(index,row,value){
                        //         return row.meal.name;
                        //     },
                        // },
                        {field: 'order_no', title: __('Order_no')},
                        {field: 'pay_no', title: __('Pay_no')},
                        {field: 'payment_type', title: __('Payment_type'), searchList: {"admin":__('后台充值'),"alipay":__('支付宝'),"weixin":__('微信'),"alipay2":__('支付宝2')}, formatter: Table.api.formatter.flag,},
                        {field: 'pay_amount', title: __('Pay_amount'), operate:false},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"0":__('Status 0')}, formatter: Table.api.formatter.status, operate:false, visible:false},
                        // {field: 'is_delete', title: __('Is_delete'), searchList: {"1":__('Is_delete 1'),"0":__('Is_delete 0')}, formatter: Table.api.formatter.normal},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate:false},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.id', title: __('User.id')},
                        // {field: 'user.user_level_hidden', title: __('User.user_level_hidden')},
                        // {field: 'user.user_level_hidden_id', title: __('User.user_level_hidden_id')},
                        // {field: 'user.user_level_id', title: __('User.user_level_id')},
                        // {field: 'user.user_agent_id', title: __('User.user_agent_id')},
                        // {field: 'user.first_parent', title: __('User.first_parent')},
                        // {field: 'user.second_parent', title: __('User.second_parent')},
                        // {field: 'user.third_parent', title: __('User.third_parent')},
                        // {field: 'user.nickname', title: __('User.nickname')},
                        // {field: 'user.password', title: __('User.password')},
                        // {field: 'user.salt', title: __('User.salt')},
                        // {field: 'user.sex', title: __('User.sex')},
                        // {field: 'user.mobile', title: __('User.mobile')},
                        // {field: 'user.avatar', title: __('User.avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'user.money', title: __('User.money'), operate:'BETWEEN'},
                        // {field: 'user.totol_reward', title: __('User.totol_reward'), operate:'BETWEEN'},
                        // {field: 'user.totol_withdraw', title: __('User.totol_withdraw'), operate:'BETWEEN'},
                        // {field: 'user.address_province', title: __('User.address_province')},
                        // {field: 'user.address_city', title: __('User.address_city')},
                        // {field: 'user.address_area', title: __('User.address_area')},
                        // {field: 'user.address_detail', title: __('User.address_detail')},
                        // {field: 'user.bank_name', title: __('User.bank_name')},
                        // {field: 'user.subbranch_name', title: __('User.subbranch_name')},
                        // {field: 'user.bank_user', title: __('User.bank_user')},
                        // {field: 'user.bank_number', title: __('User.bank_number')},
                        // {field: 'user.total_task_income', title: __('User.total_task_income'), operate:'BETWEEN'},
                        // {field: 'user.total_task_commission', title: __('User.total_task_commission'), operate:'BETWEEN'},
                        // {field: 'user.total_recommend_income', title: __('User.total_recommend_income'), operate:'BETWEEN'},
                        // {field: 'user.successions', title: __('User.successions')},
                        // {field: 'user.maxsuccessions', title: __('User.maxsuccessions')},
                        // {field: 'user.prevtime', title: __('User.prevtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.logintime', title: __('User.logintime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.loginip', title: __('User.loginip')},
                        // {field: 'user.loginfailure', title: __('User.loginfailure')},
                        // {field: 'user.joinip', title: __('User.joinip')},
                        // {field: 'user.jointime', title: __('User.jointime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.token', title: __('User.token')},
                        // {field: 'user.status', title: __('User.status'), formatter: Table.api.formatter.status},
                        // {field: 'user.is_delete', title: __('User.is_delete')},
                        // {field: 'user.create_time', title: __('User.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.update_time', title: __('User.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.delete_time', title: __('User.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.group_id', title: __('User.group_id')},
                        // {field: 'meal.id', title: __('Meal.id')},
                        // {field: 'meal.reward_user_level_id', title: __('Meal.reward_user_level_id')},
                        // {field: 'meal.name', title: __('Meal.name')},
                        // {field: 'meal.sell_amount', title: __('Meal.sell_amount'), operate:'BETWEEN'},
                        // {field: 'meal.task_user_level_id', title: __('Meal.task_user_level_id')},
                        // {field: 'meal.task_reward_amount', title: __('Meal.task_reward_amount'), operate:'BETWEEN'},
                        // {field: 'meal.task_apply_num', title: __('Meal.task_apply_num')},
                        // {field: 'meal.status', title: __('Meal.status'), formatter: Table.api.formatter.status},
                        // {field: 'meal.is_delete', title: __('Meal.is_delete')},
                        // {field: 'meal.create_time', title: __('Meal.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'meal.update_time', title: __('Meal.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'meal.delete_time', title: __('Meal.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ],
                //快捷搜索,这里可在控制器定义快捷搜索的字段
                search: false,
                //启用普通表单搜索
                commonSearch: true,
                //显示导出按钮
                showExport: false,
                //可以控制是否默认显示搜索单表,false则隐藏,默认为false
                searchFormVisible: true,
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'finance/pay_order/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'finance/pay_order/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'finance/pay_order/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});