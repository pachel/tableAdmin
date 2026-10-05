<script type="text/javascript">
    // Szűrés gombra kattintás
    $('#sendSearch').on('click', function () {
        table.draw(); // Újratölti a táblázatot az 1. oldaltól a form adataival
    });
    document.getElementById('clearBtn').addEventListener('click', function (e) {
        e.preventDefault(); // Megelőzi a form beküldését/újratöltést
        hardResetForm('#tableAdminForm');
        table.state.clear();
        $('.dataTables_filter input').val('');
        table.search('').draw();
    });

    function hardResetForm(formSelector) {
        const form = typeof formSelector === 'string'
            ? document.querySelector(formSelector)
            : formSelector;

        if (!form) return;

        // 1. inputok és textareák kezelése
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            const type = input.type.toLowerCase();

            if (type === 'checkbox' || type === 'radio') {
                input.checked = false;
                input.defaultChecked = false; // levesszük a kezdeti checked jelölőt is
            } else if (type !== 'button' && type !== 'submit' && type !== 'reset' && type !== 'hidden') {
                // Szöveges, szám, email, dátum stb. mezők kiürítése
                input.value = '';
                input.defaultValue = ''; // levesszük a kezdeti value értéket is
            }
        });

        // 2. select (legördülő) elemek kezelése
        const selects = form.querySelectorAll('select');
        selects.forEach(select => {
            // Kijelölések törlése az opciókról
            Array.from(select.options).forEach(option => {
                option.selected = false;
                option.defaultSelected = false;
            });
            // Ha azt szeretnéd, hogy teljesen üres legyen (egyik se legyen kiválasztva):
            select.selectedIndex = 0;
        });
    }

</script>