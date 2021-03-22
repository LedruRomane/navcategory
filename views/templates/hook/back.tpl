{literal}
    <style type="text/css">

    #fieldset_1_1{
        display: block;
    }
    #fieldset_2_2{
        display: none;
    }
    </style>
    <script type="text/javascript">
        $(document).ready(function() {
            $(jQuery( "#perso" )).click(function() {
                document.getElementById('fieldset_1_1').style.display = 'block';
                document.getElementById('fieldset_2_2').style.display = 'none';
            });
            $(jQuery( "#auto:radio" )).click(function() {
                document.getElementById('fieldset_1_1').style.display = 'none';
                document.getElementById('fieldset_2_2').style.display = 'block';
            });
        });
    </script>
{/literal}