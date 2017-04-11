$(function () {
    var clipboard = new Clipboard('button.to-clipboard', {
        text: function (trigger) {
            return $(trigger).parent().find("code:first").text();
        }
    });
    clipboard.on('success', function (e) {
        var $trigger = $(e.trigger);
        $trigger.text("Copied!");
        setTimeout(function () {
            $trigger.html('<i class="fa fa-clipboard" aria-hidden="true"></i> Copy');
        }, 3000);
        e.clearSelection();
    });

});
