<?php
namespace app\views\admin;

use app\views\AbstractView;

class RainbowCustomizationView extends AbstractView{
    public function printForm($customization_data){
        $return = "";
        $return .= <<<HTML
<script src="js/Sortable.js"></script>

<script type="text/javascript">
    function ExtractBuckets(){
        var x = new Array();
        var bucket_list = $("#buckets li");
        bucket_list.each(function(idx,li){
            x.push($(li).text());
        })        
        
        //$("#generate_json").val(x.toString());
        $("#generate_json").val(JSON.stringify(x));
        $("#custom_form").submit();
        return true;
    }
</script>

<div class="content">
Form would be printed here. Right now the data received is:<br />
<pre>
HTML;
        $return .= print_r($customization_data,true);
        $return .= <<< HTML
</pre>
<br />
If you'd like to try submitting something...
<form id="custom_form" method="post" action="">
<input type="hidden" id="generate_json" name="generate_json" value="true" />
Fake text box: <input type="text" name="demo_text" value="" /><br />
<ol id="buckets">
HTML;
        foreach(array_keys($customization_data) as $bucket){
            $return .= "<li>$bucket</li>";
        }
        $return .= <<< HTML
</ol>
<input type="submit" name="generate_json2" value="Submit" onclick="ExtractBuckets();"/>
</form>
</div>

<style type="text/css">
#buckets li{
    font-weight: bold;
}
</style>

<script type="text/javascript">
    var el = document.getElementById('buckets');
    var sortable = Sortable.create(el);   
</script>

HTML;
        return $return;
    }

    public function printCompletedCustomization($filename){
        $return = "";
        $return .= <<<HTML
<div class="content">
Success message would be printed here, and the file transfered.
HTML;
        return $return;
    }

    public function printError($error_messages){
        //TODO: This should eventually be scrapped in favor of reprinting the form with the input it was given, maybe marking erroneous parts in red.
        //TODO: Could use $_SESSION instead of outright printing an error, depends on if we want to report multiple problems at once.
        $return = "";
        $return .= <<<HTML
<div class="content">
The following errors occurred while processing your input:
HTML;

        assert(is_array($error_messages) && count($error_messages)>0);
        foreach($error_messages as $error){
            $return .= "<p>$error</p>";
        }

        $return .= '</div>';

        return $return;

    }
}