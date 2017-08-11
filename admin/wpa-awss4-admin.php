<?php
if ( ! defined( 'wpas3PLUGIN_URL' ) )
define( 'wpas3PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$wpas3_bootStrapJS = wpas3PLUGIN_URL."admin/asset/js/bootstrap.min.js";

$wpas3_bootStrapCSS = wpas3PLUGIN_URL."admin/asset/css/bootstrap.min.css";

wp_register_script('wpas3-bootstrap_init', $wpas3_bootStrapJS);

wp_enqueue_script('wpas3-bootstrap_init');

wp_register_style('wpas3-bootstrapCSS_init', $wpas3_bootStrapCSS);

wp_enqueue_style('wpas3-bootstrapCSS_init');

if ( ! defined( 'wpas3basedir' ) )

define( 'wpas3basedir', plugin_dir_path( __FILE__ ) );

$s3domain = site_url();

$s3domain = preg_replace("(^https?://)", "", $s3domain);

if(substr(phpversion(),0,3) < '5.4')
{
echo "<h2>ALERT: The plugin requires PHP version 5.4 or higher</h2>";
}

if(file_exists(wpas3basedir . $s3domain . ".txt")) $wpas3_modifieddata = file_get_contents(wpas3basedir . $s3domain . ".txt");


echo "<h4>Current Time on server is: " . date("Y-m-d H:i A") . "</h4>";


$timestamp = wp_next_scheduled( 'wpas3_scheduled_backups');
echo "<h4>Backup is scheduled at " .date('Y-m-d H:i A',$timestamp). "</h4>";

?>

<div class=col-sm-3>

<h3>STEP I</h3>

<b>Access key ID:</b><br>

<input type=text id=wpas3_s3ak class=form-control placeholder="Access Key ID" value="<?php echo get_option("WPGS_username");?>">	

<p>

<b>Secret Key:</b><br>

<input type=password id=wpas3_s3sk class=form-control placeholder="Secret Key" value="<?php echo get_option("WPGS_secret");?>">	

<p>

<b>S3 Backup Folder Name:</b><br>

<input type=text id=wpas3_s3origin class=form-control READONLY placeholder="Domain Name" value="<?php echo $s3domain ; ?>">	

<p>

<b>S3 Backup Folder Region:</b><br>

<select id=wpas3_s3price class=form-control placeholder="Price Class">	

<option value='us-east-1'>N. Virginia (us-east-1)</option>

<option value='us-east-2'>Ohio (us-east-2)</option>

<option value='us-west-1'>Northern California (us-west-1)</option>

<option value='us-west-2'>Oregon (us-west-2)</option>

<option value='ca-central-1'>Canada (ca-central-1)</option>					

<option value='ap-south-1'>Mumbai (ap-south-1)</option>

<option value='ap-northeast-2'>Seoul (ap-northeast-2)</option>

<option value='ap-southeast-1'>Singapore (ap-southeast-1)</option>

<option value='ap-southeast-2'>Sydney (ap-southeast-2)</option>

<option value='ap-northeast-1'>Tokyo (ap-northeast-1)</option>

<option value='eu-central-1'>Frankfurt (eu-central-1)</option>

<option value='eu-west-1'>Ireland (eu-west-1)</option>

<option value='eu-west-2'>London (eu-west-2)</option>

<option value='sa-east-1'>São Paulo (sa-east-1)</option>


</select>

<P>



<button id=wpas3_deployAWSs3 class='btn btn-danger form-control'>Create S3 Backup Folder</button><br>&nbsp;</br>

<button id=wpas3_listAWSs3 class='btn btn-danger form-control'>List S3 Backup Folders</button><br>&nbsp;</br>

<button id=wpas3_deleteAWSs3 class='btn btn-danger form-control'>Delete S3 Backup Folders</button>

<p>
Dont forget to check out the FAQ section
</p>
<p>
<b>
<a href='https://PayPal.Me/GraySquareSolutions' target=_BLANK>Buy Me a Coffee</a>					
</b>
</p>



</div>



<?php 

$s3url =  get_option("WPAdmin_s3_URL"); 

if($s3url == "Blank") $s3url = "";	

?>



<div class=col-sm-3>

<h3>STEP II</h3>

<b>Configure Backups</b>
<p>&nbsp;</p>						
Perform a <B>Full</b> Backup on<br>
<select class=form-control id=wpas3_s3fbsch>
<?php 
$wpafbsch = "Monday";
if(get_option("WPAdmin_s3_fbsch")) $wpafbsch = get_option("WPAdmin_s3_fbsch");
$days =["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
foreach($days as $day)
{
if($wpafbsch == $day)
{
echo "<option SELECTED value=\"$day\">$day</option>";	
}
else
{
echo "<option value=\"$day\">$day</option>";
}
}
?>
</select>
<p>&nbsp;</p>
Number of <B>Full Backups</b> to retain<br>
<?php 
$retain = 3;
if(get_option("WPAdmin_s3_retain")) $retain = get_option("WPAdmin_s3_retain");
?>
<input type=number min=1 max=52 value=<?php echo $retain;  ?> id=wpas3_s3retain class='form-control'>				


<p>&nbsp;</p>
Perform a Backup Everyday at<br>
<?php $wpasch = "23:11:00";
if(get_option("WPAdmin_s3_sch")) $wpasch = get_option("WPAdmin_s3_sch"); ?> 

<input type=time id=wpas3_s3sch class=form-control value='<?php echo $wpasch; ?>'>	
<p>&nbsp;</p>			
<button id=wpas3_saveconfig class='btn btn-danger form-control'>Save Configuration</button>
<p>&nbsp;</p>					
<button id=wpas3_fullbackup class='btn btn-danger form-control'>Perform Full Backup</button>
<p>&nbsp;</p>	
<?php 
if($wpas3_modifieddata)
{
?>

<button id=wpas3_increment class='btn btn-danger form-control'>Backup files modified since <?php echo $wpas3_modifieddata; ?></button>
<?php
}
?>
<p>&nbsp;</p>
<div id=wpas3_WPAsaveresult></div>

<p>&nbsp;</p>
</div>

<div class=col-sm-6>

<h3>Response</h3>

<div id=wpas3_WPAresult>

<div class="tabbable" id="Bmmli">

<ul class="nav nav-tabs">

<li class="active">

<a href="#tab1" data-toggle="tab">

<p>

How to</p>

</a>

</li>

<li>

<a href="#tab2" data-toggle="tab">

<p>

FAQ</p>

</a>

</li>

</ul>

<div class="tab-content">

<div class="tab-pane active" id="tab1">

<p>&nbsp;</p>

<b>Setup CloudFront</b>

<ol>

<li>Setup your AWS Account @ <a href='http://aws.amazon.com/' target=_BLANK>aws.amazon.com</a>. The account, if not admin, should be a member of 'PowerUserAccess' group</li>

<li>Retrieve the <i>Access Key ID</i> & <i>Secret Key</i></li>

<li>Enter the <i>Access Key ID</i> & <i>Secret Key</i> in the respective input boxes on the left</li>

<li>The domain name is automatically listed and the folder will be created on AWS S3 using the domain name. </li>

<li>Select the <u>S3 Backup Folder Region</u> </li>

<li>Click the <u>Create S3 Backup Folder</u> button</li>

<li>Enable Backups in Step II</li>

</ol>

</p>

</div>

<div class="tab-pane" id="tab2">

<p>

<dl>

<dt>How does the plugin work?</dt>

<dd><p>The Plugin performs a full and differential backup.</p>The <u>'Perform Full Backup'</u> backs up the wp-content folder, the wp-config file and the backup of your database to Amazon S3<br>
The <u>'Backup File Modified Since...'</u> backs up the file modified after specified date in wp-content folder, the wp-config file and the backup of your database to Amazon S3.</dd>

<dt>How do I restore the backup?</dt>

<dd>You will have to download the last <b>Full Backup</b> & the latest <b>Differential Backup</b> from AWS S3 and restore it on the server.</dd>

<dt>What is a differential Backup?</dt>

<dd>Differential Backup is a backup of all files created or modified since the full backup. They are usually very small in size.</dd>


<dt>Why are the AWS Access Key ID and Secret Key Stored?</dt>

<dd>The AWS Access Key ID and Secret Key is used while initiating a backup on AWS S3. It is your responsibility to keep them safe - do not share them with anyone.</dd>


<dt> What content is moved to AWS S3</dt>

<dd> All files and folders under wp-content, the wp-config file and the database are moved to AWS S3.</dd>

<dt> Can I  edit what goes and what does not?</dt>

<dd> Unfortunately, the plugin does not support granular control over contents that can be moved to AWS S3</dd>

<dt>What if I have a few Questions?</dt>

<dd>Visit  <a href='http://wpadmin.ca?utm_source=Websites&utm_medium=WordPress&utm_campaign=WordPressS3Plugin' target=_BLANK>WPAdmin.ca</a>, Chat with me If I am online or Leave a Message using the <a href='http://wpadmin.ca/contact-us/?utm_source=Websites&utm_medium=WordPress&utm_campaign=WordPressS3Plugin' target=_BLANK>contact form</a>  </dd>

<dt>I want to buy you a coffee?</dt>

<dd>Thanks! you can transfer via <a href='https://PayPal.Me/GraySquareSolutions' target=_BLANK>PayPal</a></dd>

</dl>

</p>

</div>

</div>

</div>

<p>

</p>

<p>

</p>



</div>

</div>
<div style=clear:both></div>
<?php echo "<b>NOTE</b>: Default Max execution time is " . ini_get('max_execution_time') . " seconds and will be switched to 15 minutes during <b>Full Backup</b>";
?>
<script>

jQuery(document).ready(function(){
	
jQuery(".deleteobjects").live('click',function(){
var folder = jQuery(this).attr('data-id');
var object = jQuery(this).attr('data-object');
jQuery.ajax({
url: ajaxurl,
data: {
'action':'wpas3_deletefoldercontent_s3',
'folder' : folder,
'object' : object
},
success:function(data) {
if(data != "") jQuery("#wpas3_WPAresult").html(data);
},
error: function(errorThrown){
jQuery("#wpas3_WPAresult").html(errorThrown.responseText);
}
});
});

jQuery(".showobjects").live('click',function(){
var folder = jQuery(this).attr('data-id');
jQuery.ajax({
url: ajaxurl,
data: {
'action':'wpas3_listfoldercontent_s3',
'folder' : folder
},
success:function(data) {
if(data != "") jQuery("#wpas3_WPAresult").html(data);
},
error: function(errorThrown){
jQuery("#wpas3_WPAresult").html(errorThrown.responseText);
}
});

});

jQuery(".retval").live('click',function(){

jQuery("#wpas3_s3url").val(jQuery(this).text()).focus();	

});

jQuery("#wpas3_increment").click(function(){
jQuery("#wpas3_WPAresult").html("Incremental Backup Started...");
jQuery.ajax({
url: ajaxurl,
data: {
'action':'wpas3_incremental_s3'
},
success:function(data) {
if(data != "") jQuery("#wpas3_WPAresult").append(data);
},
error: function(errorThrown){
jQuery("#wpas3_WPAresult").html(errorThrown.responseText);
}
});


});


jQuery("#wpas3_fullbackup").click(function(){

jQuery("#wpas3_WPAresult").html("Backup Started, Please Check back in 15 minutes");

jQuery.ajax({
url: ajaxurl,
type: "POST",
data: {
'action':'wpas3_fullbackup_s3'
},
success:function(data) {
if(data != "") jQuery("#wpas3_WPAresult").append(data);
},
error: function(errorThrown){
jQuery("#wpas3_WPAresult").html(errorThrown.responseText);
}
});


});

jQuery("#wpas3_deleteAWSs3").click(function(){

var ak = jQuery("#wpas3_s3ak").val();

jQuery("#wpas3_s3ak").focus();

if(ak == "") return;

var sk = jQuery("#wpas3_s3sk").val();

jQuery("#wpas3_s3sk").focus();

if(sk == "") return;

var ori = jQuery("#wpas3_s3origin").val();

var ap = jQuery("#wpas3_s3price").val();

jQuery("#wpas3_listAWSs3").focus();

jQuery.ajax({

url: ajaxurl,

data: {

'action':'wpas3_delete_s3',

'wpas3_ak' : ak,

'wpas3_sk' : sk,

'wpas3_ap' : ap,

'wpas3_ori' : ori

},

success:function(data) {

jQuery("#wpas3_WPAresult").html(data);

},

error: function(errorThrown){

jQuery("#wpas3_WPAresult").html(errorThrown.responseText);

}

});	

});


jQuery("#wpas3_listAWSs3").click(function(){

var ak = jQuery("#wpas3_s3ak").val();

jQuery("#wpas3_s3ak").focus();

if(ak == "") return;

var sk = jQuery("#wpas3_s3sk").val();

jQuery("#wpas3_s3sk").focus();

if(sk == "") return;

var ori = jQuery("#wpas3_s3origin").val();

var ap = jQuery("#wpas3_s3price").val();

jQuery("#wpas3_listAWSs3").focus();

jQuery.ajax({

url: ajaxurl,

data: {

'action':'wpas3_list_s3',

'wpas3_ak' : ak,

'wpas3_sk' : sk,

'wpas3_ap' : ap,									

'wpas3_ori' : ori

},

success:function(data) {
jQuery("#wpas3_WPAresult").html(data);

},

error: function(errorThrown){

jQuery("#wpas3_WPAresult").html(errorThrown.responseText);

}

});	

});

jQuery("#wpas3_s3files").click(function(){
if(jQuery(this).hasClass("checked"))
{
jQuery(this).removeClass("checked")	;
}
else
{
jQuery(this).addClass("checked");
}	
});

jQuery("#wpas3_s3db").click(function(){
if(jQuery(this).hasClass("checked"))
{
jQuery(this).removeClass("checked")	;
}
else
{
jQuery(this).addClass("checked");
}	
});


jQuery("#wpas3_saveconfig").click(function(){
var wps3fbsch = jQuery("#wpas3_s3fbsch").val();
var wps3retain = jQuery("#wpas3_s3retain").val();
var wps3sch = jQuery("#wpas3_s3sch").val();
if(wps3sch == "" )wps3sch = "23:11:00";

jQuery.ajax({
type:"POST",
url: ajaxurl,

data: {

'action':'wpas3_save_s3',

'wpas3_fbsch' : wps3fbsch,

'wpas3_retain' : wps3retain,

'wpas3_sch' : wps3sch

},

success:function(data) {

jQuery("#wpas3_WPAsaveresult").html(data);

},

error: function(errorThrown){

jQuery("#wpas3_WPAsaveresult").html(errorThrown.responseText);

}

});	
});

jQuery("#wpas3_deployAWSs3").click(function(){	
var ak = jQuery("#wpas3_s3ak").val();

jQuery("#wpas3_s3ak").focus();

if(ak == "") return;

var sk = jQuery("#wpas3_s3sk").val();

jQuery("#wpas3_s3sk").focus();

if(sk == "") return;

var ori = jQuery("#wpas3_s3origin").val();

var ap = jQuery("#wpas3_s3price").val();

jQuery("#wpas3_deployAWSs3").focus();

jQuery.ajax({

type:"POST",

url: ajaxurl,

data: {

'action':'wpas3_create_s3',

'wpas3_ak' : ak,

'wpas3_sk' : sk,

'wpas3_ap' : ap,

'wpas3_ori' : ori

},

success:function(data) {

jQuery("#wpas3_WPAresult").html(data);

},

error: function(errorThrown){

jQuery("#wpas3_WPAresult").html(errorThrown.responseText);

}

});



});



jQuery("#wpas3_s3url").blur(function(){

var s3url = jQuery(this).val();

jQuery.ajax({

url: ajaxurl,

data: {

'action':'wpas3_set_s3',

'wpas3_s3url' : s3url

},

success:function(data) {

jQuery("#wpas3_WPAResult").html(data);

},

error: function(errorThrown){

jQuery("#wpas3_WPAResult").html(errorThrown.responseText);

}

});



});

});

</script>				
