define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            $.getJSON('user/user_level/searchListForMeal').then(function(res){
                var user_level_list= res.data
                // 初始化表格参数配置
                Table.api.init({
                    extend: {
                        index_url: 'task/task/index' + location.search,
                        add_url: 'task/task/add',
                        edit_url: 'task/task/edit',
                        del_url: 'task/task/del',
                        multi_url: 'task/task/multi',
                        table: 'task',
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
                            {field: 'id', title: __('Id')},
                            {field: 'effective_url', title: __('视频链接'), searchList: {"0":__('无效链接'),"1":__('有效链接')}, formatter: Table.api.formatter.flag},
                            {field: 'cover', title: __('Cover'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate:false},
                            {field: 'name', title: __('Name')},
                            {
                                field: 'task_category_id', 
                                title: __('Task_category_id'), 
                                searchList: $.getJSON('task/task_category/searchList'),
                                formatter: function(index,row,value){
                                    return row.taskcategory.name;
                                },

                            },
                            {
                                field: 'task_platform_id', 
                                title: __('Task_platform_id'), 
                                searchList: $.getJSON('task/task_platform/searchList'),
                                formatter: function(index,row,value){
                                    return row.taskplatform.name;
                                },
                            },
                            {
                                field: 'user_level_id', 
                                title: __('User_level_id'),
                                searchList: user_level_list,
                                operate: 'FIND_IN_SET', 
                                formatter:  Table.api.formatter.flag,
                            },
                            {field: 'is_user', title: __('是否用户发布'), searchList: {"0":__('否'),"1":__('是')}, formatter: Table.api.formatter.normal},
                            // {field: 'userlevel.name', title: __('Userlevel.name')},
                            {field: 'user.username', title: __('发布者'), visible:false},
                            {
                                field: 'user_id', 
                                title: __('发布者'),
                                formatter: function(index,row,value){
                                    if(row.is_user == '1'){
                                        return row.user.username;
                                    }else{
                                        return '后台发布';
                                    }
                                },
                                operate: false,
                            },
                            
                            // {field: 'describe', title: __('Describe')},
                            {field: 'reward_amount', title: __('Reward_amount'), },
                            // {field: 'video_url', title: __('Video_url'), formatter: Table.api.formatter.url},
                            {field: 'max_apply_num', title: __('Max_apply_num')},
                            {field: 'apply_num', title: __('Apply_num'), operate:false},
                            {field: 'end_time', title: __('End_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, datetimeFormat:'YYYY-MM-DD', extend:'data-locale="{"format":"YYYY-MM-DD","customRangeLabel":"自定义"}"'},
                            {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"0":__('Status 0')}, formatter: Table.api.formatter.toggle},
                            // {field: 'is_delete', title: __('Is_delete'), searchList: {"1":__('Is_delete 1'),"0":__('Is_delete 0')}, formatter: Table.api.formatter.normal},
                            {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'taskcategory.id', title: __('Taskcategory.id')},
                            // {field: 'taskcategory.name', title: __('Taskcategory.name')},
                            // {field: 'taskcategory.status', title: __('Taskcategory.status'), formatter: Table.api.formatter.status},
                            // {field: 'taskcategory.is_delete', title: __('Taskcategory.is_delete')},
                            // {field: 'taskcategory.create_time', title: __('Taskcategory.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'taskcategory.update_time', title: __('Taskcategory.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'taskcategory.delete_time', title: __('Taskcategory.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'taskplatform.id', title: __('Taskplatform.id')},
                            // {field: 'taskplatform.name', title: __('Taskplatform.name')},
                            // {field: 'taskplatform.status', title: __('Taskplatform.status'), formatter: Table.api.formatter.status},
                            // {field: 'taskplatform.is_delete', title: __('Taskplatform.is_delete')},
                            // {field: 'taskplatform.create_time', title: __('Taskplatform.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'taskplatform.update_time', title: __('Taskplatform.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'taskplatform.delete_time', title: __('Taskplatform.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'userlevel.id', title: __('Userlevel.id')},
                            // {field: 'userlevel.weight', title: __('Userlevel.weight')},
                            // {field: 'userlevel.first_task_reward', title: __('Userlevel.first_task_reward'), operate:'BETWEEN'},
                            // {field: 'userlevel.second_task_reward', title: __('Userlevel.second_task_reward'), operate:'BETWEEN'},
                            // {field: 'userlevel.third_task_reward', title: __('Userlevel.third_task_reward'), operate:'BETWEEN'},
                            // {field: 'userlevel.first_meal_reward', title: __('Userlevel.first_meal_reward'), operate:'BETWEEN'},
                            // {field: 'userlevel.second_meal_reward', title: __('Userlevel.second_meal_reward'), operate:'BETWEEN'},
                            // {field: 'userlevel.third_meal_reward', title: __('Userlevel.third_meal_reward'), operate:'BETWEEN'},
                            // {field: 'userlevel.day_task_num', title: __('Userlevel.day_task_num')},
                            // {field: 'userlevel.is_default', title: __('Userlevel.is_default')},
                            // {field: 'userlevel.status', title: __('Userlevel.status'), formatter: Table.api.formatter.status},
                            // {field: 'userlevel.is_delete', title: __('Userlevel.is_delete')},
                            // {field: 'userlevel.create_time', title: __('Userlevel.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'userlevel.update_time', title: __('Userlevel.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'userlevel.delete_time', title: __('Userlevel.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'user.id', title: __('User.id')},
                            // {field: 'user.user_level_hidden', title: __('User.user_level_hidden')},
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
                                field: 'operate', title: __('Operate'), 
                                table: table, 
                                events: Table.api.events.operate, 
                                formatter: Table.api.formatter.operate,
                                buttons: [
                                    {
                                        name: 'copy',
                                        text: __('快速复制'),
                                        title: __('快速复制'),
                                        classname: 'btn btn-xs btn-danger btn-ajax btn-copy',
                                        url: 'task/task/copy',
                                        confirm: '是否复制该任务？',
                                        success: function (data, ret) {
                                            // Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                            Layer.alert(ret.msg);
                                            table.bootstrapTable('refresh', {});
                                            //如果需要阻止成功提示，则必须使用return false;
                                            return false;
                                        },
                                        error: function (data, ret) {
                                            Layer.alert(ret.msg);
                                            return false;
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
                url: 'task/task/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), align: 'left'},
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
                                    url: 'task/task/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'task/task/destroy',
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