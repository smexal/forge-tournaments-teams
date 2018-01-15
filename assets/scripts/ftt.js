var forgeTournamentTeams = {
    init : function() {

        timeout = false;
        $("#team-search-form").find("input[name='team_search']").on('input', function() {
            clearTimeout(timeout);
            var self = $(this);
            if(self.val().length == 0) {
                self.closest('#team-search-form').find("#team-results").html('');
                return;
            }
            timeout = setTimeout(function() {
                $.ajax({
                    method: "POST",
                    url: self.closest('form').attr('action'),
                    data: {
                        'query' : self.val()
                    }
                }).done(function(data) {
                    self.closest('#team-search-form').find("#team-results").html(data.content);
                    $(document).trigger("ajaxReload");
                });
            }, 400);
        })

    }
};

$(document).ready(forgeTournamentTeams.init);
$(document).on("ajaxReload", forgeTournamentTeams.init);