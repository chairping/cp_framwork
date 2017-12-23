<table class="layui-table" lay-data="{height:315, url:'/index.php/Admin/Index/data', page:true, id:'test'}" lay-filter="test">
  <thead>
    <tr>
      <th lay-data="{field:'id', width:80, sort: true}">ID</th>
      <th lay-data="{field:'username', width:80}">用户名</th>
      <th lay-data="{field:'sex', width:80, sort: true}">性别</th>
      <th lay-data="{field:'city', width:80}">城市</th>
      <th lay-data="{field:'sign', width:177}">签名</th>
      <th lay-data="{field:'experience', width:80, sort: true}">积分</th>
      <th lay-data="{field:'score', width:80, sort: true}">评分</th>
      <th lay-data="{field:'classify', width:80}">职业</th>
      <th lay-data="{field:'wealth', width:135, sort: true}">财富</th>
      <th lay-data="{fixed: 'right', width:160, align:'center', toolbar: '#barDemo'}"></th>
    </tr>
  </thead>
</table>ss

<script type="text/html" id="barDemo">
  <a class="layui-btn layui-btn-primary layui-btn-mini" lay-event="detail">查看</a>
  <a class="layui-btn layui-btn-mini" lay-event="edit">编辑</a>
  <a class="layui-btn layui-btn-danger layui-btn-mini" lay-event="del">删除</a>
</script>

<script src="/admin/layui/dist/layui.js"></script>

<script>
layui.use(['jquery', 'element', 'table'], function(){
    var element = layui.element;
    var table = layui.table;
    var $ = layui.$ //重点处

 //监听表格复选框选择
  table.on('checkbox(test)', function(obj){
    console.log(obj)
  });

  //监听工具条
  table.on('tool(test)', function(obj){
    var data = obj.data;
    if(obj.event === 'detail'){
      layer.msg('ID：'+ data.id + ' 的查看操作');
    } else if(obj.event === 'del'){
      layer.confirm('真的删除行么', function(index){
        obj.del();
        layer.close(index);
      });
    } else if(obj.event === 'edit'){
      layer.alert('编辑行：<br>'+ JSON.stringify(data))
    }
  });

    $('.barDemo .layui-btn').on('click', function(){
        var type = $(this).data('type');
        active[type] ? active[type].call(this) : '';
    });
});

</script>
