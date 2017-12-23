<div class="nav-body" style="z-index:999;">
    <div class="top">
        <a class="bt-close" id="nav-close">
            <?php et('关闭'); ?>
        </a>
    </div>
    <ul class="menu-list">

        <?php foreach($menu_games as $game): ?>
        <li class="menu-item<?php if($_GET['gcid'] == $game['gcid']) { echo ' active'; } ?>">
            <a class="link" href="/product/index?gcid=<?php echo $game['gcid']; ?>&sale_name=<?php echo htmlspecialchars($_GET['sale_name']); ?>"><?php echo $game['name'] ?></a>
        </li>
        <?php endforeach; ?>

        <?php foreach($menu as $k => $v): ?>
        <li class="menu-item <?php if($v['sub_menu']) { echo 'more'; } ?>">

            <?php if($v['sub_menu']): ?>
            <a class="link" href="#"><?php echo $v['name']; ?></a>
            <div class="secondary_bar ">
                <ul class="menu-list-list">
                    <?php foreach($v['sub_menu'] as $v2): ?>
                    <li class="menu-item-item">
                        <a class="link-link" href="/product/index/level/2/category_id/<?php echo $v2['cate_id'] . '?active_id=' . $v['cate_id']; ?>"><?php echo $v2['name'] ?></a>
                    </li>
                    <?php endforeach;  ?>
                </ul>
            </div>
            <?php else: ?>
                <a class="link" href="/product/index/level/1/category_id/<?php echo $v['cate_id'] . '?active_id=' . $v['cate_id']; ?>"><?php echo $v['name']; ?></a>
            <?php endif; ?>
        </li>
        <?php endforeach;  ?>
    </ul>
</div>

