define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'mall/product_order_detail/index' + location.search,
                    add_url: 'mall/product_order_detail/add',
                    edit_url: 'mall/product_order_detail/edit',
                    del_url: 'mall/product_order_detail/del',
                    multi_url: 'mall/product_order_detail/multi',
                    table: 'product_order_detail',
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
                        {field: 'id', title: __('Id'), operate:false, visible:false},
                        {field: 'product_order_id', title: __('Product_order_id'), visible:false},
                        {field: 'product_id', title: __('Product_id'), visible:false},
                        // {field: 'product_cover', title: __('Product_cover')},
                        {field: 'product_name', title: __('Product_name')},
                        // {field: 'product_spec_id', title: __('Product_spec_id')},
                        // {field: 'product_spec_cover', title: __('Product_spec_cover')},
                        {field: 'product_spec_name', title: __('规格')},
                        {field: 'price', title: __('单价'), operate:false},
                        {field: 'num', title: __('Num'), operate:false},
                        {field: 'total_price', title: __('Total_price'), operate:false},
                        // {field: 'is_delete', title: __('Is_delete')},
                        // {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'productorder.id', title: __('Productorder.id')},
                        // {field: 'productorder.user_id', title: __('Productorder.user_id')},
                        // {field: 'productorder.express_company_id', title: __('Productorder.express_company_id')},
                        // {field: 'productorder.num', title: __('Productorder.num')},
                        // {field: 'productorder.total_price', title: __('Productorder.total_price'), operate:'BETWEEN'},
                        // {field: 'productorder.sn', title: __('Productorder.sn')},
                        // {field: 'productorder.pay_sn', title: __('Productorder.pay_sn')},
                        // {field: 'productorder.consignee_name', title: __('Productorder.consignee_name')},
                        // {field: 'productorder.consignee_mobile', title: __('Productorder.consignee_mobile')},
                        // {field: 'productorder.consignee_address', title: __('Productorder.consignee_address')},
                        // {field: 'productorder.tracking_sn', title: __('Productorder.tracking_sn')},
                        // {field: 'productorder.delivery_time', title: __('Productorder.delivery_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'productorder.complete_time', title: __('Productorder.complete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'productorder.status', title: __('Productorder.status')},
                        // {field: 'productorder.is_delete', title: __('Productorder.is_delete')},
                        // {field: 'productorder.create_time', title: __('Productorder.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'productorder.update_time', title: __('Productorder.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'productorder.delete_time', title: __('Productorder.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'product.id', title: __('Product.id')},
                        // {field: 'product.category_id', title: __('Product.category_id')},
                        // {field: 'product.cover', title: __('Product.cover')},
                        // {field: 'product.name', title: __('Product.name')},
                        // {field: 'product.subhead', title: __('Product.subhead')},
                        // {field: 'product.reward_user_level_id', title: __('Product.reward_user_level_id')},
                        // {field: 'product.price', title: __('Product.price'), operate:'BETWEEN'},
                        // {field: 'product.stock', title: __('Product.stock')},
                        // {field: 'product.sort', title: __('Product.sort')},
                        // {field: 'product.status', title: __('Product.status'), formatter: Table.api.formatter.status},
                        // {field: 'product.is_delete', title: __('Product.is_delete')},
                        // {field: 'product.create_time', title: __('Product.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'product.update_time', title: __('Product.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'product.delete_time', title: __('Product.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'productspec.id', title: __('Productspec.id')},
                        // {field: 'productspec.product_id', title: __('Productspec.product_id')},
                        // {field: 'productspec.cover', title: __('Productspec.cover')},
                        // {field: 'productspec.name', title: __('Productspec.name')},
                        // {field: 'productspec.price', title: __('Productspec.price'), operate:'BETWEEN'},
                        // {field: 'productspec.stock', title: __('Productspec.stock')},
                        // {field: 'productspec.status', title: __('Productspec.status')},
                        // {field: 'productspec.sort', title: __('Productspec.sort')},
                        // {field: 'productspec.is_delete', title: __('Productspec.is_delete')},
                        // {field: 'productspec.create_time', title: __('Productspec.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'productspec.update_time', title: __('Productspec.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'productspec.delete_time', title: __('Productspec.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
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
                url: 'mall/product_order_detail/recyclebin' + location.search,
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
                                    url: 'mall/product_order_detail/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'mall/product_order_detail/destroy',
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