define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            $.getJSON('store/store_meal/searchListForMeal').then(function(res){
                var store_meal_list= res.data
                // 初始化表格参数配置
                Table.api.init({
                    extend: {
                        index_url: 'store/store/index' + location.search,
                        add_url: 'store/store/add',
                        edit_url: 'store/store/edit',
                        del_url: 'store/store/del',
                        multi_url: 'store/store/multi',
                        table: 'store',
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
                            {field: 'user_id', title: __('User_id'),visible:false, operate:false},
                            {field: 'name', title: __('Name'), operate:"LIKE %...%"},
                            {field: 'user.username', title: __('User.username')},
                            {
                                field: 'store_meal_hidden', 
                                title: __('Store_meal_hidden'),
                                searchList: store_meal_list,
                                operate: 'FIND_IN_SET', 
                                formatter:  Table.api.formatter.flag,
                            },
                            // {
                            //     field: 'store_meal_id', 
                            //     title: __('Store_meal_id'),
                            //     searchList: $.getJSON('store/store_meal/searchList'),
                            //     formatter: function(index,row,value){
                            //         return row.storemeal.name;
                            //     },
                            // },
                            {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate:false},
                            {field: 'store_image', title: __('Store_image'), events: Table.api.events.image, formatter: Table.api.formatter.images, operate:false},
                            {field: 'service_image', title: __('Service_image'), events: Table.api.events.image, formatter: Table.api.formatter.images, operate:false},
                            {field: 'product_num', title: __('Product_num'), operate:false},
                            {field: 'sales_money', title: __('Sales_money'), operate:false},
                            {field: 'sales_num', title: __('Sales_num'), operate:false},
                            {field: 'status', title: __('Status'), formatter: Table.api.formatter.toggle, searchList: {1: __('Normal'), 0: __('Hidden')}},
                            {field: 'sort', title: __('Sort'), operate:false},
                            {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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
                            // {field: 'user.status', title: __('User.status')},
                            // {field: 'user.is_delete', title: __('User.is_delete')},
                            // {field: 'user.create_time', title: __('User.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'user.update_time', title: __('User.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'user.delete_time', title: __('User.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'user.group_id', title: __('User.group_id')},
                            // {field: 'user.has_update_pwd', title: __('User.has_update_pwd')},
                            // {field: 'storemeal.id', title: __('Storemeal.id')},
                            // {field: 'storemeal.name', title: __('Storemeal.name')},
                            // {field: 'storemeal.sell_amount', title: __('Storemeal.sell_amount'), operate:'BETWEEN'},
                            // {field: 'storemeal.product_public_num', title: __('Storemeal.product_public_num')},
                            // {field: 'storemeal.status', title: __('Storemeal.status')},
                            // {field: 'storemeal.sort', title: __('Storemeal.sort')},
                            // {field: 'storemeal.is_delete', title: __('Storemeal.is_delete')},
                            // {field: 'storemeal.create_time', title: __('Storemeal.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'storemeal.update_time', title: __('Storemeal.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'storemeal.delete_time', title: __('Storemeal.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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
                url: 'store/store/recyclebin' + location.search,
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
                                    url: 'store/store/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'store/store/destroy',
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