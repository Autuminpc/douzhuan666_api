define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'task/task_apply/index' + location.search,
                    add_url: 'task/task_apply/add',
                    edit_url: 'task/task_apply/edit',
                    del_url: 'task/task_apply/del',
                    multi_url: 'task/task_apply/multi',
                    table: 'task_apply',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('任务申请ID')},
                        {field: 'task_id', title: __('任务ID')},
                        
                        {field: 'task.name', title: __('Task.name'), operate:false},
                        {field: 'task.type', title: __('Task.type'), searchList: {"1":__('普通任务'),"2":__('钻石任务')}, formatter: Table.api.formatter.flag, operate:false},
                        // {field: 'verify_mark', title: __('Verify_mark')},
                        {field: 'reward_amount', title: __('Reward_amount'), operate:false},
                        // {field: 'submit_file', title: __('Submit_file')},
                        {field: 'user_id', title: __('用户ID'), visible:false},
                        {field: 'user.username', title: __('User.username'), operate:false},
                        // {
                        //     field: 'user_id', 
                        //     title: __('User_id'),
                        //     formatter: function(index,row,value){
                        //         return row.user.username;
                        //     },
                        // },
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"0":__('Status 0'),"2":__('Status 2'),"3":__('Status 3')}, formatter: Table.api.formatter.status},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, sortable: true},
                        {field: 'submit_time', title: __('Submit_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, sortable: true},
                        {field: 'verify_time', title: __('Verify_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, sortable: true},
                        // {field: 'task.id', title: __('Task.id')},
                        // {field: 'task.type', title: __('Task.type')},
                        // {field: 'task.task_category_id', title: __('Task.task_category_id')},
                        // {field: 'task.task_platform_id', title: __('Task.task_platform_id')},
                        // {field: 'task.user_level_id', title: __('Task.user_level_id')},
                        // {field: 'task.is_user', title: __('Task.is_user')},
                        // {field: 'task.user_id', title: __('Task.user_id')},
                        // {field: 'task.name', title: __('Task.name')},
                        // {field: 'task.describe', title: __('Task.describe')},
                        // {field: 'task.cover', title: __('Task.cover')},
                        // {field: 'task.reward_amount', title: __('Task.reward_amount'), operate:'BETWEEN'},
                        // {field: 'task.video_url', title: __('Task.video_url'), formatter: Table.api.formatter.url},
                        // {field: 'task.max_apply_num', title: __('Task.max_apply_num')},
                        // {field: 'task.apply_num', title: __('Task.apply_num')},
                        // {field: 'task.end_time', title: __('Task.end_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'task.status', title: __('Task.status'), formatter: Table.api.formatter.status},
                        // {field: 'task.is_delete', title: __('Task.is_delete')},
                        // {field: 'task.create_time', title: __('Task.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'task.update_time', title: __('Task.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'task.delete_time', title: __('Task.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.id', title: __('User.id')},
                        // {field: 'user.user_level_hidden', title: __('User.user_level_hidden')},
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
                        // {field: 'user.total_recommend_income', title: __('User.total_recommend_income'), operate:'BETWEEN'},
                        // {field: 'user.total_task_commission', title: __('User.total_task_commission'), operate:'BETWEEN'},
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
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: '审核',
                                    title: __('审核'),
                                    text: __('审核'),
                                    classname: 'btn btn-xs btn-primary btn-dialog verify_one',
                                    url: 'task/task_apply/verify_one',
                                    visible: function (row) {
                                        // if(row.status == 1){
                                            return true;
                                        // }
                                        // return false;
                                    },
                                    callback: function (data) {
                                        table.bootstrapTable('refresh', {});
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    }
                                },
                            ]
                        }
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
            // 审核通过
            $(document).on("click", ".btn-passverify", function () {
                Layer.confirm(__('是否审核通过'), {
                    title: __('审核'),
                }, function (index) {
                    //确认
                    var rows = table.bootstrapTable('getSelections');
                    var ids = [];
                    for (let index = 0; index < rows.length; index++) {
                        const element = rows[index];
                        ids.push(element.id);
                    }
                    Fast.api.ajax({
                        url: "task/task_apply/handlepass",
                        data: {ids: ids},
                    }, function (data, ret) {
                        Layer.alert(ret.msg);
                        table.bootstrapTable('refresh', {});
                    });
                }, function (index) {
                    //取消
                });
            });

            // 审核通过
            $(document).on("click", ".btn-unpassverify", function () {
                Layer.confirm(__('是否审核不通过'), {
                    title: __('审核'),
                }, function (index) {
                    //确认
                    var rows = table.bootstrapTable('getSelections');
                    var ids = [];
                    for (let index = 0; index < rows.length; index++) {
                        const element = rows[index];
                        ids.push(element.id);
                    }
                    Fast.api.ajax({
                        url: "task/task_apply/handleunpass",
                        data: {ids: ids},
                    }, function (data, ret) {
                        // Layer.alert(ret.msg);
                        table.bootstrapTable('refresh', {});
                    });
                }, function (index) {
                    //取消
                    layer.close(index);
                });
            });


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
                url: 'task/task_apply/recyclebin' + location.search,
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
                                    url: 'task/task_apply/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'task/task_apply/destroy',
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
        verify_one: function () {
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