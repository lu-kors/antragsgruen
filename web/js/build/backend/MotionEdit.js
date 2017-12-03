define(["require","exports","./MotionSupporterEdit","../shared/AntragsgruenEditor"],function(e,t,o,i){"use strict";Object.defineProperty(t,"__esModule",{value:!0});!function(){function e(){var e=this,t=$("html").attr("lang");$("#motionDateCreationHolder").datetimepicker({locale:t}),$("#motionDateResolutionHolder").datetimepicker({locale:t}),$("#resolutionDateHolder").datetimepicker({locale:$("#resolutionDate").data("locale"),format:"L"}),$("#motionTextEditCaller").find("button").click(function(){e.initMotionTextEdit()}),$(".checkAmendmentCollissions").click(function(t){t.preventDefault(),e.loadAmendmentCollissions()}),$("#motionUpdateForm").submit(function(){$(".amendmentCollissionsHolder .amendmentOverrideBlock > .texteditor").each(function(){var e=CKEDITOR.instances[$(this).attr("id")].getData();$(this).parents(".amendmentOverrideBlock").find("> textarea").val(e)})}),$(".motionDeleteForm").submit(function(t,o){e.onSubmitDeleteForm(t,o)}),new o.MotionSupporterEdit($("#motionSupporterHolder"))}e.prototype.onSubmitDeleteForm=function(e,t){t&&(t.confirmed,!0)&&!0===t.confirmed||(e.preventDefault(),bootbox.confirm(__t("admin","delMotionConfirm"),function(e){e&&$(".motionDeleteForm").trigger("submit",{confirmed:!0})}))},e.prototype.initMotionTextEdit=function(){$("#motionTextEditCaller").addClass("hidden"),$("#motionTextEditHolder").removeClass("hidden"),$(".wysiwyg-textarea").each(function(){var e=$(this).find(".texteditor"),t=new i.AntragsgruenEditor(e.attr("id")).getEditor();e.parents("form").submit(function(){e.parent().find("textarea").val(t.getData())})}),$("#motionUpdateForm").append("<input type='hidden' name='edittext' value='1'>"),$(".checkAmendmentCollissions").length>0&&($(".wysiwyg-textarea .texteditor").on("focus",function(){$(".checkAmendmentCollissions").show(),$(".saveholder .save").prop("disabled",!0).hide()}),$(".checkAmendmentCollissions").show(),$(".saveholder .save").prop("disabled",!0).hide())},e.prototype.loadAmendmentCollissions=function(){var e=$(".checkAmendmentCollissions").data("url"),t={},o=$(".amendmentCollissionsHolder");$("#motionTextEditHolder").children().each(function(){var e=$(this);if(e.hasClass("wysiwyg-textarea")){var o=e.attr("id").replace("section_holder_","");t[o]=CKEDITOR.instances[e.find(".texteditor").attr("id")].getData()}}),$.post(e,{newSections:t,_csrf:$("#motionUpdateForm").find("> input[name=_csrf]").val()},function(e){o.html(e),o.find(".amendmentOverrideBlock > .texteditor").length>0&&(o.find(".amendmentOverrideBlock > .texteditor").each(function(){new i.AntragsgruenEditor($(this).attr("id"))}),$(".amendmentCollissionsHolder").scrollintoview({top_offset:-50})),$(".checkAmendmentCollissions").hide(),$(".saveholder .save").prop("disabled",!1).show()})}}()});
//# sourceMappingURL=MotionEdit.js.map
