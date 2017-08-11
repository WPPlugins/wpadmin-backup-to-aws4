<?php
/*
Plugin Name: WPAdmin Backup to AWS4
Plugin URI: http://wpadmin.ca/wpadmin-backup-to-aws4/
Description: Use Amazon AWS S3 to backup your WordPress Site daily. This plugin will backup the wp-config.php, wp-content folder and database to Amazon AWS S3. You can create, list and even delete folder on Amazon AWS S3.
Author: WPAdmin
Version: 0.6.1
Author URI: http://wpadmin.ca
*/
/*error_reporting(E_ALL);
ini_set('display_errors', 1);*/

$s3domain = site_url();
$s3domain = preg_replace("(^https?://)", "", $s3domain);
$s3domainnowww = str_replace("www.","",$s3domain);



if ( ! defined( 'wpas3basedir' ) )
define( 'wpas3basedir', str_replace("\\","/",plugin_dir_path( __FILE__ )) );

if ( ! defined( 'wpas3PLUGIN_BASENAME' ) )
define( 'wpas3PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'wpas3PLUGIN_DIRNAME' ) )
define( 'wpas3PLUGIN_DIRNAME', dirname( wpas3PLUGIN_BASENAME ) );

if ( ! defined( 'wpas3PLUGIN_URL' ) )
define( 'wpas3PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if( !function_exists( 'GuzzleHttp\Psr7\str' ) ) {
require  wpas3basedir . 'admin/aws-autoloader.php';
}
use Aws\S3\S3Client;

class wpaawss3
{

public function __construct(){

}

public function load()
{
add_action("admin_menu", array(&$this,'WPAs3Menus'));
wp_register_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), '20170101', true );
wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), '20170101', true );
}

function wpa_s3_checkconfigpath()
{

$s3domain = site_url();
$s3domain = preg_replace("(^https?://)", "", $s3domain);
$s3domainnowww = str_replace("www.","",$s3domain);


if(file_exists($_SERVER['DOCUMENT_ROOT']  . '/wp-config.php')) {
$wpa_s3_checkconfigpath['wpa_s3_wpconfig'] = $_SERVER['DOCUMENT_ROOT']  . '/wp-config.php';
$wpa_s3_checkconfigpath['wpa_s3_wpconfigfile'] = 'wp-config.php';
}
else if(file_exists('/etc/wordpress/config-'.$s3domain.'.php')) {
$wpa_s3_checkconfigpath['wpa_s3_wpconfig'] = '/etc/wordpress/config-'.$s3domain.'.php';
$wpa_s3_checkconfigpath['wpa_s3_wpconfigfile'] = 'config-'.$s3domain.'.php';
}
else if(file_exists('/etc/wordpress/config-'.$s3domainnowww.'.php')) {
$wpa_s3_checkconfigpath['wpa_s3_wpconfig'] = '/etc/wordpress/config-'.$s3domainnowww.'.php';
$wpa_s3_checkconfigpath['wpa_s3_wpconfigfile'] = 'config-'.$s3domainnowww.'.php';
}
return $wpa_s3_checkconfigpath;
}


function WPAs3Menus() {
add_menu_page("WPAdmin AWS4", "WPAdmin AWS4", 0, "wpa-aws4", array($this,'wpas3toplevel_page'));
}


function wpas3_list_s3(){
if ( isset($_REQUEST) ) 
{
@$wpas3_ak = $_REQUEST['wpas3_ak'];
@$wpas3_sk = $_REQUEST['wpas3_sk'];
@$wpas3_ap = $_REQUEST['wpas3_ap'];
@$wpas3_ori = $_REQUEST['wpas3_ori'];

$wpas3_client = $this->wpas3_authClient($wpas3_ak,$wpas3_sk,$wpas3_ap);

try
{
$result = $wpas3_client->listBuckets();
foreach ($result['Buckets'] as $bucket) 
{
$wpas3_bucketlocation = $wpas3_client->getBucketLocation(['Bucket' => $bucket['Name'],]);
if($bucket['Name'] == $_SERVER['SERVER_NAME'])
{
echo "<h3><a class=showobjects data-id=".$bucket['Name']." href='javascript:void(0)'><img src='".wpas3PLUGIN_URL."admin/asset/images/folder.png' title='Show Folders' alt='Show Folders' width=40px></a> <b>".$bucket['Name']. "</b> in <b>". $wpas3_bucketlocation['LocationConstraint'] . "</b></h3>";
}
}
}
catch (Exception $e) 
{
$err = $e->getMessage();
$er = explode("response",$err);
echo "<div class='alert alert-danger'>" .$er[0] . "</div>";
}
}
wp_die();
}


function wpas3_incremental_s3()
{
$wpa_current_met = ini_get('max_execution_time');
$wpa_current_mem = ini_get('memory_limit') ;
ini_set('max_execution_time', '900');
ini_set('memory_limit', '1024M');

$wps3date = date('Y-m-d');
$wpas3_bucket = get_option("WPAdmin_s3_bucket");

$this->wpas3_backupdb_s3('Inc');
$this->wpas3_backupconfig_s3('Inc');
$this->wpas3_createzip_s3('Inc');

ini_set('max_execution_time', $wpa_current_met);
ini_set('memory_limit', $wpa_current_mem);
wp_die();
}

function wpas3_fullbackup_s3()
{
$wpa_current_met = ini_get('max_execution_time');
$wpa_current_mem = ini_get('memory_limit') ;
ini_set('max_execution_time', '900');
ini_set('memory_limit', '1024M');

$wps3date = date('Y-m-d');
$wpas3_bucket = get_option("WPAdmin_s3_bucket");
file_put_contents(wpas3basedir . $wpas3_bucket . ".txt",$wps3date);

$this->wpas3_backupdb_s3('Full');
$this->wpas3_backupconfig_s3('Full');
$this->wpas3_createzip_s3('Full');
$this->wpas3_deletefoldercontent_s3('Inc');
ini_set('max_execution_time', $wpa_current_met);
ini_set('memory_limit', $wpa_current_mem);	
wp_die();
}

function wpas3_createzip_s3($type)
{
$wps3date = date('Y-m-d');
$wpas3_ak = get_option("WPGS_username");
$wpas3_sk = get_option("WPGS_secret");
$wpas3_bucket = get_option("WPAdmin_s3_bucket");
$wpas3_region = get_option("WPAdmin_s3_region");
$wpas3_randnum1 = rand(3,11);
$wpas3_randnum2 = rand(3,11);
$wpas3_rand = substr(sha1(date('YMdHisuv')),$wpas3_randnum1,$wpas3_randnum2);

foreach (glob($_SERVER["DOCUMENT_ROOT"] ."/" . "*.zip") as $del) 
{
unlink($del);
}

foreach (glob($_SERVER['DOCUMENT_ROOT']. "/" . "*.tar.gz") as $del) 
{
unlink($del);
}

if($type == "Inc" && file_exists(wpas3basedir . $wpas3_bucket . ".txt"))
{
$wpas3_modifieddata = file_get_contents(wpas3basedir . $wpas3_bucket . ".txt");
$date = strtotime($wpas3_modifieddata);
$type = "Inc";
}
else
{
$type = "Full";
$date = strtotime("1980-01-01");
file_put_contents(wpas3basedir . $wpas3_bucket . ".txt",$wps3date);
}

$folders = ['themes','plugins','uploads'];

if (!extension_loaded('zip'))
{
echo "<div class='alert alert-warning'><h3>Zip not found on server, will try Tar</h3></div>";

foreach($folders as $folder)
{
$wpa_s3_wpcontentPath = $_SERVER['DOCUMENT_ROOT']  . '/wp-content/' . $folder;
$wpa_s3_zip_file = str_replace('\\', '/',$_SERVER['DOCUMENT_ROOT'] . '/' . $wps3date . $folder . $wpas3_rand.".tar.gz");
$wpa_s3_listedincremental = wpas3basedir . $wpas3_bucket . ".snar";
if($type == "Full") unlink($wpa_s3_listedincremental);
$command = 'tar --create --file='.$wpa_s3_zip_file.' --listed-incremental=' . $wpa_s3_listedincremental. ' --gzip ' . $wpa_s3_wpcontentPath;
shell_exec($command);
$zippedfile = realpath($_SERVER['DOCUMENT_ROOT'].'/'.$wps3date.$folder.$wpas3_rand.".tar.gz");
$preset = [
'Bucket' => $wpas3_bucket,
'SourceFile' => $_SERVER['DOCUMENT_ROOT'] . '/'. $wps3date .$folder.$wpas3_rand. '.tar.gz',
'Key' => $type.'-'.$wps3date.'/'.$wps3date. $folder.'.tar.gz'
];
$wpas3_client = $this->wpas3_authClient($wpas3_ak,$wpas3_sk,$wpas3_region);
$wpas3_client->putObject($preset);	
echo "<div class='alert alert-info'><h3>3/3 <b>$folder</b> Backup Completed</h3></div>";		
unlink($zippedfile);

}
}
else
{
try
{
foreach($folders as $folder)
{
$this->wpas3_compress($folder,$wps3date,$wpas3_rand,$date);
$preset = [
'Bucket' => $wpas3_bucket,
'SourceFile' => $_SERVER['DOCUMENT_ROOT'] . '/'. $wps3date . $folder . $wpas3_rand . '.zip',
'Key' => $type.'-'.$wps3date.'/'. $wps3date .$folder.'.zip'
];
$wpas3_client = $this->wpas3_authClient($wpas3_ak,$wpas3_sk,$wpas3_region);
$wpas3_client->putObject($preset);	
echo "<div class='alert alert-info'><h3>3/3 <b>$folder</b> Backup Completed</h3></div>";		
unlink($zippedfile);
}
}
catch (Exception $e) 
{
$err = $e->getMessage();
$er = explode("response",$err);
echo "<div class='alert alert-danger'>" .$er[0] . "</div>";
}

}

}

function wpas3_compress($folder,$wps3date,$wpas3_rand,$date)
{
$wpa_s3_wpcontentPath = $_SERVER['DOCUMENT_ROOT']  . '/wp-content/' . $folder;
$wpa_s3_zip_file = str_replace('\\', '/',$_SERVER['DOCUMENT_ROOT'] . '/' . $wps3date . $folder. $wpas3_rand.".zip");
$zip = new ZipArchive();
if ($zip->open($wpa_s3_zip_file, ZIPARCHIVE::CREATE)!==TRUE) 
{
exit("cannot open <$wpa_s3_zip_file>\n");
}
$wpa_s3_wpcontentPath = str_replace("\\","/",$wpa_s3_wpcontentPath);
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($wpa_s3_wpcontentPath,RecursiveIteratorIterator::SELF_FIRST)) as $filename)
{

if($filename->getMTime() > $date)
{
$filename = str_replace('\\', '/', $filename);
if( in_array(substr($filename, strrpos($filename, '/')+1), array('.', '..'))) continue;	
$filename = realpath($filename);
if (is_dir($filename) === true)
{
$zip->addEmptyDir(str_replace($wpa_s3_wpcontentPath . '/', '', $filename . '/'));
}
else if (is_file($filename) === true)
{
$wpa_fdata = file_get_contents($filename);
$zip->addFromString(str_replace($wpa_s3_wpcontentPath . '/', '', $filename),$wpa_fdata);
$wpa_fdata = null;
}
}
}
$zip->close();	
if(!file_exists($_SERVER['DOCUMENT_ROOT'] . '/'. $wps3date . $folder . $wpas3_rand .  '.zip'))
{
echo "<div class='alert alert-danger'><h3>Failed to create file</h3></div>";
wp_die();
}
}

function wpas3_backupconfig_s3($type)
{
$wpa_s3_checkconfigpath = $this->wpa_s3_checkconfigpath();	
$wps3date = date('Y-m-d');
$wpas3_ak = get_option("WPGS_username");
$wpas3_sk = get_option("WPGS_secret");
$wpas3_bucket = get_option("WPAdmin_s3_bucket");
$wpas3_region = get_option("WPAdmin_s3_region");
try
{
$wpas3_client = $this->wpas3_authClient($wpas3_ak,$wpas3_sk,$wpas3_region);;
$preset = [
'Bucket' => $wpas3_bucket,
'SourceFile' => $wpa_s3_checkconfigpath['wpa_s3_wpconfig'],
'Key' => $type.'-'.$wps3date.'/' . $wpa_s3_checkconfigpath['wpa_s3_wpconfigfile']
];
$wpas3_client->putObject($preset);
echo "<div class='alert alert-info'><h3>1/3 Config Backup Completed</h3></div>";
}
catch (Exception $e) 
{
$err = $e->getMessage();
$er = explode("response",$err);
echo "<div class='alert alert-danger'>" .$er[0] . "</div>";
}
}

function wpas3_backupdb_s3($type)
{
$wpa_s3_checkconfigpath = $this->wpa_s3_checkconfigpath();
$wps3date = date('Y-m-d');
$wpas3_ak = get_option("WPGS_username");
$wpas3_sk = get_option("WPGS_secret");
$wpas3_bucket = get_option("WPAdmin_s3_bucket");
$wpas3_region = get_option("WPAdmin_s3_region");

if(file_exists($wpa_s3_checkconfigpath['wpa_s3_wpconfig'])) 
{
require($wpa_s3_checkconfigpath['wpa_s3_wpconfig']);
}
else
{
echo "<div class='alert alert-danger'><h3> WP-Config.php not found</h3></div>";
wp_die();
}
try{
$command = 'mysqldump '.DB_NAME.' --password='.DB_PASSWORD.' --user='.DB_USER.' --single-transaction > '.wpas3basedir.$wpas3_bucket.'.sql';
exec($command);
}
catch (Exception $e) 
{
$err = $e->getMessage();
$er = explode("response",$err);
echo "<div class='alert alert-danger'>" .$er[0] . "</div>";
}	
$wpas3_client = $this->wpas3_authClient($wpas3_ak,$wpas3_sk,$wpas3_region);
$preset = [
'Bucket' => $wpas3_bucket,
'SourceFile' => wpas3basedir.$wpas3_bucket.'.sql',
'Key' => $type.'-'.$wps3date.'/'.$wpas3_bucket.'.sql'
];

if(filesize(wpas3basedir.$wpas3_bucket.'.sql') > 0)
{
try
{
$wpas3_client->putObject($preset);
echo "<div class='alert alert-info'><h3>2/3 Database Backup Completed</h3></div>";
}
catch (Exception $e) 
{
$err = $e->getMessage();
$er = explode("response",$err);
echo "<div class='alert alert-danger'>" .$er[0] . "</div>";
}
}
else
{
echo "<div class='alert alert-danger'><h3>Something went wrong!!!<br>".$wpas3_bucket.'.sql'." was created but has no data</h3></div>";
}
$dbfile = realpath(wpas3basedir.$wpas3_bucket.'.sql');
unlink($dbfile);
}

function wpas3_create_s3() 
{
if ( isset($_REQUEST) ) 
{
@$wpas3_ak = $_REQUEST['wpas3_ak'];
@$wpas3_sk = $_REQUEST['wpas3_sk'];
@$wpas3_ap = $_REQUEST['wpas3_ap'];
@$wpas3_ori = $_REQUEST['wpas3_ori'];

$s3domain = site_url();
$s3domain = preg_replace("(^https?://)", "", $s3domain);

if($s3domain != $wpas3_ori)	 $wpas3_ori = $s3domain;

$autoload = "yes";
if(get_option('WPGS_username'))	{	update_option('WPGS_username', $wpas3_ak, $autoload);	}
else 	{	add_option('WPGS_username', $wpas3_ak, $autoload); 	}

if(get_option('WPGS_secret')) 	{	update_option('WPGS_secret', $wpas3_sk, $autoload); 	}
else 	{ 	add_option('WPGS_secret', $wpas3_sk, $autoload); 	}

if(get_option('WPAdmin_s3_region'))	{	update_option('WPAdmin_s3_region', $wpas3_ap, $autoload);	}
else	{	add_option('WPAdmin_s3_region', $wpas3_ap, $autoload);	}

if(get_option('WPAdmin_s3_bucket'))	{	update_option('WPAdmin_s3_bucket', $wpas3_ori, $autoload);	}
else	{	add_option('WPAdmin_s3_bucket', $wpas3_ori, $autoload);	}

$wpas3_client = $this->wpas3_authClient($wpas3_ak,$wpas3_sk,$wpas3_ap);

$preset = [
'Bucket' => $wpas3_ori,
'LocationConstraint' => $wpas3_ap
];

try
{
$result = $wpas3_client->createBucket($preset);
if($result["Location"]) echo "<div class='alert alert-info'>Success: " . $result["Location"] . "</div>";
}
catch (Exception $e) 
{
$err = $e->getMessage();
$er = explode("response",$err);
echo "<div class='alert alert-danger'>" .$er[0] . "</div>";
}
}
wp_die();
}

function wpas3_save_s3() 
{
if ( isset($_REQUEST) ) 
{
@$wps3fbsch = $_REQUEST['wpas3_fbsch'];
@$wps3retain = $_REQUEST['wpas3_retain'];
@$wps3sch = $_REQUEST['wpas3_sch'];
$autoload = "yes";
if($wps3sch == "") $wps3ch = "23:11:00";
if(get_option('WPAdmin_s3_fbsch'))
{update_option('WPAdmin_s3_fbsch', "$wps3fbsch", $autoload);}
else{add_option('WPAdmin_s3_fbsch', "$wps3fbsch", $autoload);}

if(get_option('WPAdmin_s3_retain'))
{update_option('WPAdmin_s3_retain', "$wps3retain", $autoload);}
else{add_option('WPAdmin_s3_retain', "$wps3retain", $autoload);}

if(get_option('WPAdmin_s3_sch'))
{update_option('WPAdmin_s3_sch', "$wps3sch", $autoload);}
else
{add_option('WPAdmin_s3_sch', "$wps3sch", $autoload);}

wp_clear_scheduled_hook('wpas3_scheduled_backups');
$wpas3_timestamp = strtotime( $wps3sch ); 
$wpas3_recurrence = 'daily';
$wpas3_args = null;

wp_schedule_event( $wpas3_timestamp, $wpas3_recurrence, 'wpas3_scheduled_backups' );

echo "<div class='alert alert-info'><b>Settings Saved</b></div>";
}
wp_die();
}

function wpas3_deletefoldercontent_s3($type)
{
if ( isset($_REQUEST) ) 
{
@$wpas3_folder = get_option("WPAdmin_s3_bucket");
@$wpas3_object = $type;
$wpas3_ak = get_option("WPGS_username");
$wpas3_sk = get_option("WPGS_secret");
$wpas3_region = get_option("WPAdmin_s3_region");
$wpas3_client = $this->wpas3_authClient($wpas3_ak,$wpas3_sk,$wpas3_region);	
try
{
$wpas3_folders = $wpas3_client->getIterator('ListObjects', array('Bucket' => $wpas3_folder));
foreach ($wpas3_folders as $subfolders) {

if(substr($subfolders['Key'],0,strlen($wpas3_object)) == $wpas3_object)
{
$preset = [
'Bucket' => $wpas3_folder, 
'Key' => $subfolders['Key'], 
];
$wpas3_client->deleteObject($preset);

$preset = [
'Bucket' => $wpas3_folder, 
'Key' => $wpas3_object, 
];
$wpas3_client->deleteObject($preset);

}
}
echo "<div class='alert alert-info'><h3>$wpas3_object Backups Deleted</h3></div>";
}
catch (Exception $e) 
{
$err = $e->getMessage();
$er = explode("response",$err);
echo "<div class='alert alert-danger'>" .$er[0] . "</div>";
}

/*limit full backups*/

$wpas3_folders = $wpas3_client->getIterator('ListObjects', array('Bucket' => $wpas3_folder));
$wpa_s3_found = 0;

try{
$wpa_s3_folderlist= [];
foreach ($wpas3_folders as $subfolders) {
if(!in_array(substr($subfolders['Key'],0,strpos($subfolders['Key'],"/")),$wpa_s3_folderlist))
{

$wpa_s3_object = substr($subfolders['Key'],0,strpos($subfolders['Key'],"/"));
array_push($wpa_s3_folderlist, $wpa_s3_object );
$wpa_s3_found = 1;
}
}

$retain = get_option('WPAdmin_s3_retain');
if($retain == "" || $retain == 0) $retain =1;
if(count($wpa_s3_folderlist) > $retain) 
{
	$retain = count($wpa_s3_folderlist) - $retain;
$i = 0;
while($i < $retain)
{
$wpas3_folders = $wpas3_client->getIterator('ListObjects', array('Bucket' => $wpas3_folder));
foreach ($wpas3_folders as $subfolders) {
if(stripos($subfolders['Key'],$wpa_s3_folderlist[$i]) > -1)
{
$preset = [
'Bucket' => $wpas3_folder, 
'Key' => $subfolders['Key'], 
];
$wpas3_client->deleteObject($preset);
}
}

$preset = [
'Bucket' => $wpas3_folder, 
'Key' => $wpa_s3_folderlist[$i], 
];
$wpas3_client->deleteObject($preset);

$i++;
}
}

}
catch (Exception $e) 
{
$err = $e->getMessage();
$er = explode("response",$err);
echo "<div class='alert alert-danger'>" .$er[0] . "</div>";
}

}

wp_die();
}

function wpas3_listfoldercontent_s3()
{
if ( isset($_REQUEST) ) 
{
@$wpas3_folder = $_REQUEST['folder'];	
echo "<h3><b>$wpas3_folder</b></h3>";
$wpas3_ak = get_option("WPGS_username");
$wpas3_sk = get_option("WPGS_secret");
$wpas3_region = get_option("WPAdmin_s3_region");
$wpas3_client = $this->wpas3_authClient($wpas3_ak,$wpas3_sk,$wpas3_region);

$wpas3_folders = $wpas3_client->getIterator('ListObjects', array('Bucket' => $wpas3_folder));
$wpa_s3_found = 0;

try{
$wpa_s3_folderlist= [];
foreach ($wpas3_folders as $subfolders) {
if(!in_array(substr($subfolders['Key'],0,strpos($subfolders['Key'],"/")),$wpa_s3_folderlist))
{

$wpa_s3_object = substr($subfolders['Key'],0,strpos($subfolders['Key'],"/"));
array_push($wpa_s3_folderlist, $wpa_s3_object );
echo "<b><a class=deleteobjects data-id=".$wpas3_folder." data-object=".$wpa_s3_object." href='javascript:void(0)'><img src='".wpas3PLUGIN_URL."admin/asset/images/delete.png' title='Delete folder' alt='Delete Folder' width=40px></a>"	. $wpa_s3_object . "</b>";
$wpa_s3_found = 1;
}
}
if($wpa_s3_found == 0){
echo "No Folders Found"	;
}
}
catch (Exception $e) 
{
$err = $e->getMessage();
$er = explode("response",$err);
echo "<div class='alert alert-danger'>" .$er[0] . "</div>";
}	
}
wp_die();
}

function wpas3_delete_s3()
{
if ( isset($_REQUEST) ) 
{
@$wpas3_ak = $_REQUEST['wpas3_ak'];
@$wpas3_sk = $_REQUEST['wpas3_sk'];
@$wpas3_ap = $_REQUEST['wpas3_ap'];
@$wpas3_ori = $_REQUEST['wpas3_ori'];
$wpas3_client = $this->wpas3_authClient($wpas3_ak,$wpas3_sk,$wpas3_ap);
$preset = ['Bucket' => $wpas3_ori];
try
{
$result = $wpas3_client->deleteBucket($preset);
echo "<div class='alert alert-info'>Folder <b>$wpas3_ori</b> deleted</div>";
}
catch (Exception $e) 
{
$err = $e->getMessage();
$er = explode("response",$err);
echo "<div class='alert alert-danger'>" .$er[0] . "</div>";
}
}
wp_die();
}

function wpas3toplevel_page() {
echo "<p><h2>" . __( 'WPAdmin AWSS4 Backup Configuration', 'wpas3_menu' ) . "</h2><p>";
require_once(wpas3basedir . "admin/wpa-awss4-admin.php");
}

function wpas3_enqueue_style() 
{
if ( ! wp_style_is( 'style', 'done' ) ) 
{
wp_deregister_style( 'style' );
wp_dequeue_style( 'style' );
$style_fp = get_stylesheet_directory() . '/style.css';
if ( file_exists($style_fp) ) 
{
wp_enqueue_style( 'style', get_stylesheet_uri() . '?' . filemtime( $style_fp ) );
}
}
}

function wpas3_authClient($wpas3_ak,$wpas3_sk,$wpas3_ap)
{
$wpas3_client =  new S3Client([
'version' => 'latest',
'region'  => $wpas3_ap,
'credentials' => [
'key'    => $wpas3_ak,
'secret' => $wpas3_sk
],
'http'    => [
'verify' => wpas3basedir.'admin/cacert.pem'
]
]);	
return $wpas3_client;
}
/*class ends here*/
}
$wpaawss3 = new wpaawss3();
$wpaawss3->load();


add_action( 'wp', 'wpa_s3_activate_cron' );
function wpa_s3_activate_cron()
{
if ( !wp_next_scheduled( 'wpas3_scheduled_backups' ) ) {
date_default_timezone_set('utc');
$wpasch = "23:11:00";
if(get_option("WPAdmin_s3_sch")) $wpasch = get_option("WPAdmin_s3_sch"); 
$wpas3_timestamp = strtotime( $wpasch ); 
$wpas3_recurrence = 'daily';
$wpas3_args = null;

wp_schedule_event( $wpas3_timestamp, $wpas3_recurrence, 'wpas3_scheduled_backups' );
}
}

function wpa_s3_deactivate_cron() {
wp_clear_scheduled_hook('wpas3_scheduled_backups');
}

register_activation_hook( __FILE__, 'wpa_s3_activate_cron' );
register_deactivation_hook(__FILE__, 'wpa_s3_deactivate_cron');


add_action( 'wpas3_scheduled_backups', 'wpas3_scheduled_backups_daily' );
function wpas3_scheduled_backups_daily()
{
if ( ! defined( 'wpas3basedir' ) )
define( 'wpas3basedir', str_replace("\\","/",plugin_dir_path( __FILE__ )) );


$wpa_current_met = ini_get('max_execution_time');
$wpa_current_mem = ini_get('memory_limit') ;
ini_set('max_execution_time', '900');
ini_set('memory_limit', '1024M');
$wps3date = date('Y-m-d');

$wpas3_bucket = get_option("WPAdmin_s3_bucket");

$wpaawss3 = new wpaawss3();
$wpaawss3->load();

$whenfull = get_option("WPAdmin_s3_fbsch");
if($whenfull == date('l') || !file_exists(wpas3basedir . $wpas3_bucket . ".txt"))
{
$wpaawss3->wpas3_backupdb_s3('Full');
$wpaawss3->wpas3_backupconfig_s3('Full');
$wpaawss3->wpas3_createzip_s3('Full');
$wpaawss3->wpas3_deletefoldercontent_s3('Inc');
}
else
{
$wpaawss3->wpas3_backupdb_s3('Inc');
$wpaawss3->wpas3_backupconfig_s3('Inc');
$wpaawss3->wpas3_createzip_s3('Inc');	
}

ini_set('max_execution_time', $wpa_current_met);
ini_set('memory_limit', $wpa_current_mem);

}


add_action( 'wp_ajax_wpas3_delete_s3', array($wpaawss3,'wpas3_delete_s3') );
add_action( 'wp_ajax_wpas3_list_s3', array($wpaawss3,'wpas3_list_s3'));
add_action( 'wp_ajax_wpas3_fullbackup_s3', array($wpaawss3,'wpas3_fullbackup_s3'));
add_action( 'wp_ajax_wpas3_incremental_s3', array($wpaawss3,'wpas3_incremental_s3'));
add_action( 'wp_ajax_nopriv_wpas3_incremental_s3', array($wpaawss3,'wpas3_incremental_s3'));
add_action( 'wp_ajax_wpas3_create_s3', array($wpaawss3,'wpas3_create_s3') );
add_action( 'wp_ajax_wpas3_save_s3', array($wpaawss3,'wpas3_save_s3') );
add_action( 'wp_ajax_wpas3_listfoldercontent_s3', array($wpaawss3,'wpas3_listfoldercontent_s3') );
add_action( 'wp_ajax_wpas3_deletefoldercontent_s3', array($wpaawss3,'wpas3_deletefoldercontent_s3') );
add_action( 'wp_enqueue_style', array($wpaawss3,'wpas3_enqueue_style'), 999 );

?>
