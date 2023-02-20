<script src="js/moment.min.js"></script>
<script src="js/constant.js?v={{ date('ymdhis') }}"></script>
<script src="js/websocket.js?v={{ date('ymdhis') }}"></script>
<script>
    $(document).ready(function () {

        $("#btn_search_medicine").click(function () {
            getIssuance();
        });

        $("#search_medicine").keypress(function (e) {
            if (e.which == 13) {
                getIssuance();
            }
        });

        getIssuance();
    });

    function getIssuance() {
        const filtered_description = $("#filter_description").val().trim();
        const filtered_date = $("#filter_date").val();

        $("#medicine_list").empty();
        fetch("{{ url('medicine/get/issuance') }}?filter_description=" + filtered_description +
            "&filter_date=" + filtered_date, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                if (data.results.length < 1) {
                    $("#medicine_list").append("<tr><td class='text-center fs-5' colspan='4'>No record found</td></tr>");
                    return;
                }

                for (let i = 0; i < data.results.length; i++) {
                    const it = data.results[i];
                    const quantity_returned = (it.quantity_returned) ? it.quantity_returned : 0;
                    const tr = `
                        <tr>
                           <td>` + it.description + `</td>
                           <td>` + it.unit + `</td>
                           <td>` + it.quantity_rendered + `</td>
                           <td>` + quantity_returned + `</td>
                        </tr>`;
                    $("#medicine_list").append(tr);
                }
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function logout() {
        window.location.href = '{{ route('logout') }}';
    }
</script>
