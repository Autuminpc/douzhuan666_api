define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'finance/user_money_log/index' + location.search,
                    add_url: 'finance/user_money_log/add',
                    edit_url: 'finance/user_money_log/edit',
                    del_url: 'finance/user_money_log/del',
                    multi_url: 'finance/user_money_log/multi',
                    table: 'user_money_log',
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
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3'),"4":__('Type 4'),"5":__('Type 5'),"6":__('Type 6'),"7":__('Type 7'),"8":__('Type 8'),"9":__('Type 9'),"10":__('Type 10'),"11":__('Type 11')}, formatter: Table.api.formatter.normal},
                        {field: 'user_id', title: __('User_id'),visible:false},
                        {field: 'user.username', title: __('User.username')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN', operate:false},
                        {field: 'before', title: __('Before'), operate:'BETWEEN', operate:false},
                        {field: 'after', title: __('After'), operate:'BETWEEN',operate:false},
                        {field: 'no', title: __('No'),operate:false, visible:false},
                        {field: 'remark', title: __('Remark'), operate: "LIKE %...%"},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.id', title: __('User.id')},
                        // {field: 'user.user_level_hidden', title: __('User.user_level_hidden')},
                        // {field: 'user.user_level_hidden_id', title: __('User.user_level_hidden_id')},
                        // {field: 'user.user_level_id', title: __('User.user_level_id')},
                        // {field: 'user.user_agent_id', title: __('User.user_agent_id')},
                        // {field: 'user.first_parent', title: __('User.first_parent')},
                        // {field: 'user.second_parent', title: __('User.second_parent')},
                        // {field: 'user.third_parent', title: __('User.third_parent')},
                        // {field: 'user.username', title: __('User.username')},
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
                url: 'finance/user_money_log/recyclebin' + location.search,
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
                                    url: 'finance/user_money_log/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'finance/user_money_log/destroy',
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