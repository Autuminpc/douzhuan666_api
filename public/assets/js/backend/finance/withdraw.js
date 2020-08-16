define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            $.getJSON('user/user_level/searchListForMeal').then(function(res){
                var user_level_list= res.data;
                // 初始化表格参数配置
                Table.api.init({
                    extend: {
                        index_url: 'finance/withdraw/index' + location.search,
                        add_url: 'finance/withdraw/add',
                        edit_url: 'finance/withdraw/edit',
                        del_url: 'finance/withdraw/del',
                        multi_url: 'finance/withdraw/multi',
                        table: 'withdraw',
                    }
                });

                var table = $("#table");

                //当表格数据加载完成时
                table.on('load-success.bs.table', function (e, data) {
                    //这里我们手动设置底部的值
                    $("#money").text(data.extend.total_amount);
                    $("#service_amount").text(data.extend.total_service_amount);
                    $("#arrival_amount").text(data.extend.total_arrival_amount);
                });

                // 初始化表格
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    sortName: 'id',
                    columns: [
                        [
                            {
                                checkbox: true,
                                formatter: function (value, row, index) {
                                    if(row.status != 0 && row.status != 2){
                                        this.checkbox  = false;
                                    }else{
                                        this.checkbox  = true;
                                    }
                                }
                            },
                            {field: 'id', title: __('Id'), operate:false},
                            // {field: 'user_id', title: __('User_id')},
                            {field: 'user.username', title: __('User.username')},
                            {
                                field: 'user.user_level_hidden', 
                                title: __('会员等级'), 
                                searchList: user_level_list,
                                operate: 'FIND_IN_SET', 
                                formatter:  Table.api.formatter.flag,
                                operate:false,
                            },
                            // {field: 'user.user_level_hidden_id', title: __('User.user_level_hidden_id')},
                            // {field: 'user.user_agent_id', title: __('User.user_agent_id')},
                            {
                                field: 'user.user_agent_id', 
                                title: __('User.user_agent_id'), 
                                searchList: {"1":"普通用户","2":"经销商","3":"代理商","4":"总代理","5":"合伙人",},
                                formatter:  Table.api.formatter.flag,
                                operate:false,
                            },
                            {field: 'amount', title: __('Amount'), sortable: true},
                            {field: 'service_amount', title: __('Service_amount'), operate:false},
                            {field: 'arrival_amount', title: __('Arrival_amount'), operate:false},
                            {field: 'order_no', title: __('Order_no')},
                            {field: 'bank_name', title: __('Bank_name'), operate: "LIKE %...%"},
                            {field: 'subbranch_name', title: __('Subbranch_name'), operate: "LIKE %...%"},
                            {field: 'bank_user', title: __('Bank_user'), operate: "LIKE %...%"},
                            {field: 'bank_number', title: __('Bank_number'), operate: "LIKE %...%"},
                            {field: 'verify_mark', title: __('Verify_mark'), operate:false},
                            {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4')}, formatter: Table.api.formatter.status},
                            {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, sortable: true},
                            {field: 'admin_name', title: '审核人', operate:false},
                            {field: 'verify_time', title: __('Verify_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            {field: 'arrival_time', title: __('到账时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            // {field: 'user.id', title: __('User.id')},
                            // {field: 'user.user_level_hidden', title: __('User.user_level_hidden')},
                            // {field: 'user.user_level_id', title: __('User.user_level_id')},
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
                                        classname: 'btn btn-xs btn-primary btn-dialog verify_withdraw',
                                        url: 'finance/withdraw/verify_withdraw',
                                        visible: function (row) {
                                            if(row.status == 0 || row.status == 2){
                                                return true;
                                            }
                                            return false;
                                        },
                                        callback: function (data) {
                                            table.bootstrapTable('refresh', {});
                                            // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                        }
                                    },
                                ],
                            }
                        ]
                    ],
                    onLoadSuccess:function(){
                        // 设置弹窗大小
                        $(".verify_withdraw").data("area", ['500px','500px']);
                    },
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
                // 审核通过-批量代付
                $(document).on("click", ".btn-multi_verify", function () {
                    Layer.confirm(__('是否代付打款'), {
                        title: __('审核通过'),
                    }, function (index) {
                        //确认
                        layer.close(index);
                        var rows = table.bootstrapTable('getSelections');
                        var ids = [];
                        for (let index = 0; index < rows.length; index++) {
                            const element = rows[index];
                            ids.push(element.id);
                        }
                        Fast.api.ajax({
                            url: "finance/withdraw/multi_daifu",
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
                url: 'finance/withdraw/recyclebin' + location.search,
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
                                    url: 'finance/withdraw/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'finance/withdraw/destroy',
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
        verify_withdraw: function () {
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