/**
 * @file statsPivotTable plugin for limesurvey, javascript part
 * @author Denis Chenu
 * @copyright Denis Chenu <http://www.sondages.pro>
 * @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL v3.0
 */

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
                // @Todo : add bs css
            }
        });
    });
});
