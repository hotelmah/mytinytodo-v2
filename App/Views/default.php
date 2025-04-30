<?php

declare(strict_types=1);

use App\Utility;
use App\Config\Config;
use App\Core\MTTNotificationCenter;
use App\Lang\Lang;

header("Content-type: text/html; charset=utf-8");

function js_options()
{
    // Here we can use URIs instead of full URLs.
    $homeUrl = htmlspecialchars(Config::getUrl('url'));
    if ($homeUrl == '') {
        $homeUrl = Utility::getMttinfo('mtt_uri');
    }
    $a = array(
        "token" => htmlspecialchars(Utility::accessToken()),
        "title" => Utility::getUnsafeMttinfo('title'),
        "lang" => Lang::instance()->jsStrings(),
        "mttUrl" => Utility::getMttinfo('mtt_uri'),
        "homeUrl" => $homeUrl,
        "apiUrl" => Utility::getMttinfo('api_url'),
        "needAuth" => Utility::needAuth() ? true : false,
        "isLogged" => Utility::isLogged() ? true : false,
        "showdate" => Config::get('showdate') ? true : false,
        "showtime" => Config::get('showtime') ? true : false,
        "showdateInline" => Config::get('showdateInline') ? true : false,
        "duedatepickerformat" => htmlspecialchars(Config::get('dateformat2')),
        "firstdayofweek" => (int) Config::get('firstdayofweek'),
        "calendarIcon" => Utility::getMttinfo('theme_url') . 'images/calendar.svg',
        "autotag" => Config::get('autotag') ? true : false,
        "markdown" => Config::get('markup') == 'v1' ? false : true,
        "newTaskCounter" => Config::get('newTaskCounter') ? true : false,
    );

    $json = json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

    if ($json === false) {
        error_log("MTT Error: Failed to encode array of options to JSON. Code: " . (int)json_last_error());
        echo "{}";
    } else {
        echo $json;
    }
}
?>
<!doctype html>
<html data-appearance="<?php Utility::mttinfo('appearance'); ?>">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php Utility::mttinfo('title'); ?></title>
    <base href="Public/">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/gif" href="<?php Utility::mttinfo('theme_url'); ?>images/logo.gif">
    <link rel="stylesheet" type="text/css" href="<?php Utility::mttinfo('theme_url'); ?>css/style.css?v=<?php Utility::filever('theme', 'style.css'); ?>" media="all">
    <link rel="stylesheet" type="text/css" href="<?php Utility::mttinfo('theme_url'); ?>css/markdown.css?v=<?php Utility::filever('theme', 'markdown.css'); ?>" media="all">
    <?php if (Utility::getMttinfo('appearance') == 'system') : ?>
        <link rel="stylesheet" type="text/css" href="<?php Utility::mttinfo('theme_url'); ?>css/dark.css?v=<?php Utility::filever('theme', 'dark.css'); ?>" media="screen and (prefers-color-scheme:dark)">
    <?php elseif (Utility::getMttinfo('appearance') == 'dark') : ?>
        <link rel="stylesheet" type="text/css" href="<?php Utility::mttinfo('theme_url'); ?>css/dark.css?v=<?php Utility::filever('theme', 'dark.css'); ?>" media="screen">
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="<?php Utility::mttinfo('theme_url'); ?>css/print.css?v=<?php Utility::filever('theme', 'print.css'); ?>" media="print">
    <?php if (Config::get('rtl')) : ?>
        <link rel="stylesheet" type="text/css" href="<?php Utility::mttinfo('theme_url'); ?>css/style_rtl.css?v=<?php Utility::filever('theme', 'style_rtl.css'); ?>" media="all">
    <?php endif; ?>
    <?php MTTNotificationCenter::doAction('theme_head_end'); ?>
    <script type="text/javascript" src="<?php Utility::mttinfo('content_url'); ?>js/jquery.min.js?v=3.7.1"></script>
    <script type="text/javascript" src="<?php Utility::mttinfo('content_url'); ?>js/jquery-ui.min.js?v=1.13.2"></script>
    <script type="text/javascript" src="<?php Utility::mttinfo('content_url'); ?>js/jquery.ui.touch-punch.js?v=1.0.8-2"></script>
    <script type="text/javascript" src="<?php Utility::mttinfo('content_url'); ?>js/mytinytodo.js?v=<?php Utility::filever('content', 'mytinytodo.js'); ?>"></script>
    <script type="text/javascript" src="<?php Utility::mttinfo('content_url'); ?>js/mytinytodo_api.js?v=<?php Utility::filever('content', 'mytinytodo_api.js'); ?>"></script>

    <script type="text/javascript">
        $().ready(function() {
            mytinytodo.init(<?php js_options(); ?>).setApiDriver(MytinytodoAjaxApi).run();
        });
    </script>
</head>

<body <?php echo ((Lang::instance()->rtl()) ? 'dir="rtl"' : ''); ?>>
    <div id="mtt">

        <!-- Top block -->
        <div class="topblock">

            <div class="topblock-title">
                <h2><?php Utility::mttinfo('title'); ?></h2>
            </div>

            <div class="topblock-bar">
                <div id="msg"><span class="msg-text"></span>
                    <div class="msg-details"></div>
                </div>
                <div class="bar-menu">
                    <a href="#settings" class="mtt-only-authorized" data-settings-link="index"><?php Utility::_e('a_settings'); ?></a>
                    <span id="bar_public" style="display:none" class="mtt-need-auth-enabled"><?php Utility::_e('public_tasks'); ?></span>
                    <a href="#login" id="login_btn" class="mtt-need-auth-enabled"><?php Utility::_e('a_login'); ?></a>
                    <a href="#logout" id="logout_btn" class="mtt-need-auth-enabled" style="display:none"><?php Utility::_e('a_logout'); ?></a>
                    </span>
                </div>
            </div>

        </div>
        <!-- End of Top block -->


        <!-- Page: Tasks -->
        <div id="page_tasks" style="display:none">

            <div id="lists">
                <div class="tabs-n-button">
                    <ul class="mtt-tabs"></ul>
                    <div class="mtt-tabs-new-button" title="<?php Utility::_e('list_new'); ?>">
                        <div class="tab-height-wrapper"><span></span></div>
                    </div>
                </div>
                <div id="tabs_buttons">
                    <div class="tab-height-wrapper">
                        <div class="mtt-tabs-select-button mtt-img-button" title="<?php Utility::_e('list_select'); ?>"><span></span></div>
                    </div>
                </div>
            </div>


            <div id="toolbar">

                <div class="newtask-n-search-container">
                    <div class="taskbox-c">
                        <div class="mtt-taskbox">
                            <form id="newtask_form" method="post">
                                <input type="text" name="task" value="" maxlength="250" id="task" autocomplete="off" placeholder="<?php Utility::_e('htab_newtask'); ?>">
                                <div id="newtask_submit" class="mtt-taskbox-icon" title="<?php Utility::_e('btn_add'); ?>"></div>
                            </form>
                        </div>
                        <a href="#" id="newtask_adv" class="mtt-img-button" title="<?php Utility::_e('advanced_add'); ?>"><span></span></a>
                    </div>
                    <div class="searchbox-c">
                        <div class="mtt-searchbox">
                            <input type="text" name="search" value="" maxlength="250" id="search" autocomplete="off">
                            <div class="mtt-searchbox-icon mtt-icon-search"></div>
                            <div id="search_close" class="mtt-searchbox-icon mtt-icon-cancelsearch"></div>
                        </div>
                    </div>
                </div>

                <div id="searchbar" style="display:none"><?php Utility::_e('searching'); ?> <span id="searchbarkeyword"></span></div>

                <div id="mtt-tag-toolbar" style="display:none">
                    <div class="tag-toolbar-content">
                        <span id="mtt-tag-filters"></span>
                    </div>
                    <div class="tag-toolbar-close">
                        <div id="mtt-tag-toolbar-close" class="mtt-img-button"><span></span></div>
                    </div>
                </div>

            </div>


            <h3 class="page-title">
                <span id="taskview" class="mtt-menu-button"><span class="btnstr"><?php Utility::_e('tasks'); ?></span> (<span id="total">0</span>) <span class="arrdown"></span></span>
                <span class="mtt-notes-showhide"><?php Utility::_e('notes'); ?> <a href="#" id="mtt-notes-show"><?php Utility::_e('notes_show'); ?></a> / <a href="#" id="mtt-notes-hide"><?php Utility::_e('notes_hide'); ?></a></span>
                <span id="tagcloudbtn" class="mtt-menu-button"><?php Utility::_e('tagcloud'); ?> <span class="arrdown2"></span></span>
            </h3>

            <div id="tasks_info" style="display:none;">
                <div class="v"></div>
            </div>

            <ol id="tasklist" class="sortable"></ol>

        </div>
        <!-- End of page_tasks -->


        <!-- Page: Edit Task -->
        <div id="page_taskedit" style="display:none">

            <h3 class="page-title mtt-inadd"><a class="mtt-back-button"></a><?php Utility::_e('add_task'); ?></h3>
            <h3 class="page-title mtt-inedit"><a class="mtt-back-button"></a><?php Utility::_e('edit_task'); ?> <span id="taskedit_id"></span></h3>

            <div id="taskedit_info" class="mtt-inedit">
                <div class="date-created"><?php Utility::_e('taskdate_created'); ?>: <span class="date-created-value"></span></div>
                <div class="date-completed"><?php Utility::_e('taskdate_completed'); ?>: <span class="date-completed-value"></span></div>
                <div class="date-edited"><?php Utility::_e('taskdate_edited'); ?>: <span class="date-edited-value"></span></div>
            </div>

            <form id="taskedit_form" name="edittask" method="post">
                <input type="hidden" name="isadd" value="0">
                <input type="hidden" name="id" value="">

                <div class="form-container">
                    <div class="form-row form-row-short">
                        <span class="h"><?php Utility::_e('priority'); ?></span>
                        <select name="prio" class="form-input">
                            <option value="2">+2</option>
                            <option value="1">+1</option>
                            <option value="0" selected="selected">&plusmn;0</option>
                            <option value="-1">&minus;1</option>
                        </select>
                    </div>
                    <div class="form-row form-row-short">
                        <span class="h"><?php Utility::_e('due'); ?> </span>
                        <input name="duedate" id="duedate" value="" class="in100 form-input" title="Y-M-D, M/D/Y, D.M.Y, M/D, D.M" autocomplete="off" type="text">
                    </div>
                    <div class="form-row">
                        <div class="h"><?php Utility::_e('task'); ?></div>
                        <input type="text" name="task" value="" class="inmax form-input" maxlength="250" autocomplete="off">
                    </div>
                    <div class="form-row">
                        <div class="h"><?php Utility::_e('note'); ?></div>
                        <textarea name="note" class="inmax form-input" spellcheck="false"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="h"><?php Utility::_e('tags'); ?></div>
                        <table cellspacing="0" cellpadding="0" width="100%">
                            <tr>
                                <td><input type="text" name="tags" id="edittags" value="" class="inmax form-input" maxlength="250" autocomplete="off"></td>
                                <td class="alltags-cell">
                                    <a href="#" id="alltags_show"><?php Utility::_e('alltags_show'); ?></a>
                                    <a href="#" id="alltags_hide" style="display:none"><?php Utility::_e('alltags_hide'); ?></a>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="form-row" id="alltags" style="display:none;"></div>
                    <div class="form-row form-bottom-buttons">
                        <button type="submit"><?php Utility::_e('save'); ?></button>
                        <button class="mtt-back-button"><?php Utility::_e('cancel'); ?></button>
                    </div>
                </div>
            </form>

        </div>
        <!-- end of page_taskedit -->

        <!-- Page: View Task -->
        <div id="page_taskviewer" style="display:none">
            <h3 class="page-title">
                <a class="mtt-back-button"></a>
                <div><span class="title"></span> <span id="taskviewer_id"></span></div>
            </h3>
            <div class="container">
                <div class="left">
                    <div class="no-note"><?php Utility::_e('no_note'); ?></div>
                    <div class="note markdown-note"></div>
                </div>
                <div class="right">
                    <div class="property prio"><?php Utility::_e('priority'); ?>: <span class="content"></span> </div>
                    <div class="property due"><?php Utility::_e('due'); ?>: <span class="content"></span> </div>
                    <div class="property tags"><?php Utility::_e('tags'); ?>: <span class="content"></span> </div>
                    <div class="property list"><?php Utility::_e('list'); ?>: <span class="content"></span> </div>
                    <div class="form-bottom-buttons">
                        <button id="taskviewer_edit_btn"><?php Utility::_e('edit_task'); ?></button>
                        <button class="mtt-back-button"><?php Utility::_e('close'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <!-- end of page_taskviewer -->


        <div id="page_ajax" style="display:none">
        </div>


        <!-- Page: Login -->
        <div id="page_login" style="display:none">
            <div id="authform">
                <form id="login_form">
                    <div class="auth-content">
                        <div class="h"><?php Utility::_e('password'); ?></div>
                        <div><input type="password" name="password" id="password" class="form-input"></div>
                    </div>
                    <div class="form-bottom-buttons">
                        <button type="submit"><?php Utility::_e('btn_login'); ?></button>
                        <button type="button" class="mtt-back-button"><?php Utility::_e('cancel'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <!-- end of page_login -->


        <div id="priopopup" style="display:none">
            <span class="prio-neg prio-neg-1">&minus;1</span>
            <span class="prio-zero">&plusmn;0</span>
            <span class="prio-pos prio-pos-1">+1</span>
            <span class="prio-pos prio-pos-2">+2</span>
        </div>

        <div id="taskviewcontainer" class="mtt-menu-container" style="display:none">
            <ul>
                <li id="view_tasks"><?php Utility::_e('tasks'); ?> (<span id="cnt_total">0</span>)</li>
                <li id="view_past"><?php Utility::_e('f_past'); ?> (<span id="cnt_past">0</span>)</li>
                <li id="view_today"><?php Utility::_e('f_today'); ?> (<span id="cnt_today">0</span>)</li>
                <li id="view_soon"><?php Utility::_e('f_soon'); ?> (<span id="cnt_soon">0</span>)</li>
            </ul>
        </div>

        <div id="tagcloud" style="display:none">
            <div class="actions">
                <div><input id="tagcloudAllLists" type="checkbox"> <label for="tagcloudAllLists"><?php Utility::_e('showTagsFromAllLists'); ?></label></div>
                <div id="tagcloudcancel" class="mtt-img-button"><span></span></div>
            </div>
            <div class="content">
                <div id="tagcloudload"></div>
                <div id="tagcloudcontent"></div>
            </div>
        </div>


        <div id="listmenucontainer" class="mtt-menu-container" style="display:none">
            <ul>
                <li class="mtt-need-list mtt-need-real-list" id="btnRenameList"><?php Utility::_e('list_rename'); ?></li>
                <li class="mtt-need-list mtt-need-real-list" id="btnDeleteList"><?php Utility::_e('list_delete'); ?></li>
                <li class="mtt-need-list mtt-need-real-list" id="btnClearCompleted"><?php Utility::_e('list_clearcompleted'); ?></li>
                <li class="mtt-need-list" id="btnHideList"><?php Utility::_e('list_hide'); ?></li>
                <li class="mtt-menu-delimiter"></li>
                <li class="mtt-need-list mtt-need-real-list mtt-menu-indicator" submenu="listsharemenucontainer">
                    <div class="submenu-icon"></div><?php Utility::_e('list_share'); ?>
                </li>
                <li class="mtt-menu-delimiter mtt-need-real-list"></li>
                <li class="mtt-need-list mtt-need-real-list sort-item" id="sortByHand">
                    <div class="menu-icon"></div><?php Utility::_e('sortByHand'); ?> <span class="mtt-sort-direction"></span>
                </li>
                <li class="mtt-need-list sort-item" id="sortByDateCreated">
                    <div class="menu-icon"></div><?php Utility::_e('sortByDateCreated'); ?> <span class="mtt-sort-direction"></span>
                </li>
                <li class="mtt-need-list sort-item" id="sortByPrio">
                    <div class="menu-icon"></div><?php Utility::_e('sortByPriority'); ?> <span class="mtt-sort-direction"></span>
                </li>
                <li class="mtt-need-list sort-item" id="sortByDueDate">
                    <div class="menu-icon"></div><?php Utility::_e('sortByDueDate'); ?> <span class="mtt-sort-direction"></span>
                </li>
                <li class="mtt-need-list sort-item" id="sortByDateModified">
                    <div class="menu-icon"></div><?php Utility::_e('sortByDateModified'); ?> <span class="mtt-sort-direction"></span>
                </li>
                <!--<li class="mtt-need-list sort-item" id="sortByTitle"><div class="menu-icon"></div><?php Utility::_e('sortByTitle'); ?> <span class="mtt-sort-direction"></span></li>-->
                <li class="mtt-menu-delimiter"></li>
                <li class="mtt-need-list" id="btnShowCompleted">
                    <div class="menu-icon"></div><?php Utility::_e('list_showcompleted'); ?>
                </li>
            </ul>
        </div>

        <div id="listsharemenucontainer" class="mtt-menu-container" style="display:none">
            <ul>
                <li class="mtt-need-list mtt-need-real-list" id="btnPublish">
                    <div class="menu-icon"></div><?php Utility::_e('list_publish'); ?>
                </li>
                <li class="mtt-need-list mtt-need-real-list" id="btnRssFeed">
                    <div class="menu-icon"></div><a href="#"><?php Utility::_e('list_rssfeed'); ?></a>
                </li>
                <li class="mtt-need-list mtt-need-real-list" id="btnExportCSV"><a href="#"><?php Utility::_e('list_export_to_csv'); ?></a></li>
                <li class="mtt-need-list mtt-need-real-list" id="btnExportICAL"><a href="#"><?php Utility::_e('list_export_to_ical'); ?></a></li>
                <li class="mtt-menu-delimiter"></li>
                <li class="mtt-need-list mtt-need-real-list" id="btnFeedKey">
                    <div class="menu-icon"></div><?php Utility::_e('list_enable_feedkey'); ?>
                </li>
                <li class="mtt-need-list mtt-need-real-list" id="btnShowFeedKey">
                    <div class="menu-icon"></div><?php Utility::_e('list_show_feedkey'); ?>
                </li>
            </ul>
        </div>

        <div id="taskcontextcontainer" class="mtt-menu-container" style="display:none">
            <ul>
                <li id="cmenu_edit"><b><?php Utility::_e('action_edit'); ?></b></li>
                <!--<li id="cmenu_note"><?php Utility::_e('action_note'); ?></li>-->
                <li id="cmenu_prio" class="mtt-menu-indicator" submenu="cmenupriocontainer">
                    <div class="submenu-icon"></div><?php Utility::_e('action_priority'); ?>
                </li>
                <li id="cmenu_move" class="mtt-menu-indicator" submenu="cmenulistscontainer">
                    <div class="submenu-icon"></div><?php Utility::_e('action_move'); ?>
                </li>
                <li id="cmenu_delete"><?php Utility::_e('action_delete'); ?></li>
            </ul>
        </div>

        <div id="cmenupriocontainer" class="mtt-menu-container" style="display:none">
            <ul>
                <li id="cmenu_prio:2">
                    <div class="menu-icon"></div>+2
                </li>
                <li id="cmenu_prio:1">
                    <div class="menu-icon"></div>+1
                </li>
                <li id="cmenu_prio:0">
                    <div class="menu-icon"></div>&plusmn;0
                </li>
                <li id="cmenu_prio:-1">
                    <div class="menu-icon"></div>&minus;1
                </li>
            </ul>
        </div>

        <div id="cmenulistscontainer" class="mtt-menu-container" style="display:none">
            <ul>
            </ul>
        </div>

        <div id="slmenucontainer" class="mtt-menu-container" style="display:none">
            <ul>
                <li id="slmenu_list:-1" class="list-id--1 mtt-only-authorized">
                    <div class="menu-icon"></div><a href="#alltasks"><?php Utility::_e('alltasks'); ?></a>
                </li>
                <li class="mtt-menu-delimiter slmenu-lists-begin mtt-need-list"></li>
            </ul>
        </div>

        <div id="modal" style="display:none">
            <div class="modal-box">
                <div class="modal-content">
                    <div id="modalMessage"></div>
                    <input id="modalTextInput" type="text">
                </div>
                <div class="modal-bottom form-bottom-buttons">
                    <button type="submit" id="btnModalOk"><?php Utility::_e('action_ok'); ?></button>
                    <button id="btnModalCancel"><?php Utility::_e('action_cancel'); ?></button>
                </div>
            </div>
        </div>

    </div><!-- end of #mtt -->

    <div id="footer">
        <div id="footer_content">
            <span><?php Utility::_e('powered_by'); ?> <a href="http://www.mytinytodo.net/" class="powered-by-link">myTinyTodo</a>&nbsp;<?php Utility::mttinfo('version'); ?></span>
            <?php MTTNotificationCenter::doAction('theme_footer_content_end'); ?>
        </div>
    </div>
</body>
</html>