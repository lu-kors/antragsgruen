define(["require","exports"],function(e,i){"use strict";Object.defineProperty(i,"__esModule",{value:!0});var t=function(){function e(e){this.$form=e,this.initLogoUpload(),this.initLayoutChooser(),this.initTranslationService(),this.initSpeechQueues(),$('[data-toggle="tooltip"]').tooltip()}return e.prototype.initLogoUpload=function(){var t=this.$form.find(".logoRow"),n=t.find(".uploadCol label .text");t.on("click",".imageChooserDd ul    a",function(e){e.preventDefault();var i=$(e.currentTarget).find("img").attr("src");t.find("input[name=consultationLogo]").val(i),0===t.find(".logoPreview img").length&&t.find(".logoPreview").prepend('<img src="" alt="">'),t.find(".logoPreview img").attr("src",i).removeClass("hidden"),n.text(n.data("title")),t.find("input[type=file]").val("")}),t.find("input[type=file]").on("change",function(){var e=t.find("input[type=file]").val().split("\\"),i=e[e.length-1];t.find("input[name=consultationLogo]").val(""),t.find(".logoPreview img").addClass("hidden"),n.text(i)})},e.prototype.initLayoutChooser=function(){var i=this.$form.find(".thumbnailedLayoutSelector input"),t=this.$form.find(".editThemeLink"),n=t.attr("href"),e=function(){var e=i.filter(":checked");0===e.length&&(e=i.first()),t.attr("href",n.replace(/DEFAULT/,e.val()))};i.on("change",e),e()},e.prototype.initTranslationService=function(){var i=this;this.$form.find("#translationService").on("change",function(e){$(e.currentTarget).prop("checked")?(i.$form.find(".services").removeClass("hidden"),i.$form.find(".services input").prop("required",!0)):(i.$form.find(".services").addClass("hidden"),i.$form.find(".services input").prop("required",!1))}).trigger("change")},e.prototype.initSpeechQueues=function(){var i=this;this.$form.find("#hasSpeechLists").on("change",function(e){$(e.currentTarget).prop("checked")?(i.$form.find(".quotas").removeClass("hidden"),i.$form.find(".quotas input").prop("required",!0)):(i.$form.find(".quotas").addClass("hidden"),i.$form.find(".quotas input").prop("required",!1))}).trigger("change")},e}();i.AppearanceEdit=t});
//# sourceMappingURL=AppearanceEdit.js.map
