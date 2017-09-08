(function($) {
    var updatesGraph = $('.updates-graph');
    updatesGraph.circleProgress({
        value: updatesGraph.attr('data-value') / 100,
        fill: updatesGraph.attr('data-color'),
        size: 175,
      }).on('circle-animation-progress', function(event, progress) {
        console.log(progress);
        $(this).find('.update-graph-content').html(Math.round(progress * updatesGraph.attr('data-value')) + '<i>%</i>');
    });


    var uptimeGraph = $('.uptime-graph');
    uptimeGraph.circleProgress({
        value: 100,
        fill: uptimeGraph.attr('data-value') == 'true' ? "#4ce276" : "#f36362", // true = up
        size: 125,
      }).on('circle-animation-progress', function(event, progress) {
        displayText = uptimeGraph.attr('data-value') == 'true' ? "UP" : "DOWN";
        $(this).find('.uptime-graph-content').html(displayText);
    });



})(jQuery);