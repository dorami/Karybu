/* Sign in focus in the area */
function doFocusUserId(fo_id) {
    if(xScrollTop()) return;
    var fo_obj = xGetElementById(fo_id);
    if(fo_obj.user_id) {
        try{
            fo_obj.user_id.focus();
        } catch(e) {};
    }
}

/* After logging in */
function completeLogin(ret_obj, response_tags, params, fo_obj) {
    var url =  current_url.setQuery('act','');
    location.href = url;
}

/* Login to OpenID */
function completeOpenIDLogin(ret_obj, response_tags) {
    var redirect_url =  ret_obj['redirect_url'];
    location.href = redirect_url;
}

/* OpenID converted form */
function toggleLoginForm(obj) {
    if(xGetElementById('login').style.display != "none") {
        xGetElementById('login').style.display = "none";
        xGetElementById('openid_login').style.display = "block";
        xGetElementById('use_open_id_2').checked = true;
    } else {
        xGetElementById('openid_login').style.display = "none";
        xGetElementById('login').style.display = "block";
        xGetElementById('use_open_id').checked = false;
        xGetElementById('use_open_id_2').checked = false;
    }
}
