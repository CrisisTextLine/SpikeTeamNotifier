var Button = {
    init: function() {
        $("#button.enabled").find("button").unwrap();
        $("#button").click(function(){
            var groupId = $('.group-select select').val();
            var groupName = $('option:selected').text();

            if ($(this).is(".enabled")) {
                if (confirm("Are you sure you want to notify the " +
                    groupName + " Spike Team?")) {
                    $(this).removeClass("enabled").addClass("disabled");
                    Button.goCallback(groupId);
                }
            }
            else if ($(this).is(".disabled")) {
                alert("Sorry, you cannot alert the Spike Team yet.");
            }
        });
    },

    goCallback: function(id) {
        $.get(Routing.generate('goteamgo', {gid:id}), function (data) {
            $('.latest .latest-group').html(data.id);
            $('.latest .latest-time').html(data.time);
        });
    }
};



