function doDisplaySkinColorset(colorset)
{
	var skin = jQuery('select[name=skin]').val();
	if(!skin) {
		doHideSkinColorset();
		return;
	}

		function completeGetSkinColorset(ret_obj)
		{
			var sel = jQuery("select[name=colorset]").get(0);
			var length = sel.options.length;
			var selected_colorset = colorset;
			for(var i=0;i<length;i++) sel.remove(0);

			if(!ret_obj["colorset_list"]) return;

			var colorset_list = ret_obj["colorset_list"].split("\n");
			var selected_index = 0;
			for(var i=0;i<colorset_list.length;i++) {
				var tmp = colorset_list[i].split("|@|");
				if(selected_colorset && selected_colorset==tmp[0]) selected_index = i;
				var opt = new Option(tmp[1], tmp[0], false, false);
				sel.options.add(opt);
			}

			sel.selectedIndex = selected_index;

			doShowSkinColorset();
		}

	var params = new Array();
	params["selected_widget"] = jQuery('input[name=selected_widget]').val();
	params["skin"] = skin;
	params["colorset"] = colorset;

	var response_tags = new Array("error","message","colorset_list");

	exec_xml("widget", "procWidgetGetColorsetList", params, completeGetSkinColorset, response_tags, params);
}

function doHideSkinColorset()
{
	jQuery('select[name=colorset]').parents('li').hide();
}

function doShowSkinColorset()
{
	jQuery('select[name=colorset]').parents('li').show();
}

function completeGenerateCodeInPage(widget_code) {
    if(!widget_code) {
        window.parent.triggerClose();
        return;
    }

    window.parent.doAddWidgetCode(widget_code);
    window.parent.triggerClose();
}

var selected_node = null;

function getWidgetVars() {
    if(!window.parent || !window.parent.selectedWidget || !window.parent.selectedWidget.getAttribute("widget")) return;
    selected_node = window.parent.selectedWidget;

    if(!xGetElementById('fo_widget').widgetstyle.value) {
        xGetElementById('fo_widget').widgetstyle.value = selected_node.getAttribute('widgetstyle');
    }

    doFillWidgetVars();
}

function doFillWidgetVars() {
    if(!window.parent || !window.parent.selectedWidget || !window.parent.selectedWidget.getAttribute("widget")) return;
    selected_node = window.parent.selectedWidget;

    // Skin and the color of the three basic
    var skin = selected_node.getAttribute("skin");
    var colorset = selected_node.getAttribute("colorset");
    var widget_sequence = parseInt(selected_node.getAttribute("widget_sequence"),10);

    var fo_widget = jQuery("#fo_widget");
    var fo_obj = xGetElementById("fo_widget");
    jQuery('#widget_skin').val(skin);

    // Add hidden input for the maintenance of the widget style, and store the value
    var attrs = selected_node.attributes;

	//  That occurs in IE7 for jQuery add attribute to filter out
	var attrFilters = ['style', 'sizset', 'draggable', 'class'];

	for (i=0; i< attrs.length ; i++){
		var name = attrs[i].name;
		var value = jQuery(selected_node).attr(name);
		if(value=='Array') continue;
		if(jQuery("[name="+name+"]",fo_widget).size()>0 || !value) continue;
		if(name.indexOf('sizcache')  == 0) continue;
		if(jQuery.inArray(name, attrFilters) > -1) continue;

		var dummy = jQuery('<input type="hidden" name="'+name+'" />').val(value).appendTo("#fo_widget").get(0);
	}

    // Set the Properties of Widget
    var obj_list = new Array();
    jQuery('input,select,textarea','#fo_widget').each( function() {
            obj_list.push(this);
    });

    for(var j=0;j<obj_list.length;j++) {
        var node = obj_list[j];
        if(node.name.indexOf('_')==0) continue;
        if(node.name == 'widgetstyle') continue;
        if(node.type == 'button') continue;
        if(node.name == '') continue;

        var length = node.length;
        var type = node.type;
        if((typeof(type)=='undefined'||!type) && typeof(length)!='undefined' && typeof(node[0])!='undefined' && length>0) type = node[0].type;
        else length = 0;
        var name = node.name;

        switch(type) {
            case "hidden" :
            case "text" :
            case "textarea" :
                    var val = selected_node.getAttribute(name);
                    if(!val) continue;
                    var unescaped_val = unescape(val);
                    if(!unescaped_val) node.value = val;
                    else node.value = unescaped_val;
					jQuery('#'+name).val(node.value);
                break;
            case "checkbox" :
                    if(selected_node.getAttribute(name)) {
                        var val = selected_node.getAttribute(name).split(',');
                        if(fo_obj[name].length) {
                            for(var i=0;i<fo_obj[name].length;i++) {
                                var v = fo_obj[name][i].value;
                                for(var k=0;k<val.length;k++) {
                                    if(v == val[k]) {
                                        fo_obj[name][i].checked=true;
                                        break;
                                    }
                                }
                            }
                        } else {
                            if(fo_obj[name].value == val) fo_obj[name].checked =true;
                        }
                    }
                break;
            case "select" :
            case "select-one" :
                    var val = selected_node.getAttribute(name);
                    var sel = fo_obj[name];
                    if(!val) break;
                    for(var i=0;i<sel.options.length;i++) {
                        if(sel.options[i].value == val) sel.options[i].selected = true;
                        else sel.options[i].selected = false;
                    }
                break;
        }

    }

    var style = selected_node.getAttribute("style");
    if(typeof(style)=="object") style = style["cssText"];
    fo_obj.style.value = style;

    fo_obj.widget_padding_left.value = selected_node.getAttribute("widget_padding_left");
    fo_obj.widget_padding_right.value = selected_node.getAttribute("widget_padding_right");
    fo_obj.widget_padding_bottom.value = selected_node.getAttribute("widget_padding_bottom");
    fo_obj.widget_padding_top.value = selected_node.getAttribute("widget_padding_top");


    //  Three color settings
    if(skin && xGetElementById("widget_colorset") && xGetElementById("widget_colorset").options.length<1 && colorset) {
        doDisplaySkinColorset(colorset);
    }

    //Set widget sequence
    fo_obj.widget_sequence.value = widget_sequence;

	xe.broadcast('MULTIORDER_SYNC');
	xe.broadcast('MODULELIST_SYNC');
	xe.broadcast('MID_SYNC');
	xe.broadcast('MULTILANG_SYNC');

	jQuery('.filebox')
		.each(function(){
			var $this = jQuery(this);
			var src = $this.siblings('input').eq(0).val().split(',');
			
			if (src) $this.trigger('filebox.selected', [src]);
		})
}

var $current_filebox;

jQuery(document).ready(function($){
	$('select[name=skin]').next('input').bind('click', function(e){
		doDisplaySkinColorset();
	});
	doHideSkinColorset();

	$('#modalFilebox').on('show', function(){
		$('#filebox_upload').find('input[name=comment], input[name=addfile]').val('');
	});

	$('.filebox').bind('filebox.selected', function(e, src){
		$(this)
			.siblings()
			.filter(function(){
				return this.nodeName.toLowerCase() != 'input';
			})
			.remove();
		var htmlCode = "";
		if(src instanceof Object ) {
			for(var i=0;i<src.length;i++){
				if(src[i].id) {
					htmlCode += '<img src="'+src[i].id+'" alt="" style="border: 1px solid #ccc; padding: 5px; max-height: 200px; max-width: 200px;"><button class="filebox_del text btn btn-small btn-danger" type="button">'+xe.lang.cmd_delete+'</button>';
					if(i==0) $(this).siblings('input').val(src[i].id);
					else {
						var aux = $(this).siblings('input').val();
						$(this).siblings('input').val(aux+","+src[i].id);
					}
				}
				else {
					if(src[i]){
						htmlCode += '<img src="'+src[i]+'" alt="" style="border: 1px solid #ccc; padding: 5px; max-height: 200px; max-width: 200px;"><button class="filebox_del text btn btn-small btn-danger" type="button">'+xe.lang.cmd_delete+'</button>';
						if(i==0) $(this).siblings('input').val(src[i]);
						else {
							var aux = $(this).siblings('input').val();
							$(this).siblings('input').val(aux+","+src[i]);
						}
					}
				}
			}
		} else {
			htmlCode = '<img src="'+src+'" alt="" style="border: 1px solid #ccc; padding: 5px; max-height: 200px; max-width: 200px;"> <button class="filebox_del text btn btn-small btn-danger" type="button">'+xe.lang.cmd_delete+'</button>';
			$(this).siblings('input').val(src);
		}
		$(this).before(htmlCode);

		

		$('.filebox_del').bind('click', function(){
			var filename = $(this).prev('img').attr("src");
			var files = $(this).siblings('input').val().split(",");
			var newInput = "";
			for(var i=0;i<files.length;i++){
				if(files[i] != filename) {
					if(!newInput.length) newInput = files[i];
					else newInput += ","+files[i];
				}
			}
			$(this).siblings('input').val(newInput);
			$(this).prev('img').remove();
			$(this).remove();
		});
	});

	$('.filebox').click(function(){
		$current_filebox = $(this);
	});

	$('#filebox_upload').find('button[type=submit], input[type=submit]').click(function(){
		if ($('iframe[name=iframeTarget]').length < 1){
			var $iframe = $(document.createElement('iframe'));

			$iframe.css('display', 'none');
			$iframe.attr('src', '#');
			$iframe.attr('name', 'iframeTarget');
			$iframe.load(function(){
				var data = eval('(' + $(window.iframeTarget.document.getElementsByTagName("body")[0]).html() + ')');

				if (data.error){
					alert(data.message);
					return;
				}

				$current_filebox.trigger('filebox.selected', [data.save_filename]);
                $("#modalFilebox").modal('hide');
			});

			$('body').append($iframe.get(0));

			$(this).parents('form').attr('target', 'iframeTarget');
		}
	});

	$('#widget_code_form').bind('submit', function(){
		function on_complete(data){
			if (data.error){
				alert(data.message);
				return;
			}

			$('#widget_code').val(data.widget_code);
		}

		var datas = $(this).serializeArray();
		var params = new Object();
		for(var i in datas){
			var data = datas[i];

			if(/\[\]$/.test(data.name)) data.name = data.name.replace(/\[\]$/, '');
			if(params[data.name]) params[data.name] += '|@|' + data.value;
			else params[data.name] = data.value;
		}

		$.exec_json('widget.procWidgetGenerateCode', params, on_complete);

		return false;
	});

	$('#fo_widget').bind('submit', function(){
		function on_complete(data){
			if (data['error'] != '0'){
				alert(data['message']);
				return;
			}

			completeGenerateCodeInPage(data['widget_code']);
		}

		var datas = $(this).serializeArray();
		var params = new Object();
		for(var i in datas){
			var data = datas[i];

			if(/\[\]$/.test(data.name)) data.name = data.name.replace(/\[\]$/, '');
			if(params[data.name]) params[data.name] += '|@|' + data.value;
			else params[data.name] = data.value;
		}

		exec_xml('widget', 'procWidgetGenerateCodeInPage', params, on_complete, ['error', 'message', 'widget_code']);
		return false;
	});


});
