!function(t){t("[data-antragsgruen-load-class]").each(function(){var a=t(this).data("antragsgruen-load-class");requirejs([a])}),t("[data-antragsgruen-widget]").each(function(){var a=t(this),e=a.data("antragsgruen-widget");requirejs([e],function(t){var n=e.split("/");new t[n[n.length-1]](a)})}),t(".jsProtectionHint").each(function(){var a=t(this);t('<input type="hidden" name="jsprotection">').attr("value",a.data("value")).appendTo(a.parent()),a.remove()}),bootbox.setLocale(t("html").attr("lang").split("_")[0]),t(document).on("click",".amendmentAjaxTooltip",function(a){var e=t(a.currentTarget);"0"==e.data("initialized")&&(e.data("initialized","1"),e.popover({html:!0,trigger:"manual",container:"body",template:'<div class="popover popover-amendment-ajax" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',content:function(){var a="pop_"+(new Date).getTime(),n='<div id="'+a+'">Loading...</div>',o=e.data("url");return t.get(o,function(e){t("#"+a).html(e)}),n}})),t(".amendmentAjaxTooltip").not(e).popover("hide"),e.popover("toggle")}),t(document).on("click",function(a){var e=t(a.target);e.hasClass("amendmentAjaxTooltip")||e.hasClass("popover")||0!=e.parents(".amendmentAjaxTooltip").length||0!=e.parents(".popover").length||t(".amendmentAjaxTooltip").popover("hide")});var a=function(e){var n="0.";e.find("> li.agendaItem").each(function(){var e=t(this),o=e.data("code"),i="",r=e.find("> ol");if("#"==o){var d=n.split(".");if(d[0].match(/^[a-y]$/i))d[0]=String.fromCharCode(d[0].charCodeAt(0)+1);else{var l=d[0].match(/^(.*[^0-9])?([0-9]*)$/),c=void 0===l[1]?"":l[1],s=parseInt(""==l[2]?"1":l[2]);d[0]=c+ ++s}n=i=d.join(".")}else i=n=o;e.find("> div > h3 .code").text(i),r.length>0&&a(r)})};t("ol.motionListAgenda").on("antragsgruen:agenda-change",function(){a(t(this))}).trigger("antragsgruen:agenda-change"),window.__t=function(t,a){return"undefined"==typeof ANTRAGSGRUEN_STRINGS?"@TRANSLATION STRINGS NOT LOADED":void 0===ANTRAGSGRUEN_STRINGS[t]?"@UNKNOWN CATEGORY: "+t:void 0===ANTRAGSGRUEN_STRINGS[t][a]?"@UNKNOWN STRING: "+t+" / "+a:ANTRAGSGRUEN_STRINGS[t][a]}}(jQuery);
//# sourceMappingURL=Antragsgruen.js.map
