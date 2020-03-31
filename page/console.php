<?php
include_once 'common.php';
include 'header.php';
include 'menu.php';

$all = Typecho_Plugin::export();
if (!array_key_exists('Bmob', $all['activated'])) {
    exit("你没有启用Bmob插件,请启用该插件得到依赖");
}

require_once __DIR__ . '/../AccessBmob_Core.php';
$access = new AccessBmob_Core();
?>
<link rel="stylesheet" href="<?php $options->pluginUrl('AccessBmob/lib/sweetalert/sweetalert.css') ?>">
<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?php echo $access->title; ?></h2>
        </div>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs fix-tabs clearfix">
                    <li <?php _e($access->action == 'overview' ? ' class="current"' : '') ?>><a
                                href="<?php $options->adminUrl('extending.php?panel=' . AccessBmob_Plugin::$panel . '&action=overview'); ?>"><?php _e('访问概览'); ?></a>
                    </li>
                    <li <?php _e($access->action == 'logs' ? ' class="current"' : '') ?>><a
                                href="<?php $options->adminUrl('extending.php?panel=' . AccessBmob_Plugin::$panel . '&action=logs'); ?>"><?php _e('访问日志'); ?></a>
                    </li>
                    <li>
                        <a href="<?php $options->adminUrl('options-plugin.php?config=AccessBmob') ?>"><?php _e('插件设置'); ?></a>
                    </li>
                </ul>
            </div>

            <?php if ($access->action == 'logs'): ?>

                <div class="col-mb-12 typecho-list">
                    <div class="typecho-list-operate clearfix">

                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox"
                                                                                   class="typecho-table-select-all"/></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i
                                            class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i
                                            class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a data-action="delete" href="javascript:;"><?php _e('删除'); ?></a></li>
                                </ul>
                            </div>
                        </div>

                        <form method="get" class="search-form">
                            <div class="search" role="search">
                                <?php if ($request->get('filter', 'all') != 'all'): ?>
                                    <a href="<?php $options->adminUrl('extending.php?panel=' . AccessBmob_Plugin::$panel . '&action=logs'); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                                <?php endif; ?>
                                <input type="hidden" value="<?php echo $request->get('panel'); ?>" name="panel"/>
                                <?php if (isset($request->page)): ?>
                                    <input type="hidden" value="<?php echo $request->get('page'); ?>" name="page"/>
                                <?php endif; ?>
                                <select name="filter">
                                    <option <?php if ($request->filter == 'all'): ?> selected="true"
                                                                                     <?php endif; ?>value="all"><?php _e('所有'); ?></option>
                                    <option <?php if ($request->filter == 'ip'): ?> selected="true"
                                                                                    <?php endif; ?>value="ip"><?php _e('按IP'); ?></option>
                                    <option <?php if ($request->filter == 'path'): ?> selected="true"
                                                                                      <?php endif; ?>value="path"><?php _e('按路由'); ?></option>
                                </select>
                                <input style="<?php if ($request->get('filter', 'all') != 'ip'): ?>display: none<?php endif; ?>"
                                       type="text" class="text-s" placeholder=""
                                       value="<?php echo htmlspecialchars($request->ip); ?>" name="ip"/>
                                <input style="<?php if ($request->get('filter', 'all') != 'path'): ?>display: none<?php endif; ?>"
                                       type="text" class="text-s" placeholder=""
                                       value="<?php echo htmlspecialchars($request->path); ?>" name="path"/>
                                <select name="type">
                                    <option <?php if ($request->type == 1): ?> selected="true"
                                                                               <?php endif; ?>value="1"><?php _e('默认(仅人类)'); ?></option>
                                    <option <?php if ($request->type == 2): ?> selected="true"
                                                                               <?php endif; ?>value="2"><?php _e('仅爬虫'); ?></option>
                                    <option <?php if ($request->type == 3): ?> selected="true"
                                                                               <?php endif; ?>value="3"><?php _e('所有'); ?></option>
                                </select>
                                <input type="hidden" name="page" value="1">
                                <button type="button" class="btn btn-s"><?php _e('筛选'); ?></button>
                            </div>
                        </form>
                    </div><!-- end .typecho-list-operate -->

                    <form method="post" class="operate-form">
                        <div class="typecho-table-wrap">
                            <table class="typecho-list-table">
                                <colgroup>
                                    <col width="5"/>
                                    <col width="20%"/>
                                    <col width="25%"/>
                                    <col width="18%"/>
                                    <col width="20%"/>
                                    <col width="15%"/>
                                </colgroup>
                                <thead>
                                <tr>
                                    <th></th>
                                    <th><?php _e('受访地址'); ?></th>
                                    <th><?php _e('UA'); ?></th>
                                    <th><?php _e('IP地址'); ?></th>
                                    <th><?php _e('Referer'); ?></th>
                                    <th><?php _e('日期'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($access->logs['list'])): ?>
                                    <?php foreach ($access->logs['list'] as $log): ?>
                                        <tr id="<?php echo $log['id']; ?>" data-id="<?php echo $log['id']; ?>">
                                            <td><input type="checkbox" data-id="<?php echo $log['id']; ?>"
                                                       value="<?php echo $log['id']; ?>" name="id[]"/></td>
                                            <td><a target="_self"
                                                   href="<?php $options->adminUrl('extending.php?panel=' . AccessBmob_Plugin::$panel . '&filter=path&path=' . $log['path'] . '&type=' . $request->type); ?>"><?php echo urldecode(str_replace("%23", "#", $log['url'])); ?></a>
                                            </td>
                                            <td><a data-action="ua" href="#"
                                                   title="<?php echo $log['ua']; ?>"><?php echo $log['display_name']; ?></a>
                                            </td>
                                            <td>
                                                <?php if ($request->filter != 'ip'): ?>
                                                    <a target="_self"
                                                       href="<?php $options->adminUrl('extending.php?panel=' . AccessBmob_Plugin::$panel . '&filter=ip&ip=' . $access->long2ip($log['ip']) . '&type=' . $request->type); ?>">
                                                        👉</a>
                                                <?php endif; ?>
                                                <a data-action="ip"
                                                   data-ip="<?php echo $access->long2ip($log['ip']); ?>"
                                                   href="#"><?php echo $access->long2ip($log['ip']); ?></a>
                                            </td>
                                            <td><a target="_blank" data-action="referer"
                                                   href="<?php echo $log['referer']; ?>"><?php echo $log['referer']; ?></a>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i:s', $log['time']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6"><h6 class="typecho-list-table-title"><?php _e('当前无日志'); ?></h6>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form><!-- end .operate-form -->

                    <div class="typecho-list-operate clearfix">
                        <form method="get">

                            <div class="operate">
                                <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox"
                                                                                       class="typecho-table-select-all"/></label>
                                <div class="btn-group btn-drop">
                                    <button class="btn dropdown-toggle btn-s" type="button"><i
                                                class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i
                                                class="i-caret-down"></i></button>
                                    <ul class="dropdown-menu">
                                        <li><a data-action="delete" href="javascript:;"><?php _e('删除'); ?></a></li>
                                    </ul>
                                </div>
                            </div>


                            <?php if ($access->logs['rows'] > 1): ?>
                                <ul class="typecho-pager">
                                    <?php echo $access->logs['page']; ?>
                                </ul>
                            <?php endif; ?>
                        </form>
                    </div><!-- end .typecho-list-operate -->
                </div><!-- end .typecho-list -->

            <?php elseif ($access->action == 'overview'): ?>


                <div class="col-mb-12 typecho-list">

                    <h4 class="typecho-list-table-title">访问数表格</h4>

                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="10%"/>
                                <col width="30%"/>
                                <col width="25%"/>
                                <col width=""/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th></th>
                                <th><?php _e('浏览量(PV)'); ?></th>
                                <th><?php _e('访客数(UV)'); ?></th>
                                <th><?php _e('IP数'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>今日</td>
                                <td><?php echo $access->overview['pv']['today']['total']; ?></td>
                                <td><?php echo $access->overview['uv']['today']['total']; ?></td>
                                <td><?php echo $access->overview['ip']['today']['total']; ?></td>
                            </tr>
                            <tr>
                                <td>昨日</td>
                                <td><?php echo $access->overview['pv']['yesterday']['total']; ?></td>
                                <td><?php echo $access->overview['uv']['yesterday']['total']; ?></td>
                                <td><?php echo $access->overview['ip']['yesterday']['total']; ?></td>
                            </tr>
                            <tr>
                                <td>总计</td>
                                <td><?php echo $access->overview['pv']['all']['total']; ?></td>
                                <td><?php echo $access->overview['uv']['all']['total']; ?></td>
                                <td><?php echo $access->overview['ip']['all']['total']; ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="typecho-list-table-title">来源域名</h4>

                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="10%"/>
                                <col width="10%"/>
                                <col width="80%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th>排名</th>
                                <th>次数</th>
                                <th>来源域名</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($access->referer['domain'] as $key => $value): ?>
                                <tr>
                                    <td><?php echo $key + 1 ?></td>
                                    <td><?php echo $value['_count'] ?></td>
                                    <td><?php echo $value['entrypoint_domain'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="typecho-list-table-title">来源页</h4>

                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="10%"/>
                                <col width="10%"/>
                                <col width="80%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th>排名</th>
                                <th>次数</th>
                                <th>来源URL</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($access->referer['url'] as $key => $value): ?>
                                <tr>
                                    <td><?php echo $key + 1 ?></td>
                                    <td><?php echo $value["_count"] ?></td>
                                    <td><?php echo $value["entrypoint"] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
?>
<script type="text/javascript">
    $(document).ready(function () {
        $('a[data-action="ua"]').click(function () {
            swal('User-Agent', $.trim($(this).attr('title')), 'info');
            return false;
        });

        $('a[data-action="ip"]').click(function () {
            swal('IP查询中...', '正在查询...', 'info');
            $.ajax({
                url: '<?php echo rtrim(Helper::options()->index, '/') . '/accessBmob/ip.json';?>',
                method: 'get',
                dataType: 'json',
                data: {ip: $(this).data('ip')},
                success: function (data) {
                    if (data.code == 0) {
                        swal('IP查询成功', data.data, 'success');
                    } else {
                        swal('IP查询失败', data.data, 'warning');
                    }
                },
                error: function () {
                    swal('IP查询失败', '网络异常或PHP环境配置异常', 'warning');
                }
            });
            return false;
        });

        $('.dropdown-menu a[data-action="delete"]').click(function () {
            swal({
                title: '你确定?',
                text: '你确认要删除这些记录吗?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DD6B55',
                confirmButtonText: '是的',
                cancelButtonText: '算啦',
                closeOnConfirm: false
            }, function () {
                var ids = [];
                $('.typecho-list-table input[type="checkbox"]').each(function (index, elem) {
                    if (elem.checked) {
                        ids.push($(elem).data('id'));
                    }
                });

                if (ids.length == 0) {
                    return swal('错误', '你并没有勾选任何内容', 'warning');
                }
                $.ajax({
                    url: '<?php echo rtrim(Helper::options()->index, '/') . '/accessBmob/log/delete.json';?>',
                    method: 'post',
                    dataType: 'json',
                    contentType: 'application/json',
                    data: JSON.stringify(ids),
                    success: function (data) {
                        if (data.code == 0) {
                            swal('删除成功', '所选记录已删除', 'success');
                            $.each(ids, function (index, elem) {
                                $('.typecho-list-table tbody tr[data-id="' + elem + '"]').fadeOut(500).remove();
                            });
                        } else {
                            swal('错误', '发生错误了', 'warning');
                        }
                    }
                });
            });
            var $this = $(this);
            $this.parents('.dropdown-menu').hide().prev().removeClass('active');
        });

        var $form = $('form.search-form');
        var $ipInput = $form.find('input[name="ip"]');
        var $cidSelect = $form.find('select[name="cid"]');
        var $pathInput = $form.find('input[name="path"]');
        var $filterSelect = $form.find('select[name="filter"]');

        $filterSelect.on('change', function () {
            $ipInput.removeAttr('placeholder').val('').hide();
            $cidSelect.hide();
            $pathInput.removeAttr('placeholder').val('').hide();

            switch ($filterSelect.val()) {
                case 'ip':
                    $ipInput.attr('placeholder', '输入ip').show();
                    break;
                case 'post':
                    $cidSelect.show();
                    break;
                case 'path':
                    $pathInput.attr('placeholder', '输入路由').show();
                    break;
            }
        });

        $form.find('button[type="button"]').on('click', function () {
            var ipRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;

            if ($filterSelect.val() == 'ip' && !ipRegex.test($ipInput.val())) {
                return swal('筛选条件错误', 'IP地址不合法', 'warning');
            }

            $form.submit();
        });
    });
</script>
<script src="<?php $options->pluginUrl('AccessBmob/lib/sweetalert/sweetalert.min.js') ?>"></script>
<?php
include 'footer.php';
?>
