/**
 * @file     modules/module/js/module_admin.js
 * @author Arnia (dev@karybu.org)
 * @brief    module 모듈의 관리자용 javascript
 **/

/* Categories related tasks */
function doUpdateCategory(module_category_srl, message) {
    if(typeof(message)!='undefined'&&!confirm(message)) return;

    var fo_obj = get_by_id('fo_category_info');
    fo_obj.module_category_srl.value = module_category_srl;
	fo_obj.submit();
}

/* Revised Category */
function completeUpdateCategory(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    alert(message);

    location.href =  current_url.setQuery('module_category_srl','');
}

/* The shortcut menu for the selected module manager registered in */
function doAddShortCut(module) {
    var fo_obj = get_by_id("fo_shortcut");
    fo_obj.selected_module.value = module;
    procFilter(fo_obj, insert_shortcut);
}

/* Module */
function doInstallModule(module) {
    var params = new Array();
    params['module_name'] = module;
    exec_xml('install','procInstallAdminInstall',params, completeInstallModule);
}

function completeInstallModule(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

/* Module Upgrade */
function doUpdateModule(module) {
    var params = new Array();
    params['module_name'] = module;
    exec_xml('install','procInstallAdminUpdate',params, completeInstallModule);
}

/* Modules after copying */
function completeCopyModule() {
    if(typeof(opener)!='undefined') opener.location.href = opener.location.href;
    window.close();
}

/* Selected modules from the module's input selector */
function insertModule(id, module_srl, mid, browser_title, multi_select) {
    if(typeof(multi_select)=='undefined') multi_select = true;
    if(!window.opener) {
        window.close();
    }

    if(multi_select) {
        if(typeof(opener.insertSelectedModules)=='undefined') return;
        opener.insertSelectedModules(id, module_srl, mid, browser_title);
    } else {
        if(typeof(opener.insertSelectedModule)=='undefined') return;
        opener.insertSelectedModule(id, module_srl, mid, browser_title);
        window.close();
    }
}

/* Authorization for selecting */
function doShowGrantZone() {
    jQuery(".grant_default").each( function() {
        var id = "#zone_"+this.name.replace(/_default$/,'');
        if(!jQuery(this).val()) jQuery(id).css("display","block");
        else jQuery(id).css("display","none");
    } );
}

/* After registration rights notices */
function completeInsertGrant(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

/* The administrator user ID registration / Remove */
function doInsertAdmin() {
    var fo_obj = get_by_id("fo_obj");
    var sel_obj = fo_obj._admin_member;
    var admin_id = fo_obj.admin_id.value;
    if(!admin_id) return;

    var opt = new Option(admin_id,admin_id,true,true);
    sel_obj.options[sel_obj.options.length] = opt;

    fo_obj.admin_id.value = '';
    sel_obj.size = sel_obj.options.length;
    sel_obj.selectedIndex = -1;

    var members = new Array();
    for(var i=0;i<sel_obj.options.length;i++) {
        members[members.length] = sel_obj.options[i].value;

    }
    fo_obj.admin_member.value = members.join(',');

    fo_obj.admin_id.focus();
}

function doDeleteAdmin() {
    var fo_obj = get_by_id("fo_obj");
    var sel_obj = fo_obj._admin_member;
    sel_obj.remove(sel_obj.selectedIndex);

    sel_obj.size = sel_obj.options.length;
    sel_obj.selectedIndex = -1;

    var members = new Array();
    for(var i=0;i<sel_obj.options.length;i++) {
        members[members.length] = sel_obj.options[i].value;

    }
    fo_obj.admin_member.value = members.join(',');
}


function completeModuleSetup(ret_obj) {
    alert(ret_obj['message']);
    window.close();
}

/**
 * Language-related
 **/
function doInsertLangCode(name) {
    var fo_obj = get_by_id("menu_fo");
    var target = fo_obj.target.value;
    if(window.opener && target) {
        var obj = window.opener.get_by_id(target);
        if(obj) obj.value = '$user_lang->'+name;
    }
    window.close();
}

function completeInsertLang(ret_obj) {
    doInsertLangCode(ret_obj['name']);
}

function doDeleteLang(name) {
    var params = new Array();
    params['name'] = name;
    var response_args = new Array('error','message');
    exec_xml('module','procModuleAdminDeleteLang',params, completeDeleteLang);
}

function completeDeleteLang(ret_obj) {
    location.href = current_url.setQuery('name','');
}

function doFillLangName() {
	if (/[\?&]name=/i.test(location.search)) return;

    var $form  = jQuery("#menu_fo");
    var target = $form[0].target.value;
    if(window.opener && window.opener.document.getElementById(target)) {
        var value = window.opener.document.getElementById(target).value;
        if(/^\$user_lang->/.test(value)) {
            var param = new Array();
            param['name'] = value.replace(/^\$user_lang->/,'');
            var response_tags = new Array('error','message','name','langs');
            exec_xml('module','getModuleAdminLangCode',param,completeFillLangName, response_tags);
        }
    }
}

function completeFillLangName(ret_obj, response_tags) {
    var name  = ret_obj['name'];
    var langs = ret_obj['langs'];
    if(typeof(langs)=='undefined') return;
    var $form = jQuery("#menu_fo");
    $form[0].lang_code.value = name;
    for(var i in langs) {
        $form[0][i].value = langs[i];
    }

}
