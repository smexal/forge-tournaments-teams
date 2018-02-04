var forgeTournamentTeams = {
    init : function() {

        timeout = false;
        $("#team-search-form").find("input[name='team_search']").on('keyup keypress', function(e) {
            var keyCode = e.keyCode || e.which;
            if (keyCode === 13) { 
                e.preventDefault();
                return false;
            }
        });

        $("#team-search-form").find("input[name='team_search']").on('input', function() {
            clearTimeout(timeout);
            var self = $(this);
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