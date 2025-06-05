<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2011,2020-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

declare(strict_types=1);

use App\Utility;
use App\Utility2;
use App\Config\Config;
use App\Core\MTTNotificationCenter;
use App\Core\MTTExtensionLoader;
use App\Core\MTTExtensionSettingsInterface;
use App\Lang\Lang;

header("Content-type: text/html; charset=utf-8");

/* ===================================================================================================================== */

$lang = Lang::instance();

/* ===================================================================================================================== */

if (!Utility::isLogged()) {
    die("Access denied!<br> Disable password protection or Log in.");
}

/* ===================================================================================================================== */

if (isset($_POST['save'])) {
    Utility::checkToken();

    $t = array();
    $langs = getLangs();
    Config::$params['lang']['options'] = array_keys($langs);
    Config::set('lang', Utility2::post('lang'));

    // in Demo mode we can set only language by cookies
    if (defined('MTT_DEMO')) {
        setcookie('lang', Config::get('lang'), 0, Utility2::urlDir(Config::get('url') == '' ? Utility2::getRequestUri() : Config::getUrl('url')));
        $t['saved'] = 1;
        Utility::jsonExit($t);
    }

    if (isset($_POST['password']) && $_POST['password'] != '') {
        Config::set('password', Utility2::passwordHash($_POST['password']));
    } elseif (!Utility2::post('allowpassword')) {
        Config::set('password', '');
    }

    Config::set('smartsyntax', (int)Utility2::post('smartsyntax'));
    // Do not set invalid timezone
    try {
        $tz = trim(Utility2::post('timezone'));
        $testTZ = new DateTimeZone($tz); //will throw Exception on invalid timezone
        Config::set('timezone', $tz);
    } catch (Exception $e) {
    }
    Config::set('autotag', (int)Utility2::post('autotag'));
    Config::set('markup', (int)Utility2::post('markdown') == 0 ? 'v1' : 'markdown');
    Config::set('firstdayofweek', (int)Utility2::post('firstdayofweek'));
    Config::set('clock', (int)Utility2::post('clock'));
    Config::set('dateformat', Utility2::removeNewLines(Utility2::post('dateformat')));
    Config::set('dateformat2', Utility2::removeNewLines(Utility2::post('dateformat2')));
    Config::set('dateformatshort', Utility2::removeNewLines(Utility2::post('dateformatshort')));
    Config::set('title', Utility2::removeNewLines(trim(Utility2::post('title'))));
    Config::set('showdate', (int)Utility2::post('showdate'));
    Config::set('showtime', (int)Utility2::post('showtime'));
    Config::set('showdateInline', (int)Utility2::post('showdateInline'));
    Config::set('exactduedate', (int)Utility2::post('exactduedate'));
    Config::set('appearance', Utility2::removeNewLines(trim(Utility2::post('appearance'))));
    Config::set('newTaskCounter', (int)Utility2::post('newTaskCounter'));
    Config::save();
    $t['saved'] = 1;
    Utility::jsonExit($t);
} else if (isset($_POST['activate'])) {
    Utility::checkToken();

    $t = array('saved' => 0);

    // in Demo mode we do nothing
    if (defined('MTT_DEMO')) {
        $t['saved'] = 1;
        Utility::jsonExit($t);
    }

    $activate = (int)Utility2::post('activate');
    $ext = Utility2::post('ext');

    $extBundles = MTTExtensionLoader::bundles();
    $exts = array_keys($extBundles);
    $a = Config::get('extensions');
    if (!is_array($a)) {
        $a = [];
    }

    if (in_array($ext, $exts)) {
        if ($activate) {
            try {
                MTTExtensionLoader::loadExtension($ext);
                $a[] = $ext;
            } catch (Exception $e) {
                http_response_code(500);
                Utility::logAndDie($e->getMessage());
            }
        } else {
            $a = array_diff($a, [$ext]);
        }

        Config::set('extensions', $a);
        Config::save();
    } else if (!$activate && in_array($ext, $a)) {
        $a = array_diff($a, [$ext]);
        Config::set('extensions', $a);
        Config::save();
    }
    $t['saved'] = 1;
    Utility::jsonExit($t);
}

/* ===================================================================================================================== */

function _c($key)
{
    return Config::get($key);
}

/* ===================================================================================================================== */

function getLangs()
{
    $langDir = Lang::instance()->langDir();
    if (! $h = opendir($langDir)) {
        return false;
    }
    $a = array();
    while (false !== ($file = readdir($h))) {
        if (preg_match('/(.+)\.json$/', $file, $m)) {
            $jsonText = file_get_contents($langDir . $file);
            if (false === $jsonText) {
                die("false ");
                continue;
            }
            $a[$m[1]] = $m[1];

            $j = json_decode($jsonText, true);
            if (isset($j['_header']['language']) && isset($j['_header']['original_name'])) {
                $a[$m[1]] = [
                    'name' => $j['_header']['original_name'],
                    'title' => $j['_header']['language']
                ];
            }
        }
    }
    closedir($h);
    uasort($a, 'cmpLangs');
    return $a;
}

/* ===================================================================================================================== */

function cmpLangs($a, $b): int
{
    //return strcmp( mb_strtoupper($a['name']), mb_strtoupper($b['name']) );
    return strcasecmp($a['title'], $b['title']);
}

/* ===================================================================================================================== */

function selectOptions($a, $value, $default = null)
{
    if (!$a) {
        return '';
    }

    $s = '';
    if ($default !== null && !isset($a[$value])) {
        $value = $default;
    }

    foreach ($a as $k => $v) {
        $s .= '<option value="' . htmlspecialchars((string)$k) . '" ' . ($k === $value ? 'selected="selected"' : '') . '>' . htmlspecialchars($v) . '</option>';
    }
    return $s;
}

/* ===================================================================================================================== */

/**
 * @param array $a             array of id=>array(name, optional title)
 * @param mixed $key           Key of OPTION to be selected
 * @param mixed $default       Default key if $key is not present in $a
 */
function selectOptionsA($a, $key, $default = null)
{
    if (!$a) {
        return '';
    }

    $s = '';

    if ($default !== null && !isset($a[$key])) {
        $key = $default;
    } else if ($default === null && !isset($a[$key])) {
        $s .= '<option hidden disabled selected value></option>';
    }
    foreach ($a as $k => $v) {
        if (!is_array($v)) {
            $v = array('name' => $k);
        }
        $s .= '<option value="' . htmlspecialchars($k) . '" ' . ($k === $key ? 'selected="selected"' : '') .
            (isset($v['title']) ? ' title="' . htmlspecialchars($v['title']) . '"' : '') .
            '>' . htmlspecialchars($v['name']) . '</option>';
    }
    return $s;
}

/* ===================================================================================================================== */

function timezoneIdentifiers()
{
    $zones = DateTimeZone::listIdentifiers();
    $a = array();
    foreach ($zones as $v) {
        $a[$v] = $v;
    }
    return $a;
}

/* ===================================================================================================================== */
/**
 * List all extensions with links to activate/deactivate and settings
 */
function listExtensions()
{
    $extBundles = MTTExtensionLoader::bundles();
    $activatedExts = Config::get('extensions');
    if (!is_array($activatedExts)) {
        $activatedExts = [];
    }

    $a = [];
    foreach ($extBundles as $ext => $meta) {
        $out = htmlspecialchars($meta['name'] . ' v' . $meta['version']) . ' ';
        if (in_array($ext, $activatedExts)) {
            $out .= "<a href='#' data-settings-link='ext-deactivate' data-ext='" . htmlspecialchars((string)$ext) .  "'>" . Utility::__('set_deactivate', true) . '</a>';
            $instance = MTTExtensionLoader::extensionInstance((string)$ext);
            if ($instance instanceof MTTExtensionSettingsInterface) {
                $out .= " <a href='#' data-settings-link='ext-index' data-ext='" . htmlspecialchars((string)$ext) . "'>" . Utility::__('a_settings', true) . "</a>";
            }
            $activatedExts = array_diff($activatedExts, [$ext]);
        } else {
            $out .= "<a href='#' data-settings-link='ext-activate' data-ext='" . htmlspecialchars((string)$ext) . "'>" . Utility::__('set_activate', true) . '</a>';
        }
        $a[] = $out;
    }
    // removed and not deactivated
    foreach ($activatedExts as $ext) {
        $out = "$ext &lt;not found&gt; " . "<a href='#' data-settings-link='ext-deactivate' data-ext='" . htmlspecialchars((string)$ext) . "'>" . Utility::__('set_deactivate', true) . '</a>';
        $a[] = $out;
    }
    print(implode("<br>\n", $a));
}

header('Content-type:text/html; charset=utf-8');
?>

<h3 class="page-title"><a class="mtt-back-button"></a><?php Utility::_e('set_header'); ?></h3>


<?php
if (isset($_GET['json'])) {
    $j = Config::requestDefaultDomain();
    if ($j['password'] != '') {
        $j['password'] = "<not empty>";
    }

    $j = json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
    ?>
    <div class="mtt-settings-table">
        <div class="tr">
            <div class="th"> config.json </div>
            <div class="td"><textarea class="in350"><?php echo htmlspecialchars($j); ?> </textarea></div>
        </div>
    </div>
    <?php
    exit;
}
?>

<div id="settings_msg" style="display:none"></div>

<form id="settings_form" method="post" action="" class="mtt-settings-form">

    <div class="mtt-settings-table">

        <div class="tr">
            <div class="th"> <?php Utility::_e('set_title'); ?>: <div class="descr"><?php Utility::_e('set_title_descr'); ?></div>
            </div>
            <div class="td"> <input name="title" value="<?php echo htmlspecialchars(_c('title')); ?>" class="in350" autocomplete="off" /> </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_language'); ?>:</div>
            <div class="td"> <select name="lang"><?php echo selectOptionsA(getLangs(), _c('lang')); ?></select> </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_protection'); ?>:</div>
            <div class="td">
                <label><input type="radio" name="allowpassword" value="1" <?php echo ((_c('password') != '') ? 'checked="checked"' : ''); ?> onclick='$(this.form).find("input[name=password]").attr("disabled",false)' /> <?php Utility::_e('set_enabled'); ?></label> <br />
                <label><input type="radio" name="allowpassword" value="0" <?php echo ((_c('password') == '') ? 'checked="checked"' : ''); ?> onclick='$(this.form).find("input[name=password]").attr("disabled","disabled")' /> <?php Utility::_e('set_disabled'); ?></label> <br />
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_newpass'); ?>: <div class="descr"><?php Utility::_e('set_newpass_descr'); ?></div>
            </div>
            <div class="td"><input type="password" name="password" autocomplete="new-password" <?php echo ((_c('password') == '') ? "disabled" : ""); ?> /> </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_smartsyntax'); ?>: <div class="descr"><?php Utility::_e('set_smartsyntax3_descr'); ?></div>
            </div>
            <div class="td">
                <label><input type="radio" name="smartsyntax" value="1" <?php echo ((_c('smartsyntax')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_enabled'); ?></label> <br />
                <label><input type="radio" name="smartsyntax" value="0" <?php echo ((!_c('smartsyntax')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_disabled'); ?></label>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_autotag'); ?>: <div class="descr"><?php Utility::_e('set_autotag_descr'); ?></div>
            </div>
            <div class="td">
                <label><input type="radio" name="autotag" value="1" <?php echo ((_c('autotag')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_enabled'); ?></label> <br />
                <label><input type="radio" name="autotag" value="0" <?php echo ((!_c('autotag')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_disabled'); ?></label>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_markdown'); ?>: <div class="descr"><?php Utility::_e('set_markdown_descr'); ?></div>
            </div>
            <div class="td">
                <label><input type="radio" name="markdown" value="1" <?php echo ((_c('markup') != 'v1') ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_enabled'); ?></label> <br />
                <label><input type="radio" name="markdown" value="0" <?php echo ((_c('markup') == 'v1') ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_disabled'); ?></label>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_timezone'); ?>:</div>
            <div class="td">
                <select name="timezone"><?php echo selectOptions(timezoneIdentifiers(), _c('timezone')); ?></select>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_firstdayofweek'); ?>:</div>
            <div class="td">
                <select name="firstdayofweek"><?php echo selectOptions(Utility::__('days_long'), _c('firstdayofweek')); ?></select>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_date'); ?>:</div>
            <div class="td">
                <input name="dateformat" size="8" value="<?php echo htmlspecialchars(_c('dateformat')); ?>" />
                <select onchange="if(this.value!=0) this.form.dateformat.value=this.value;">
                    <?php $ts = strtotime('2023-09-05 09:15:25');
                    echo selectOptions(array(
                        'F j, Y' => Utility::formatTime('F j, Y', $ts),
                        'M j, Y' => Utility::formatTime('M j, Y', $ts),
                        'j M Y'  => Utility::formatTime('j M Y', $ts),
                        'j F Y'  => Utility::formatTime('j F Y', $ts),
                        'n/j/Y'  => Utility::formatTime('n/j/Y', $ts),
                        'd.m.Y'  => Utility::formatTime('d.m.Y', $ts),
                        'j. F Y' => Utility::formatTime('j. F Y', $ts),
                        0 => Utility::__('set_custom')
                    ), _c('dateformat'), 0); ?>
                </select>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_date2'); ?>:</div>
            <div class="td">
                <input name="dateformat2" size="8" value="<?php echo htmlspecialchars(_c('dateformat2')); ?>" />
                <select onchange="if(this.value!=0) this.form.dateformat2.value=this.value;">
                    <?php echo selectOptions(array(
                        'Y-m-d' => 'yyyy-mm-dd (' . date('Y-m-d', $ts) . ')',
                        'n/j/y' => 'm/d/yy (' . date('n/j/y', $ts) . ')',
                        'd.m.y' => 'dd.mm.yy (' . date('d.m.y', $ts) . ')',
                        'd/m/y' => 'dd/mm/yy (' . date('d/m/y', $ts) . ')',
                        0 => Utility::__('set_custom')
                    ), _c('dateformat2'), 0);  ?>
                </select>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_shortdate'); ?>:</div>
            <div class="td">
                <input name="dateformatshort" size="8" value="<?php echo htmlspecialchars(_c('dateformatshort')); ?>" />
                <select onchange="if(this.value!=0) this.form.dateformatshort.value=this.value;">
                    <?php echo selectOptions(array(
                        'M d' => Utility::formatTime('M d', $ts),
                        'j M' => Utility::formatTime('j M', $ts),
                        'n/j' => Utility::formatTime('n/j', $ts),
                        'd.m' => Utility::formatTime('d.m', $ts),
                        0 => Utility::__('set_custom')
                    ), _c('dateformatshort'), 0); ?>
                </select>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_clock'); ?>:</div>
            <div class="td">
                <select name="clock"><?php echo selectOptions(array(
                                            12 => Utility::__('set_12hour') . ' (' . Utility::formatTime('g:i A', $ts) . ')',
                                            24 => Utility::__('set_24hour') . ' (' . Utility::formatTime('H:i', $ts) . ')'
                                        ), _c('clock')); ?>
                </select>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_showdate'); ?>:</div>
            <div class="td">
                <label><input type="radio" name="showdate" value="1" <?php echo ((_c('showdate')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_enabled'); ?></label> <br>
                <label><input type="radio" name="showdate" value="0" <?php echo ((!_c('showdate')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_disabled'); ?></label> <br>
                <label><input type="checkbox" name="showdateInline" value="1" <?php echo ((_c('showdateInline')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_showdate_inline'); ?></label> <br>
                <label><input type="checkbox" name="showtime" value="1" <?php echo ((_c('showtime')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_showtime'); ?></label>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_exactduedate'); ?>:</div>
            <div class="td">
                <label><input type="radio" name="exactduedate" value="1" <?php echo ((_c('exactduedate')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_enabled'); ?></label> <br />
                <label><input type="radio" name="exactduedate" value="0" <?php echo ((!_c('exactduedate')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_disabled'); ?></label>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_appearance'); ?>:</div>
            <div class="td">
                <label><input type="radio" name="appearance" value="system" <?php echo ((_c('appearance') == 'system') ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_appearance_system'); ?></label> <br>
                <label><input type="radio" name="appearance" value="light" <?php echo ((_c('appearance') == 'light') ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_appearance_light'); ?></label> <br>
                <label><input type="radio" name="appearance" value="dark" <?php echo ((_c('appearance') == 'dark') ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_appearance_dark'); ?></label>
            </div>
        </div>

        <div class="tr">
            <div class="th"><?php Utility::_e('set_newtaskcounter_h'); ?>:</div>
            <div class="td"><label><input type="checkbox" name="newTaskCounter" value="1" <?php echo ((_c('newTaskCounter')) ? 'checked="checked"' : ''); ?> /> <?php Utility::_e('set_newtaskcounter'); ?></label></div>
        </div>

        <?php
        if (!defined('MTT_DISABLE_EXT')) {
            ?>
            <div class="tr">
                <div class="th"><?php Utility::_e('set_extensions'); ?>:</div>
                <div class="td extensions"> <?php listExtensions(); ?>
                </div>
            </div>
            <?php
        }
        ?>

        <div class="tr form-bottom-buttons">
            <button type="submit"><?php Utility::_e('set_submit'); ?></button>
            <button type="button" class="mtt-back-button"><?php Utility::_e('set_cancel'); ?></button>
        </div>

    </div>
</form>