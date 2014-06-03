<?php
/**
 * ****************************************************************************
 *  - A Project by Developers TEAM For Xoops - ( http://www.xoops.org )
 * ****************************************************************************
 *  XNEWSLETTER - MODULE FOR XOOPS
 *  Copyright (c) 2007 - 2012
 *  Goffy ( wedega.com )
 *
 *  You may not change or alter any portion of this comment or credits
 *  of supporting developers from this source code or any supporting
 *  source code which is considered copyrighted (c) material of the
 *  original comment or credit authors.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  ---------------------------------------------------------------------------
 *  @copyright  Goffy ( wedega.com )
 *  @license    GPL 2.0
 *  @package    xnewsletter
 *  @author     Goffy ( webmaster@wedega.com )
 *
 *  Version : $Id: letter.php 12559 2014-06-02 08:10:39Z beckmi $
 * ****************************************************************************
 */

$currentFile = basename(__FILE__);
include_once "header.php";

$uid = (is_object($xoopsUser) && isset($xoopsUser)) ? $xoopsUser->uid() : 0;
$groups = is_object($xoopsUser) ? $xoopsUser->getGroups() : array(0 => XOOPS_GROUP_ANONYMOUS);

$op = xnewsletter_CleanVars($_REQUEST, 'op', 'list_letters', 'string');
$letter_id  = xnewsletter_CleanVars($_REQUEST, 'letter_id', 0, 'int');
$cat_id = xnewsletter_CleanVars($_REQUEST, 'cat_id', 0, 'int');

//check the rights of current user first
if (!xnewsletter_userAllowedCreateCat()) redirect_header("index.php", 3, _NOPERM);

$delete_att_1 = xnewsletter_CleanVars($_REQUEST, 'delete_attachment_1', 'none', 'string');
$delete_att_2 = xnewsletter_CleanVars($_REQUEST, 'delete_attachment_2', 'none', 'string');
$delete_att_3 = xnewsletter_CleanVars($_REQUEST, 'delete_attachment_3', 'none', 'string');
$delete_att_4 = xnewsletter_CleanVars($_REQUEST, 'delete_attachment_4', 'none', 'string');
$delete_att_5 = xnewsletter_CleanVars($_REQUEST, 'delete_attachment_5', 'none', 'string');

if ($delete_att_1 != 'none') {
    $op = "delete_attachment";
    $id_del = 1;
} elseif ($delete_att_2 != 'none') {
    $op = "delete_attachment";
    $id_del = 2;
} elseif ($delete_att_3 != 'none') {
    $op = "delete_attachment";
    $id_del = 3;
} elseif ($delete_att_4 != 'none') {
    $op = "delete_attachment";
    $id_del = 4;
} elseif ($delete_att_5 != 'none') {
    $op = "delete_attachment";
    $id_del = 5;
} else {
    $id_del = 0;
}

switch ($op) {
    case "list_subscrs" :
        $xoopsOption['template_main'] = 'xnewsletter_letter_list_subscrs.tpl';
        include_once XOOPS_ROOT_PATH . "/header.php";

        $xoTheme->addStylesheet(XNEWSLETTER_URL . '/assets/css/module.css');
        $xoTheme->addMeta('meta', 'keywords', $xnewsletter->getConfig('keywords')); // keywords only for index page
        $xoTheme->addMeta('meta', 'description', strip_tags(_MA_XNEWSLETTER_DESC)); // description

        // Breadcrumb
        $breadcrumb = new xnewsletterBreadcrumb();
        $breadcrumb->addLink($xnewsletter->getModule()->getVar('name'), XNEWSLETTER_URL);
        $breadcrumb->addLink(_MD_XNEWSLETTER_LIST_SUBSCR, '');
        $xoopsTpl->assign('xnewsletter_breadcrumb', $breadcrumb->render());

        // check right to edit/delete subscription of other persons
        $permissionChangeOthersSubscriptions = false;
        foreach ($groups as $group) {
            if (in_array($group, $xnewsletter->getConfig('xn_groups_change_other')) || XOOPS_GROUP_ADMIN == $group) {
                $permissionChangeOthersSubscriptions = true;
                break;
            }
        }
        $xoopsTpl->assign('permissionChangeOthersSubscriptions', $permissionChangeOthersSubscriptions);
        // get search subscriber form
        if ($permissionChangeOthersSubscriptions) {
            include_once(XOOPS_ROOT_PATH . "/class/xoopsformloader.php");
            $form = new XoopsThemeForm(_AM_XNEWSLETTER_FORMSEARCH_SUBSCR_EXIST, 'form_search', 'subscription.php', 'post', true);
            $form->setExtra('enctype="multipart/form-data"');
            $form->addElement(new XoopsFormText(_AM_XNEWSLETTER_SUBSCR_EMAIL, 'subscr_email', 60, 255, '', true));
            $form->addElement(new XoopsFormButton('', 'submit', _AM_XNEWSLETTER_SEARCH, 'submit'));
            $xoopsTpl->assign('searchSubscriberForm', $form->render());
        } else {
            $xoopsTpl->assign('searchSubscriberForm', '');
        }
        // get cat objects
        $criteria_cats = new CriteriaCompo();
        $criteria_cats->setSort('cat_id');
        $criteria_cats->setOrder('ASC');
        $catObjs = $xnewsletter->getHandler('xnewsletter_cat')->getAll($criteria_cats, null, true, true);
        // cats table
        foreach ($catObjs as $cat_id => $catObj) {
            $permissionShowCats[$cat_id] = $gperm_handler->checkRight('newsletter_list_cat', $cat_id, $groups, $xnewsletter->getModule()->mid());
            if ($permissionShowCats[$cat_id] == true) {
                $cat_array = $catObj->toArray();
                $criteria_catsubscrs = new CriteriaCompo();
                $criteria_catsubscrs->add(new Criteria("catsubscr_catid", $cat_id));
                $cat_array['catsubscrCount'] = $xnewsletter->getHandler('xnewsletter_catsubscr')->getCount($criteria_catsubscrs);
                $xoopsTpl->append('cats', $cat_array);
            }
        }
        // get cat_id
        $cat_id = xnewsletter_CleanVars($_REQUEST, 'cat_id', 0, 'int');
        $xoopsTpl->assign('cat_id', $cat_id);
        if ($cat_id > 0) {
            $catObj = $xnewsletter->getHandler('xnewsletter_cat')->get($cat_id);
            // subscrs table
            if ($permissionShowCats[$cat_id] == true) {
                $counter = 1;
                $sql ="SELECT `subscr_sex`, `subscr_lastname`, `subscr_firstname`, `subscr_email`, `subscr_id`";
                $sql.= " FROM {$xoopsDB->prefix("xnewsletter_subscr")} INNER JOIN {$xoopsDB->prefix("xnewsletter_catsubscr")} ON `subscr_id` = `catsubscr_subscrid`";
                $sql.= " WHERE (((`catsubscr_catid`)={$cat_id}) AND ((`catsubscr_quited`)=0)) ORDER BY `subscr_lastname`, `subscr_email`;";
                $subscrs = $xoopsDB->query($sql) || die ("MySQL-Error: " . mysql_error());
                while ($subscr_array = mysql_fetch_assoc($subscrs)) {
                    $subscr_array['counter'] = ++$counter;
                    $xoopsTpl->append('subscrs', $subscr_array);
                }
            }
        }
        break;

    case "delete_attachment" :
//$xoopsOption['template_main'] = 'xnewsletter_letter.tpl'; // IN PROGRESS
        include_once XOOPS_ROOT_PATH . "/header.php";

        $xoTheme->addStylesheet(XNEWSLETTER_URL . '/assets/css/module.css');
        $xoTheme->addMeta('meta', 'keywords', $xnewsletter->getConfig('keywords')); // keywords only for index page
        $xoTheme->addMeta('meta', 'description', strip_tags(_MA_XNEWSLETTER_DESC)); // description

        // Breadcrumb
        $breadcrumb = new xnewsletterBreadcrumb();
        $breadcrumb->addLink($xnewsletter->getModule()->getVar('name'), XNEWSLETTER_URL);
        $xoopsTpl->assign('xnewsletter_breadcrumb', $breadcrumb->render());

// IN PROGRESS FROM HERE
        // get attachment
        $attachment_id = xnewsletter_CleanVars($_REQUEST, 'attachment_' . $id_del, 'none', 'string');
        if ($attachment_id == 'none') {
            redirect_header($currentFile, 3, _AM_XNEWSLETTER_LETTER_ERROR_INVALID_ATT_ID);
        }
        $attachmentObj = $xnewsletter->getHandler('xnewsletter_attachment')->get($attachment_id);
        $attachment_name = $attachmentObj->getVar("attachment_name");
        // delete attachment
        if ($xnewsletter->getHandler('xnewsletter_attachment')->delete($attachmentObj, true)) {
            // delete file
            $uploadDir = XOOPS_UPLOAD_PATH . $xnewsletter->getConfig('xn_attachment_path') . $letter_id;
            if (file_exists($uploadDir . '/' . $attachment_name)) {
                unlink($uploadDir . '/' . $attachment_name);
            }
            // get letter
            $letterObj =& $xnewsletter->getHandler('xnewsletter_letter')->get($letter_id);
            $letterObj->setVar("letter_title", $_REQUEST['letter_title']);
            $letterObj->setVar("letter_content", $_REQUEST['letter_content']);
            $letterObj->setVar("letter_template", $_REQUEST['letter_template']);
// IN PROGRESS
// IN PROGRESS
// IN PROGRESS
            //Form letter_cats
            $letter_cats = '';
            //$cat_arr = isset($_REQUEST["letter_cats"]) ? $_REQUEST["letter_cats"] : "";
            $cats_arr = xnewsletter_CleanVars( $_REQUEST, 'letter_cats', '', 'array');
            if (is_array($cats_arr) && count($cats_arr) > 0) {
                foreach ($cats_arr as $cat) {
                    $letter_cats .= $cat . '|';
                }
                $letter_cats = substr($letter_cats, 0, -1);
            } else {
                $letter_cats = $cats_arr;
            }
            //no cat
            if ($letter_cats == false) {
                $form = $obj_letter->getForm();
                $content = $form->render();
                $xoopsTpl->assign('content', $content);
                break;
            }
            $letterObj->setVar("letter_cats", $letter_cats);
// IN PROGRESS
// IN PROGRESS
// IN PROGRESS
            $letterObj->setVar("letter_account", $_REQUEST['letter_account']);
            $letterObj->setVar("letter_email_test", $_REQUEST['letter_email_test']);
            // get letter form
            $form = $letterObj->getForm(false, true);
            $form->display();
        } else {
            echo $attachmentObj->getHtmlErrors();
        }
        break;

    case "show_preview" :
    case "show_letter_preview" :
        $xoopsOption['template_main'] = 'xnewsletter_letter_preview.tpl';
        include XOOPS_ROOT_PATH . "/header.php";

        $xoTheme->addStylesheet(XNEWSLETTER_URL . '/assets/css/module.css');
        $xoTheme->addMeta('meta', 'keywords', $xnewsletter->getConfig('keywords')); // keywords only for index page
        $xoTheme->addMeta('meta', 'description', strip_tags(_MA_XNEWSLETTER_DESC)); // description

        // Breadcrumb
        $breadcrumb = new xnewsletterBreadcrumb();
        $breadcrumb->addLink($xnewsletter->getModule()->getVar('name'), XNEWSLETTER_URL);
        $breadcrumb->addLink(_MD_XNEWSLETTER_LIST, XNEWSLETTER_URL . '/letter.php?op=list_letters');
        $breadcrumb->addLink(_MD_XNEWSLETTER_LETTER_PREVIEW, '');
        $xoopsTpl->assign('xnewsletter_breadcrumb', $breadcrumb->render());

        // get letter templates path
        $letterTemplatePath = XNEWSLETTER_ROOT_PATH . '/language/' . $GLOBALS['xoopsConfig']['language'] . '/templates/';
        if (!is_dir($letterTemplatePath)) {
            $letterTemplatePath = XNEWSLETTER_ROOT_PATH . '/language/english/templates/';
        }
        // get letter_id
        $letter_id = xnewsletter_CleanVars($_REQUEST, 'letter_id', 0, 'int');
        // get letter object
        $letterObj =& $xnewsletter->getHandler('xnewsletter_letter')->get($letter_id);
        $letterTemplate = "{$letterTemplatePath}{$letterObj->getVar('letter_template')}.tpl";

        $xoopsTpl->assign('sex', _AM_XNEWSLETTER_SUBSCR_SEX_MALE);
        $xoopsTpl->assign('firstname', _AM_XNEWSLETTER_SUBSCR_FIRSTNAME);
        $xoopsTpl->assign('lastname', _AM_XNEWSLETTER_SUBSCR_LASTNAME);
        $xoopsTpl->assign('title', $letterObj->getVar('letter_title', 'n')); // new from v1.3
        $xoopsTpl->assign('content', $letterObj->getVar('letter_content', 'n'));
        $xoopsTpl->assign('unsubscribe_url', XOOPS_URL . '/modules/xnewsletter/');
        $xoopsTpl->assign('catsubscr_id', '0');

        $letter_array = $letterObj->toArray();
        $letter_array['letter_content_templated'] = $xoopsTpl->fetch($letterTemplate);
        $letter_array['letter_created_timestamp'] = formatTimestamp($letterObj->getVar('letter_created'), $xnewsletter->getConfig('dateformat'));
        $letter_array['letter_submitter_name'] = XoopsUserUtility::getUnameFromId($letterObj->getVar('letter_submitter'));
        $xoopsTpl->assign('letter', $letter_array);
        break;

    case "list" :
exit("IN_PROGRESS: use op=list_letters instead of op=list");
break;
    case "list_letters" :
    default :
        $xoopsOption['template_main'] = 'xnewsletter_letter_list_letters.tpl';
        include_once XOOPS_ROOT_PATH . "/header.php";

        $xoTheme->addStylesheet(XNEWSLETTER_URL . '/assets/css/module.css');
        $xoTheme->addMeta('meta', 'keywords', $xnewsletter->getConfig('keywords')); // keywords only for index page
        $xoTheme->addMeta('meta', 'description', strip_tags(_MA_XNEWSLETTER_DESC)); // description

        // Breadcrumb
        $breadcrumb = new xnewsletterBreadcrumb();
        $breadcrumb->addLink($xnewsletter->getModule()->getVar('name'), XNEWSLETTER_URL);
        $breadcrumb->addLink(_MD_XNEWSLETTER_LIST, '');
        $xoopsTpl->assign('xnewsletter_breadcrumb', $breadcrumb->render());

        // get letters array
        $criteria_letters = new CriteriaCompo();
        $criteria_letters->setSort("letter_id");
        $criteria_letters->setOrder("DESC");
        $letterCount = $xnewsletter->getHandler('xnewsletter_letter')->getCount();
        $start = xnewsletter_CleanVars($_REQUEST, 'start', 0, 'int');
        $limit = $xnewsletter->getConfig('adminperpage');
        $criteria_letters->setStart($start);
        $criteria_letters->setLimit($limit);
        if ($letterCount > $limit) {
            include_once XOOPS_ROOT_PATH . "/class/pagenav.php";
            $pagenav = new XoopsPageNav($letterCount, $limit, $start, 'start', "op={$op}");
            $pagenav = $pagenav->renderNav(4);
        } else {
            $pagenav = '';
        }
        $xoopsTpl->assign('pagenav', $pagenav);
        $letterObjs = $xnewsletter->getHandler('xnewsletter_letter')->getAll($criteria_letters, null, true, true);
        // letters table
        if ($letterCount> 0) {
            foreach ($letterObjs as $letter_id => $letterObj) {
                $userPermissions = array();
                $userPermissions = xnewsletter_getUserPermissionsByLetter($letter_id);
                if ($userPermissions["read"]) {
                    $letter_array = $letterObj->toArray();
                    $letter_array['letter_created_timestamp'] = formatTimestamp($letterObj->getVar('letter_created'), $xnewsletter->getConfig('dateformat'));
                    $letter_array['letter_submitter_name'] = XoopsUserUtility::getUnameFromId($letterObj->getVar('letter_submitter'));
                    // get categories
                    $catsAvailableCount = 0;
                    $cats_string = '';
                    $cat_ids = explode('|' , $letterObj->getVar('letter_cats'));
                    unset($letter_array['letter_cats']); // IN PROGRESS
                    foreach ($cat_ids as $cat_id) {
                        $catObj = $xnewsletter->getHandler('xnewsletter_cat')->get($cat_id);
                        if ($gperm_handler->checkRight('newsletter_read_cat', $catObj->getVar('cat_id'), $groups, $xnewsletter->getModule()->mid())) {
                            ++$catsAvailableCount;
                            $letter_array['letter_cats'][] = $catObj->toArray();
                        }
                        unset($catObj);
                    }
                    if ($catsAvailableCount > 0) {
                        $letters_array[] = $letter_array;
                    }
                    // count letter attachements
                    $criteria_attachments = new CriteriaCompo();
                    $criteria_attachments->add(new Criteria('attachment_letter_id', $letterObj->getVar("letter_id")));
                    $letter_array['attachmentCount'] = $xnewsletter->getHandler('xnewsletter_attachment')->getCount($criteria_attachments);
                    // get protocols
                    if ($userPermissions["edit"]) {
                        // take last item protocol_subscriber_id=0 from table protocol as actual status
                        $criteria_protocols = new CriteriaCompo();
                        $criteria_protocols->add(new Criteria('protocol_letter_id', $letterObj->getVar("letter_id")));
                        //$criteria->add(new Criteria('protocol_subscriber_id', '0'));
                        $criteria_protocols->setSort("protocol_id");
                        $criteria_protocols->setOrder("DESC");
                        $criteria_protocols->setLimit(1);
                        $protocolObjs = $xnewsletter->getHandler('xnewsletter_protocol')->getAll($criteria_protocols);
                        $protocol_status = "";
                        $protocol_letter_id = 0;
                        foreach ($protocolObjs as $protocolObj) {
                            $letter_array['protocols'][] = array(
                                'protocol_status' => $protocolObj->getVar("protocol_status"),
                                'protocol_letter_id' => $protocolObj->getVar("protocol_letter_id")
                                );
                        }
                    }

                    $letter_array['userPermissions'] = $userPermissions;
                    $xoopsTpl->append('letters', $letter_array);
                }
            }
        } else {
            // NOP
        }
        break;

    case "new_letter" :
$xoopsOption['template_main'] = 'xnewsletter_letter.tpl'; // IN PROGRESS
        include_once XOOPS_ROOT_PATH . "/header.php";

        $xoTheme->addStylesheet(XNEWSLETTER_URL . '/assets/css/module.css');
        $xoTheme->addMeta('meta', 'keywords', $xnewsletter->getConfig('keywords')); // keywords only for index page
        $xoTheme->addMeta('meta', 'description', strip_tags(_MA_XNEWSLETTER_DESC)); // description

        // Breadcrumb
        $breadcrumb = new xnewsletterBreadcrumb();
        $breadcrumb->addLink($xnewsletter->getModule()->getVar('name'), XNEWSLETTER_URL);
        $breadcrumb->addLink(_MD_XNEWSLETTER_LETTER_CREATE, '');
        $xoopsTpl->assign('xnewsletter_breadcrumb', $breadcrumb->render());

// IN PROGRESS FROM HERE
        $letterObj =& $xnewsletter->getHandler('xnewsletter_letter')->create();
        $form = $letterObj->getForm();
        $content = $form->render();
        $xoopsTpl->assign('content', $content);
        break;

    case "copy_letter":
    case "clone_letter":
$xoopsOption['template_main'] = 'xnewsletter_letter.tpl'; // IN PROGRESS
        include_once XOOPS_ROOT_PATH . "/header.php";

        $xoTheme->addStylesheet(XNEWSLETTER_URL . '/assets/css/module.css');
        $xoTheme->addMeta('meta', 'keywords', $xnewsletter->getConfig('keywords')); // keywords only for index page
        $xoTheme->addMeta('meta', 'description', strip_tags(_MA_XNEWSLETTER_DESC)); // description

        // Breadcrumb
        $breadcrumb = new xnewsletterBreadcrumb();
        $breadcrumb->addLink($xnewsletter->getModule()->getVar('name'), XNEWSLETTER_URL);
        $breadcrumb->addLink(_MD_XNEWSLETTER_LIST, XNEWSLETTER_URL . '/letter.php?op=list_letters');
        $breadcrumb->addLink(_MD_XNEWSLETTER_LETTER_COPY, '');
        $xoopsTpl->assign('xnewsletter_breadcrumb', $breadcrumb->render());

// IN PROGRESS FROM HERE

        $obj_letter_old =& $xnewsletter->getHandler('xnewsletter_letter')->get($letter_id);
        $obj_letter_new =& $xnewsletter->getHandler('xnewsletter_letter')->create();

        $obj_letter_new->setVar("letter_title", $obj_letter_old->getVar("letter_title"));
        $obj_letter_new->setVar("letter_content", $obj_letter_old->getVar("letter_content","n"));
        $obj_letter_new->setVar("letter_template", $obj_letter_old->getVar("letter_template"));
        $obj_letter_new->setVar("letter_cats", $obj_letter_old->getVar("letter_cats"));
        $obj_letter_new->setVar("letter_account", $obj_letter_old->getVar("letter_account"));
        $obj_letter_new->setVar("letter_email_test", $obj_letter_old->getVar("letter_email_test"));
        unset($obj_letter_old);
        $action = XOOPS_URL . "/modules/xnewsletter/{$currentFile}?op=copy_letter";
        $form = $obj_letter_new->getForm($action);
        $content = $form->render();
        $xoopsTpl->assign('content', $content);
        break;

    case "save_letter" :
$xoopsOption['template_main'] = 'xnewsletter_letter.tpl'; // IN PROGRESS
        include_once XOOPS_ROOT_PATH . "/header.php";

        $xoTheme->addStylesheet(XNEWSLETTER_URL . '/assets/css/module.css');
        $xoTheme->addMeta('meta', 'keywords', $xnewsletter->getConfig('keywords')); // keywords only for index page
        $xoTheme->addMeta('meta', 'description', strip_tags(_MA_XNEWSLETTER_DESC)); // description

        // Breadcrumb
        $breadcrumb = new xnewsletterBreadcrumb();
        $breadcrumb->addLink($xnewsletter->getModule()->getVar('name'), XNEWSLETTER_URL);
        $xoopsTpl->assign('xnewsletter_breadcrumb', $breadcrumb->render());

// IN PROGRESS FROM HERE

        if ( !$GLOBALS["xoopsSecurity"]->check() ) {
            redirect_header($currentFile, 3, implode(",", $GLOBALS["xoopsSecurity"]->getErrors()));
        }
        $obj_letter =& $xnewsletter->getHandler('xnewsletter_letter')->get($letter_id);

        //Form letter_title
        $obj_letter->setVar("letter_title", $_REQUEST['letter_title']);
        //Form letter_content
        $obj_letter->setVar("letter_content", $_REQUEST['letter_content']);
        //Form letter_template
        $obj_letter->setVar("letter_template", $_REQUEST['letter_template']);
        //Form letter_cats
        $letter_cats = "";
        //$cat_arr = isset($_REQUEST["letter_cats"]) ? $_REQUEST["letter_cats"] : "";
        $cat_arr = xnewsletter_CleanVars( $_REQUEST, 'letter_cats', '', 'array');
        if (is_array($cat_arr) && count($cat_arr) > 0) {
            foreach ($cat_arr as $cat) {
                $letter_cats .= $cat . '|';
            }
            $letter_cats = substr($letter_cats, 0, -1);
        } else {
            $letter_cats = $cat_arr;
        }
        //no cat
        if ($letter_cats == false) {
            $form = $obj_letter->getForm();
            $content = $form->render();
            $xoopsTpl->assign('content', $content);
            break;
        }

        $obj_letter->setVar("letter_cats", $letter_cats);

        // Form letter_account
        $obj_letter->setVar("letter_account", $_REQUEST["letter_account"]);
        // Form letter_email_test
        $obj_letter->setVar("letter_email_test", $_REQUEST["letter_email_test"]);
        // Form letter_submitter
        $obj_letter->setVar("letter_submitter", xnewsletter_CleanVars($_REQUEST, "letter_submitter", 0, 'int'));
        // Form letter_created
        $obj_letter->setVar("letter_created", xnewsletter_CleanVars($_REQUEST, "letter_created", 0, 'int'));
        if ($xnewsletter->getHandler('xnewsletter_letter')->insert($obj_letter)) {
            $letter_id = $obj_letter->getVar("letter_id");

            //upload attachments
            $uploaded_files = array();
            include_once XOOPS_ROOT_PATH . "/class/uploader.php";
            $uploaddir = XOOPS_UPLOAD_PATH . $xnewsletter->getConfig('xn_attachment_path') . $letter_id . '/';
            if (!is_dir($uploaddir)) {
                $indexFile = XOOPS_UPLOAD_PATH . "/index.html";
                mkdir($uploaddir, 0777);
                chmod($uploaddir, 0777);
                copy($indexFile, $uploaddir . "index.html");
            }
            $uploader = new XoopsMediaUploader($uploaddir, $xnewsletter->getConfig('xn_mimetypes'), $xnewsletter->getConfig('xn_maxsize'), null, null);
            for ($upl = 0 ;$upl < 5; ++$upl) {
                if ($uploader->fetchMedia($_POST['xoops_upload_file'][$upl])) {
                    //$uploader->setPrefix("xn_") ; keep original name
                    $uploader->fetchMedia($_POST['xoops_upload_file'][$upl]);
                    if (!$uploader->upload()) {
                        $errors = $uploader->getErrors();
                        redirect_header("javascript:history.go(-1)", 3, $errors);
                    } else {
                        $uploaded_files[] = array("name" => $uploader->getSavedFileName(), "origname" => $uploader->getMediaType());
                    }
                }
            }

            // create items in attachments
            foreach ($uploaded_files as $file) {
                $attachmentObj =& $xnewsletter->getHandler('xnewsletter_attachment')->create();
                //Form attachment_letter_id
                $attachmentObj->setVar("attachment_letter_id", $letter_id);
                //Form attachment_name
                $attachmentObj->setVar("attachment_name", $file["name"]);
                //Form attachment_type
                $attachmentObj->setVar("attachment_type", $file["origname"]);
                //Form attachment_submitter
                $attachmentObj->setVar("attachment_submitter", $xoopsUser->uid());
                //Form attachment_created
                $attachmentObj->setVar("attachment_created", time());
                $xnewsletter->getHandler('xnewsletter_attachment')->insert($attachmentObj);
            }
            //create item in protocol
            $obj_protocol =& $xnewsletter->getHandler('xnewsletter_protocol')->create();
            $obj_protocol->setVar("protocol_letter_id", $letter_id);
            $obj_protocol->setVar("protocol_subscriber_id", '0');
            $obj_protocol->setVar("protocol_success", '1');
            $action = "";
            //$action = isset($_REQUEST["letter_action"]) ? $_REQUEST["letter_action"] : 0;
            $action = xnewsletter_CleanVars($_REQUEST, "letter_action", 0, 'int');
            switch ($action) {
                case _AM_XNEWSLETTER_LETTER_ACTION_VAL_PREVIEW :
                    $url = "{$currentFile}?op=show_preview&letter_id={$letter_id}";
                    break;
                case _AM_XNEWSLETTER_LETTER_ACTION_VAL_SEND :
                    $url = "sendletter.php?op=send_letter&letter_id={$letter_id}";
                    break;
                case _AM_XNEWSLETTER_LETTER_ACTION_VAL_SENDTEST :
                    $url = "sendletter.php?op=send_test&letter_id={$letter_id}";
                    break;
                default:
                    $url = "{$currentFile}?op=list_letters";
                    break;
            }
            $obj_protocol->setVar("protocol_status", _AM_XNEWSLETTER_LETTER_ACTION_SAVED);
            $obj_protocol->setVar("protocol_submitter", $xoopsUser->uid());
            $obj_protocol->setVar("protocol_created", time());

            if ($xnewsletter->getHandler('xnewsletter_protocol')->insert($obj_protocol)) {
                // create protocol is ok
                redirect_header($url, 3, _AM_XNEWSLETTER_FORMOK);
            }
        } else {
            echo "Error create protocol: " . $obj_protocol->getHtmlErrors();
        }
        break;

    case "edit_letter" :
$xoopsOption['template_main'] = 'xnewsletter_letter.tpl'; // IN PROGRESS
        include_once XOOPS_ROOT_PATH . "/header.php";

        $xoTheme->addStylesheet(XNEWSLETTER_URL . '/assets/css/module.css');
        $xoTheme->addMeta('meta', 'keywords', $xnewsletter->getConfig('keywords')); // keywords only for index page
        $xoTheme->addMeta('meta', 'description', strip_tags(_MA_XNEWSLETTER_DESC)); // description

        // Breadcrumb
        $breadcrumb = new xnewsletterBreadcrumb();
        $breadcrumb->addLink($xnewsletter->getModule()->getVar('name'), XNEWSLETTER_URL);
        $breadcrumb->addLink(_MD_XNEWSLETTER_LIST, XNEWSLETTER_URL . '/letter.php?op=list_letters');
        $breadcrumb->addLink(_MD_XNEWSLETTER_LETTER_EDIT, '');
        $xoopsTpl->assign('xnewsletter_breadcrumb', $breadcrumb->render());

// IN PROGRESS FROM HERE

        $letterObj = $xnewsletter->getHandler('xnewsletter_letter')->get($letter_id);
        $form = $letterObj->getForm();
        $content = $form->render();
        $xoopsTpl->assign('content', $content);
        break;

    case "delete_letter":
$xoopsOption['template_main'] = 'xnewsletter_letter.tpl'; // IN PROGRESS
        include_once XOOPS_ROOT_PATH . "/header.php";

        $xoTheme->addStylesheet(XNEWSLETTER_URL . '/assets/css/module.css');
        $xoTheme->addMeta('meta', 'keywords', $xnewsletter->getConfig('keywords')); // keywords only for index page
        $xoTheme->addMeta('meta', 'description', strip_tags(_MA_XNEWSLETTER_DESC)); // description

        // Breadcrumb
        $breadcrumb = new xnewsletterBreadcrumb();
        $breadcrumb->addLink($xnewsletter->getModule()->getVar('name'), XNEWSLETTER_URL);
        $breadcrumb->addLink(_MD_XNEWSLETTER_LIST, XNEWSLETTER_URL . '/letter.php?op=list_letters');
        $breadcrumb->addLink(_MD_XNEWSLETTER_LETTER_DELETE, '');
        $xoopsTpl->assign('xnewsletter_breadcrumb', $breadcrumb->render());

// IN PROGRESS FROM HERE

        $obj_letter =& $xnewsletter->getHandler('xnewsletter_letter')->get($letter_id);
        if (isset($_REQUEST["ok"]) && $_REQUEST["ok"] == 1) {
            if ( !$GLOBALS["xoopsSecurity"]->check() ) {
                redirect_header($currentFile, 3, implode(",", $GLOBALS["xoopsSecurity"]->getErrors()));
            }

            if ($xnewsletter->getHandler('xnewsletter_letter')->delete($obj_letter)) {
                // delete protocol
                $sql = "DELETE FROM `{$xoopsDB->prefix("xnewsletter_protocol")}`";
                $sql.= " WHERE `protocol_letter_id`={$letter_id}";
                $result = $xoopsDB->query($sql) || die("MySQL-Error: " . mysql_error());

                // delete attachments
                $crit_att = new CriteriaCompo();
                $crit_att->add(new Criteria("attachment_letter_id", $letter_id));
                $attachment_arr = $xnewsletter->getHandler('xnewsletter_attachment')->getall($crit_att);
                foreach (array_keys($attachment_arr) as $attachment_id) {
                    $attachmentObj = $xnewsletter->getHandler('xnewsletter_attachment')->get($attachment_id);
                    $attachment_name = $attachmentObj->getVar("attachment_name");
                    $xnewsletter->getHandler('xnewsletter_attachment')->delete($attachmentObj, true);
                    // delete file
                    $uploaddir = XOOPS_UPLOAD_PATH . $xnewsletter->getConfig('xn_attachment_path') . $letter_id . "/";
                    unlink($uploaddir . $attachment_name);
                }
                redirect_header($currentFile, 3, _AM_XNEWSLETTER_FORMDELOK);
            } else {
                echo $obj_letter->getHtmlErrors();
            }
        } else {
            xoops_confirm(array("ok" => 1, "letter_id" => $letter_id, "op" => "delete_letter"), $_SERVER["REQUEST_URI"], sprintf(_AM_XNEWSLETTER_FORMSUREDEL, $obj_letter->getVar("letter_title")));
        }
        break;
}

include 'footer.php';