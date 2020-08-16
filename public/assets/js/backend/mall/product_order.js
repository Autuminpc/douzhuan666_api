define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'mall/product_order/index' + location.search,
                    add_url: 'mall/product_order/add',
                    edit_url: 'mall/product_order/edit',
                    del_url: 'mall/product_order/del',
                    multi_url: 'mall/product_order/multi',
                    table: 'product_order',
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
                        // {field: 'user_id', title: __('User_id')},
                        // {field: 'score_product_id', title: __('Score_product_id')},
                        
                        // {
                        //     field: 'score_product_name', 
                        //     title: __('Score_product_name'),
                        //     cellStyle : function(value, row, index, field){
                        //         return {
                        //             css: {"min-width": "200px",
                        //                 "white-space": "nowrap",
                        //                 "text-overflow": "ellipsis",
                        //                 "overflow": "hidden",
                        //                 "max-width":"200px"
                        //             }
                        //         };
                        //     },
                        //     operate: "LIKE %...%"

                        // },
                        // {
                        //     field: 'score_product_cover', 
                        //     title: __('Score_product_cover'),
                        //     events: Table.api.events.image, 
                        //     formatter: Table.api.formatter.image, 
                        //     operate: false,
                        // },

                        // {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'store.name', title: __('店铺名称')},
                        {field: 'sn', title: __('Sn')},
                        {field: 'total_price', title: __('Total_price'), operate:'BETWEEN'},
                        {field: 'num', title: __('Num')},
                        {field: 'consignee_name', title: __('Consignee_name')},
                        {field: 'consignee_mobile', title: __('Consignee_mobile')},
                        {field: 'consignee_address', title: __('Consignee_address')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4')}, formatter: Table.api.formatter.status},

                        {field: 'delivery_time', title: __('Delivery_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'complete_time', title: __('Complete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'is_delete', title: __('Is_delete')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.id', title: __('User.id')},
                        // {field: 'user.parent_id', title: __('User.parent_id')},
                        // {field: 'user.username', title: __('User.username')},
                        // {field: 'user.mobile', title: __('User.mobile')},
                        // {field: 'user.password', title: __('User.password')},
                        // {field: 'user.avatar', title: __('User.avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'user.score', title: __('User.score'), operate:'BETWEEN'},
                        // {field: 'user.wx_account', title: __('User.wx_account')},
                        // {field: 'user.email', title: __('User.email')},
                        // {field: 'user.address', title: __('User.address')},
                        // {field: 'user.group_id', title: __('User.group_id')},
                        // {field: 'user.salt', title: __('User.salt')},
                        // {field: 'user.successions', title: __('User.successions')},
                        // {field: 'user.maxsuccessions', title: __('User.maxsuccessions')},
                        // {field: 'user.prevtime', title: __('User.prevtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.logintime', title: __('User.logintime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.loginip', title: __('User.loginip')},
                        // {field: 'user.loginfailure', title: __('User.loginfailure')},
                        // {field: 'user.joinip', title: __('User.joinip')},
                        // {field: 'user.jointime', title: __('User.jointime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.create_time', title: __('User.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.update_time', title: __('User.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.token', title: __('User.token')},
                        // {field: 'user.status', title: __('User.status')},
                        // {field: 'user.verification', title: __('User.verification')},
                        // {field: 'user.delete_time', title: __('User.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'user.is_delete', title: __('User.is_delete')},
                        // {field: 'scoreproduct.id', title: __('Scoreproduct.id')},
                        // {field: 'scoreproduct.category_id', title: __('Scoreproduct.category_id')},
                        // {field: 'scoreproduct.cover', title: __('Scoreproduct.cover')},
                        // {field: 'scoreproduct.name', title: __('Scoreproduct.name')},
                        // {field: 'scoreproduct.subhead', title: __('Scoreproduct.subhead')},
                        // {field: 'scoreproduct.price', title: __('Scoreproduct.price'), operate:'BETWEEN'},
                        // {field: 'scoreproduct.stock', title: __('Scoreproduct.stock')},
                        // {field: 'scoreproduct.sort', title: __('Scoreproduct.sort')},
                        // {field: 'scoreproduct.status', title: __('Scoreproduct.status'), formatter: Table.api.formatter.status},
                        // {field: 'scoreproduct.is_delete', title: __('Scoreproduct.is_delete')},
                        // {field: 'scoreproduct.create_time', title: __('Scoreproduct.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'scoreproduct.update_time', title: __('Scoreproduct.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'scoreproduct.delete_time', title: __('Scoreproduct.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'detail',
                                    title: __('详情'),
                                    text: __('详情'),
                                    classname: 'btn btn-xs btn-primary btn-dialog btn-order_detail',
                                    // icon: 'fa fa-list',
                                    url: function (row) {
                                        return 'mall/product_order_detail/index?product_order_id='+row.id;
                                    },
                                    // url: 'mall/product_order_detail/index',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    }
                                },
                                // {
                                //     name: '修改收货信息',
                                //     title: __('修改收货信息'),
                                //     text: __('修改收货信息'),
                                //     classname: 'btn btn-xs btn-info btn-dialog',
                                //     // icon: 'fa fa-address-card',
                                //     url: 'mall/product_order/edit_address',
                                //     visible: function (row) {
                                //         if(row.status != 1){
                                //             return false;
                                //         }
                                //         return true;
                                //     },
                                // },
                                // {
                                //     name: '发货',
                                //     title: __('发货'),
                                //     text: __('发货'),
                                //     classname: 'btn btn-xs btn-primary btn-dialog',
                                //     url: 'mall/product_order/deliver_good',
                                //     visible: function (row) {
                                //         if(row.status == 1){
                                //             return true;
                                //         }
                                //         return false;
                                //     },
                                // },
                                {
                                    name: '发货',
                                    text: __('发货'),
                                    title: __('发货'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-deliver_good',
                                    url: 'mall/product_order/deliver_good',
                                    confirm: '是否确认已经发货？',
                                    visible: function (row) {
                                        if(row.status == 1){
                                            return true;
                                        }
                                        return false;
                                    },
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        return true;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    }
                                },
                                {
                                    name: '确认收货',
                                    text: __('确认收货'),
                                    title: __('确认收货'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-sure_recive',
                                    url: 'mall/product_order/sure_recive',
                                    confirm: '是否确认已经收货？',
                                    visible: function (row) {
                                        if(row.status == 2){
                                            return true;
                                        }
                                        return false;
                                    },
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        return true;
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
                url: 'mall/product_order/recyclebin' + location.search,
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
                                    url: 'mall/product_order/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'mall/product_order/destroy',
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
        edit_address: function () {
            Controller.api.bindevent();
        },
        deliver_good: function () {
            Controller.api.bindevent();
        },
        detail: function () {
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