/**
 * @file statsPivotTable plugin for limesurvey, javascript part
 * @author Denis Chenu
 * @copyright Denis Chenu <http://www.sondages.pro>
 * @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL v3.0
 */

/**
 * Set first word to strong
 * @link https://github.com/melbon/jquery.useWord
 */
$.fn.firstWord = function() {
  var text = this.text().trim().split(" ");
  var first = text.shift();
  this.html((text.length > 0 ? "<strong>"+ first + "</strong> " : first) + text.join(" "));
};

$(function(){
    var derivers = $.pivotUtilities.derivers;
    var renderers = $.extend(
        $.pivotUtilities.renderers,
        $.pivotUtilities.c3_renderers,
        $.pivotUtilities.d3_renderers,
        $.pivotUtilities.export_renderers
    );
    $.getJSON(LS.plugin.statsPivotTable.jsonUrl, function(responses) {
        $("#pivot-table").pivotUI(responses, {
            renderers : renderers,
            onRefresh: function() {
                $("#pivot-table-construct").fadeOut(400).remove();
                $("#pivot-table .pvtUi").addClass('table table-condensed table-bordered');
                $("#pivot-table .ui-sortable-handle").addClass('btn btn-default btn-sm btn-block');
                $("#pivot-table select").addClass('form-control');
                $("#pivot-table .pvtFilterBox input[type='text']").addClass('form-control input-sm');
                $("#pivot-table .pvtFilterBox button").addClass('btn btn-default btn-sm');
                $("#pivot-table .pvtVals").find("br").remove();
                $("#pivot-table .pvtTriangle").addClass('label label-default');
                $("#pivot-table table.pvtUi td").not(":last").addClass('active');
                $("#pivot-table table.pvtUi td.pvtRendererArea").removeClass('active');
                $("#pivot-table .pvtFilterBox").addClass("panel panel-default");
                $("#pivot-table .pvtFilterBox h4").addClass("panel-heading");
                $("#pivot-table .pvtFilterBox .pvtCheckContainer").addClass("panel-body");
                $("#pivot-table .pvtFilterBox > p").addClass("panel-footer");
                $("#pivot-table .pvtFilterBox .count").addClass("badge");
                /* Commented : this brokepivotable.js
                $("#pivot-table").find(".ui-sortable-handle").each(function(){
                    $(this).firstWord();
                });
                */
            }
        });
    });
});

