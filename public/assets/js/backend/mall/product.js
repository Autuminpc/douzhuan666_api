define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'mall/product/index' + location.search,
                    add_url: 'mall/product/add',
                    edit_url: 'mall/product/edit',
                    del_url: 'mall/product/del',
                    multi_url: 'mall/product/multi',
                    table: 'product',
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
                        {
                            field: 'store_id', 
                            title: __('Store_id'),
                            visible:false,
                        },
                        {field: 'store.name', title: __('Store.name')},
                        {field: 'store.user_id', title: __('Store.user_id')},
                        {
                            field: 'category_id', 
                            title: __('Category_id'),
                            searchList: $.getJSON('mall/category/searchList'),
                            formatter: function(index,row,value){
                                return row.category.name;
                            }
                        },
                        {field: 'image', title: __('Image'), events: Table.api.events.image, formatter: Table.api.formatter.images, operate:false},
                        {field: 'cover', title: __('Cover'), events: Table.api.events.image, formatter: Table.api.formatter.images, operate:false},
                        {
                            field: 'name', 
                            title: __('Name'),
                            cellStyle : function(value, row, index, field){
                                return {
                                    css: {"min-width": "250px",
                                        "white-space": "nowrap",
                                        "text-overflow": "ellipsis",
                                        "overflow": "hidden",
                                        "max-width":"250px"
                                    }
                                };
                            },
                            operate: "LIKE %...%"
                        },
                        // {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        // {field: 'stock', title: __('Stock'), operate:'BETWEEN'},
                        {
                            field: 'spec_count', 
                            title: __('规格总数'), 
                            operate:false,
                            events: Controller.api.events.spec_count,
                            formatter: Controller.api.formatter.spec_count
                        },
                        {field: 'sort', title: __('Sort'), operate:false},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        // {field: 'is_delete', title: __('Is_delete')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'category.id', title: __('Category.id')},
                        // {field: 'category.name', title: __('Category.name')},
                        // {field: 'category.sort', title: __('Category.sort')},
                        // {field: 'category.status', title: __('Category.status')},
                        // {field: 'category.is_delete', title: __('Category.is_delete')},
                        // {field: 'category.create_time', title: __('Category.create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'category.update_time', title: __('Category.update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'category.delete_time', title: __('Category.delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'add_spec',
                                    title: __('添加规格'),
                                    text: __('添加规格'),
                                    classname: 'btn btn-xs btn-primary btn-dialog btn-add_spec',
                                    url: function (row) {
                                        return 'mall/product_spec/add?product_id='+row.id;
                                    },
                                },
                                {
                                    name: 'look_spec',
                                    title: __('查看规格'),
                                    text: __('查看规格'),
                                    classname: 'btn btn-xs btn-info btn-dialog btn-look_spec',
                                    url: function (row) {
                                        return 'mall/product_spec/index?product_id='+row.id;
                                    },
                                },
                            ],
                        },
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
                url: 'mall/product/recyclebin' + location.search,
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
                                    url: 'mall/product/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'mall/product/destroy',
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
            },
            formatter: {
                spec_count: function (value, row, index) {
                    //这里我们直接使用row的数据
                    return '<a class="btn btn-xs btn-spec_count">' + row.spec_count + '</a>';
                },
            },
            events: {
                spec_count: {
                    'click .btn-spec_count': function (e, value, row, index) {
                        // alert(1);
                        Backend.api.open('mall/product_spec/index/product_id/' + row.id, __('Detail'));
                    }
                }
            }
        }
    };
    return Controller;
});