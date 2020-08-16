define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            $.getJSON('user/user_level/searchListForMeal').then(function(res){
                var user_level_list= res.data
                Table.api.init({
                    extend: {
                        index_url: 'user/user/index',
                        add_url: 'user/user/add',
                        edit_url: 'user/user/edit',
                        del_url: 'user/user/del',
                        multi_url: 'user/user/multi',
                        table: 'user',
                    }
                });

                var table = $("#table");

                // 初始化表格
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    sortName: 'user.id',
                    columns: [
                        [
                            {checkbox: true},
                            {field: 'id', title: __('Id'), sortable: true},
                            // {field: 'group.name', title: __('Group')},
                            {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                            {field: 'username', title: __('Username'), operate: 'LIKE'},
                            {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                            {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                            {
                                field: 'user_level_hidden', 
                                title: __('User_Level_Id'), 
                                sortable: true,
                                searchList: user_level_list,
                                operate: 'FIND_IN_SET', 
                                formatter:  Table.api.formatter.flag,
                            },
                            {
                                field: 'user_agent_id', 
                                title: __('User_Agent_Id'), 
                                sortable: true,
                                searchList: $.getJSON('user/user_agent/searchList'),
                                formatter: function(index,row,value){
                                    return row.useragent.name;
                                },
                            },
                            // {field: 'sex', title: __('Gender'), visible: false, searchList: {1: __('Male'), 2: __('Female')},  formatter: Table.api.formatter.flag},
                            {
                                field: 'money', 
                                title: __('Money'), 
                                operate: false, 
                                sortable: true,
                                rowStyle:function(row, index){
                                    if (row.totol_reward - row.totol_withdraw != row.money) {
                                        return{
                                            css:{
                                                color:'red'
                                            }
                                        }
                                    }                 
                                },
                            },
                            {field: 'totol_reward', title: __('累计奖励'), operate: false, sortable: true},
                            {field: 'totol_withdraw', title: __('累计提现'), operate: false, sortable: true},
                            {field: 'first_parent', title: __('上级ID'), operate: false},
                            {field: 'first_count', title: __('一级团队人数'), operate: false},
                            {
                                field: 'organ_count', 
                                title: __('销售团队人数'), 
                                operate: false,
                                events: Controller.api.events.organ_count,
                                formatter: Controller.api.formatter.organ_count
                            },
                            {field: 'logintime', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: false, addclass: 'datetimerange', sortable: true, visible: false},
                            {field: 'loginip', title: __('Loginip'), visible: false, formatter: Table.api.formatter.search, operate: false},
                            {field: 'jointime', title: __('Jointime'), formatter: Table.api.formatter.datetime, operate: false, addclass: 'datetimerange', sortable: true},
                            {field: 'joinip', title: __('Joinip'), visible: false, formatter: Table.api.formatter.search, operate: false},
                            {field: 'status', title: __('Status'), formatter: Table.api.formatter.toggle, searchList: {1: __('Normal'), 0: __('Hidden')}},
                            {
                                field: 'operate', 
                                title: __('Operate'), 
                                table: table, 
                                events: Table.api.events.operate, 
                                formatter: Table.api.formatter.operate,
                                buttons: [
                                    {
                                        name: 'add_store',
                                        text: __('开通店铺'),
                                        title: __('开通店铺'),
                                        classname: 'btn btn-xs btn-danger btn-ajax btn-add_store',
                                        url: function (row) {
                                            return 'user/user/add_store?ids='+row.id;
                                        },
                                        confirm: '是否确认开通店铺？',
                                        visible: function(row){
                                            if(row.has_store != '0'){
                                                return false;
                                            }else{
                                                return true;
                                            }
                                        },
                                        success: function (data, ret) {
                                            // Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                            table.bootstrapTable('refresh', {});
                                            return true;
                                            //如果需要阻止成功提示，则必须使用return false;
                                            //return false;
                                        },
                                        error: function (data, ret) {
                                            // console.log(data, ret);
                                            Layer.alert(ret.msg);
                                            return false;
                                        }
                                    },
                                    // {
                                    //     name: 'add_store',
                                    //     title: __('开通店铺'),
                                    //     text: __('开通店铺'),
                                    //     classname: 'btn btn-xs btn-danger btn-dialog btn-add_store',
                                    //     url: function (row) {
                                    //         return 'user/user/add_store?ids='+row.id;
                                    //     },
                                    //     visible: function(row){
                                    //         if(row.has_store != '0'){
                                    //             return false;
                                    //         }else{
                                    //             return true;
                                    //         }
                                    //     }
                                    // },
                                    {
                                        name: 'set_agent',
                                        title: __('设置代理'),
                                        text: __('设置代理'),
                                        classname: 'btn btn-xs btn-primary btn-dialog btn-set_agent',
                                        url: function (row) {
                                            return 'user/user/set_agent?ids='+row.id;
                                        },
                                    },
                                    {
                                        name: 'set_level',
                                        title: __('设置会员'),
                                        text: __('设置会员'),
                                        classname: 'btn btn-xs btn-danger btn-dialog btn-set_level',
                                        url: function (row) {
                                            return 'user/user/set_level?ids='+row.id;
                                        },
                                    },
                                    {
                                        name: 'set_team',
                                        title: __('调整团队'),
                                        text: __('调整团队'),
                                        classname: 'btn btn-xs btn-success btn-dialog btn-set_team',
                                        url: function (row) {
                                            return 'user/user/set_team?ids='+row.id;
                                        },
                                    },
                                    {
                                        name: 'rechage_meal',
                                        title: __('线下充值套餐'),
                                        text: __('线下充值套餐'),
                                        classname: 'btn btn-xs btn-danger btn-dialog btn-rechage_meal',
                                        url: function (row) {
                                            return 'user/user/rechage_meal?ids='+row.id;
                                        },
                                    },
                                    {
                                        name: 'rechage',
                                        title: __('线下充值余额'),
                                        text: __('线下充值余额'),
                                        classname: 'btn btn-xs btn-info btn-dialog btn-rechage',
                                        url: function (row) {
                                            return 'user/user/rechage?ids='+row.id;
                                        },
                                    },
                                    {
                                        name: 'money_log',
                                        title: __('资金明细'),
                                        text: __('资金明细'),
                                        classname: 'btn btn-xs btn-primary btn-dialog btn-money_log',
                                        url: function (row) {
                                            return 'finance/user_money_log/index?user_id='+row.id;
                                        },
                                    },
                                ],
                            }
                        ]
                    ],
                    onLoadSuccess:function(){
                        // 设置弹窗大小
                        $(".btn-set_agent").data("area", ['400px','300px']);
                        // 设置弹窗大小
                        $(".btn-set_level").data("area", ['400px','300px']);
                        //设置线下充值的弹窗大小
                        $(".btn-rechage").data("area", ['400px','300px']);
                        // 设置弹窗大小
                        $(".btn-set_team").data("area", ['400px','300px']);
                        // 设置弹窗大小
                        $(".btn-rechage_meal").data("area", ['400px','300px']);
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
            });  
        },
        add: function () {
            Controller.api.bindevent();
        },
        set_agent: function () {
            Controller.api.bindevent();
        },
        add_store: function () {
            Controller.api.bindevent();
        },
        set_level: function () {
            Controller.api.bindevent();
        },
        set_team: function () {
            Controller.api.bindevent();
        },
        rechage: function () {
            Controller.api.bindevent();
        },
        rechage_meal: function () {
            Controller.api.bindevent();
        },
        team: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                organ_count: function (value, row, index) {
                    //这里我们直接使用row的数据
                    return '<a class="btn btn-xs btn-organ_count">' + row.organ_count + '</a>';
                },
            },
            events: {
                organ_count: {
                    'click .btn-organ_count': function (e, value, row, index) {
                        // alert(1);
                        Backend.api.open('user/user/team/ids/' + row.id, __('Detail'));
                    }
                }
            }
        }
    };
    return Controller;
});