define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            $.getJSON('user/user_level/searchListForMeal').then(function(res){
                var user_level_list= res.data
                // 初始化表格参数配置
                Table.api.init({
                    extend: {
                        index_url: 'user/meal/index' + location.search,
                        add_url: 'user/meal/add',
                        edit_url: 'user/meal/edit',
                        del_url: 'user/meal/del',
                        multi_url: 'user/meal/multi',
                        table: 'meal',
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
                            {field: 'id', title: __('Id'), operate:false},
                            {field: 'name', title: __('Name'), operate:false},
                            {field: 'sell_amount', title: __('Sell_amount'), operate: false,},
                            {
                                field: 'reward_user_level_id', 
                                title: __('Reward_user_level_id'),
                                searchList: user_level_list,
                                formatter: function(index,row,value){
                                    return row.rewarduserlevel.name;
                                },
                                operate:false,
                            },
                            
                            {
                                field: 'task_user_level_id', 
                                title: __('Task_user_level_id'),
                                searchList: user_level_list,
                                operate: 'FIND_IN_SET', 
                                formatter:  Table.api.formatter.flag,
                                // operate: false,
                            },
                            {
                                field: 'task_category_id', 
                                title: __('所发任务要求'), 
                                sortable: true,
                                searchList: $.getJSON('task/task_category/searchList'),
                                formatter: function(index,row,value){
                                    return row.taskcategory.name;
                                },
                            },
                            {field: 'task_reward_amount', title: __('Task_reward_amount'), operate: false},
                            {field: 'task_apply_num', title: __('Task_apply_num'), operate: false},
                            {field: 'first_meal_reward', title: __('First_meal_reward'), operate:'BETWEEN'},
                            {field: 'second_meal_reward', title: __('Second_meal_reward'), operate:'BETWEEN'},
                            {field: 'third_meal_reward', title: __('Third_meal_reward'), operate:'BETWEEN'},
                            // {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"0":__('Status 0')}, formatter: Table.api.formatter.status},
                            {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: false},
                            {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: false},
                            // {field: 'rewarduserlevel.id', title: __('Rewarduserlevel.id')},
                            // {field: 'rewarduserlevel.name', title: __('Rewarduserlevel.name')},
                            // {field: 'rewarduserlevel.first_task_reward', title: __('Rewarduserlevel.first_task_reward'), operate:'BETWEEN'},
                            // {field: 'rewarduserlevel.second_task_reward', title: __('Rewarduserlevel.second_task_reward'), operate:'BETWEEN'},
                            // {field: 'rewarduserlevel.third_task_reward', title: __('Rewarduserlevel.third_task_reward'), operate:'BETWEEN'},
                            // {field: 'rewarduserlevel.first_meal_reward', title: __('Rewarduserlevel.first_meal_reward'), operate:'BETWEEN'},
                            // {field: 'rewarduserlevel.second_meal_reward', title: __('Rewarduserlevel.second_meal_reward'), operate:'BETWEEN'},
                            // {field: 'rewarduserlevel.third_meal_reward', title: __('Rewarduserlevel.third_meal_reward'), operate:'BETWEEN'},
                            // {field: 'rewarduserlevel.day_task_num', title: __('Rewarduserlevel.day_task_num')},
                            // {field: 'rewarduserlevel.is_default', title: __('Rewarduserlevel.is_default')},
                            // {field: 'rewarduserlevel.status', title: __('Rewarduserlevel.status'), formatter: Table.api.formatter.status},
                            // {field: 'rewarduserlevel.is_delete', title: __('Rewarduserlevel.is_delete')},
                            // {field: 'rewarduserlevel.create_time', title: __('Rewarduserlevel.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'rewarduserlevel.update_time', title: __('Rewarduserlevel.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'rewarduserlevel.delete_time', title: __('Rewarduserlevel.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'tasklevel.id', title: __('Tasklevel.id')},
                            // {field: 'tasklevel.name', title: __('Tasklevel.name')},
                            // {field: 'tasklevel.first_task_reward', title: __('Tasklevel.first_task_reward'), operate:'BETWEEN'},
                            // {field: 'tasklevel.second_task_reward', title: __('Tasklevel.second_task_reward'), operate:'BETWEEN'},
                            // {field: 'tasklevel.third_task_reward', title: __('Tasklevel.third_task_reward'), operate:'BETWEEN'},
                            // {field: 'tasklevel.first_meal_reward', title: __('Tasklevel.first_meal_reward'), operate:'BETWEEN'},
                            // {field: 'tasklevel.second_meal_reward', title: __('Tasklevel.second_meal_reward'), operate:'BETWEEN'},
                            // {field: 'tasklevel.third_meal_reward', title: __('Tasklevel.third_meal_reward'), operate:'BETWEEN'},
                            // {field: 'tasklevel.day_task_num', title: __('Tasklevel.day_task_num')},
                            // {field: 'tasklevel.is_default', title: __('Tasklevel.is_default')},
                            // {field: 'tasklevel.status', title: __('Tasklevel.status'), formatter: Table.api.formatter.status},
                            // {field: 'tasklevel.is_delete', title: __('Tasklevel.is_delete')},
                            // {field: 'tasklevel.create_time', title: __('Tasklevel.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'tasklevel.update_time', title: __('Tasklevel.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'tasklevel.delete_time', title: __('Tasklevel.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        ]
                    ],
                    //快捷搜索,这里可在控制器定义快捷搜索的字段
                    search: false,
                    //启用普通表单搜索
                    commonSearch: true,
                    //显示导出按钮
                    showExport: false,
                    //可以控制是否默认显示搜索单表,false则隐藏,默认为false
                    searchFormVisible: false,
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
                url: 'user/meal/recyclebin' + location.search,
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
                                    url: 'user/meal/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'user/meal/destroy',
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