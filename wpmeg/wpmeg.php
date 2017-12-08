<?php
/*
Plugin Name:  مخزن فارسی وردپرس
Plugin URI:   https://wpmeg.com
Description:  افزونه ای برای نصب مستقیم قالب و افزونه از وبسایت WPMeg.com
Version:      1
Author:       علی فرجی
Author URI:   https://wpmeg.com/author/ali
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

//include( plugin_dir_path( __FILE__ ) . 'library/zipadmin.php');

add_action( 'admin_menu', 'wpmeg_admin_menu' );


function wpmeg_admin_menu() {
  add_menu_page( 'مخزن فارسی وردپرس', 'مخزن وردپرس', 'manage_options', 'wpmeg', 'wpmeg_admin_page', plugin_dir_url(__FILE__) . 'images/wpmeg.png', 6  );
}

function wpmeg_admin_page(){
  $api = $_POST['wpmeg_api'];

  if(!empty($_POST['submit'])) {
    update_option('wpmeg_api', $api);
  }

	?>
	<div class="wrap">
		<h2>دانلود قالب و افزونه از WPMeg.com</h2>
    <?php if(!isset($_GET['mode'])) {  ?>
  <form action="<?php echo esc_url( admin_url('admin.php?page=wpmeg') ); ?>" method="post">
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row"><label>کلید API </label></th>
          <td><input name="wpmeg_api" type="text" value="<?php echo get_option('wpmeg_api'); ?>" class="regular-text ltr">
          <p class="description">برای دریافت کلید API <a href="https://wpmeg.com/api.php?mode=add_api" target="_new">اینجا کلیک کنید.</a></p></td>
        </tr>
        <tr>
          <th scope="row"><label>آدرس به پنل مدیریت: </label></th>
          <td><input type="url" value="<?php echo admin_url(); ?>" class="regular-text ltr" title="لینک به کنترل پنل سایت شما این فیلد می باشد." readonly>
          <p class="description">آدرس پنل مدیریت شما این عبارت می باشد، درحین <a href="https://wpmeg.com/api.php?mode=add_api">اضافه کردن API</a> این عبارت را وارد کنید.</p></td>
        </tr>
      </tbody>
    </table>
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="ذخیره&zwnj;ی تغییرات"></p>
  </form>
	</div>
	<?php
}

  $api = get_option('wpmeg_api');
  $mode = $_GET['mode'];
  $fileid = $_GET['file'];
  $confirm = $_GET['confirm'];

  $jsondata = file_get_contents('https://wpmeg.com/api.php?file='.$fileid.'&api='.$api.'&url='.get_site_url());
  $response = json_decode($jsondata, true);
  $res_title = $response['title'];
  $res_download = $response['download'];
  $res_support = $response['support'];
  $type =  $response['type'];
  $api_error =  $response['api_error'];

  if ($api_error) {
    wpmeg_error('notice-error', $api_error);
    die();
  }
    if(isset($mode) && $mode === 'file' && isset($type)) {
        if($type == 'theme' || $type == 'plugin') {

          if($res_download == null || $res_download == "") {
            wpmeg_error('notice-error', 'چنین فایلی وجود ندارد!');
            die();
          }

          if($confirm == null) {
            ?>
            <div class="notice notice-success">
              <p>
              <h2>تایید نصب</h2>
              آیا از تایید نصب "<?php echo $res_title; ?>" مطمئن هستید؟
              <a href=" <?php echo $current_url="//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>&confirm=1">بله!</a>
            </p>
            </div>
            <?php
          }
          elseif ($confirm == 1) {
          wpmeg_unzip($type, $res_title, $res_download, $res_support);
          }
        } else {
        wpmeg_error('notice-error', 'نوع فایل معتبر نیست!');
      }
    }
}

function wpmeg_unzip($type, $res_title, $res_download, $res_support) {  // تایید و آنزیپ کردن فایل درخواستی
    $filesdir = ($type == 'plugin') ? ABSPATH.'wp-content/plugins' : get_theme_root(); // محل پوشه های قالب و افزونه براساس درخواست
    $url = 'https://www.wpmeg.com/download/'.$res_download; // لینک دانلود درخواستی
    $zipFile = "demo/wp-content/test/wpmeg.zip"; // فایل زیپ موقتی که در پایان عملیات حذف خواهد شد

    $zipResource = fopen($zipFile, "w");

    if (!wpmeg_check_curl()) {
      wpmeg_error('notice-error', 'Curl در سرور شما نصب نشده است!');
      die();
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FILE, $zipResource);

    $page = curl_exec($ch);

      if(!$page) {
       wpmeg_error('notice-error', curl_error($ch));
       die();
      }

    curl_close($ch);


    $zip = new ZipArchive;
    $extractPath = $filesdir;

      if($zip->open($zipFile) != "true"){
       wpmeg_error('notice-error', 'فایل زیپ قابل دسترسی نیست.');
       die();
      }

    $pathlist = array();


      $i = 0;
      while ($info = $zip->statIndex($i)) {
          $folder_render = explode('/', $info['name']);
          $foldercount[] = $folder_render[0];
          $pathlist[] = $info['name'];
        $i++;
        }

        $is_single = count($pathlist, 1); // کنترل انکه فایل زیپ شامل فقط یک فایل بدون فولدر نباشد
        $is_istandard = count(array_unique($foldercount)); // کنترل اینکه روت فایل زیپ فقط شامل یک فولدر است (استاندارد وردپرس) و...

      if($is_single == 1 || $is_istandard > 1) {
        wpmeg_error('notice-error', 'فایل مورد نظر معتبر نمی باشد. لطفا فایل را به مدیریت WPMeg.com گزارش دهید.');
        die();
      }

        $parent = explode('/', $pathlist[0]);
        $filename = str_replace('/','',$parent[0]); // به دست آورن نام فولدر روت
        $dirs = array_filter(glob($filesdir.'/*'), 'is_dir');
        $files = array_map('basename', $dirs);

        if (in_array($filename, $files)) {
          if ($type == 'plugin') {
            wpmeg_error('notice-warning', 'اخطار: افزونه ای با همین نام در وردپرس این سایت نصب شده است، برای نصب، ابتدا باید آن را پاک کنید.');
          }
          if ($type == 'theme') {
            wpmeg_error('notice-warning', 'اخطار: قالبی با همین نام در وردپرس این سایت نصب شده است، برای نصب، ابتدا باید آن را پاک کنید.');
          }
          die();
        }

    $zip->extractTo($extractPath); // آنزیپ
    $zip->close();
    unlink($zipFile); // پاک کردن فایل زیپ موقتی
    if($type == 'plugin') {
      ?>
      <div class="notice notice-success is-dismissible"><p>افزونه "<?php echo $res_title; ?>" با موفقیت بارگذاری شد. برای فعال سازی <a href="<?php echo admin_url(); ?>plugins.php?plugin_status=inactive">اینجا کلیک کنید.</a>
      <br /> <br />
      <a href="<?php echo $res_support; ?>" target="_new">پشتیبانی از این افزونه</a>
      </p>
      </div>
      <?php
    }
    if($type == 'theme') {
      ?>
      <div class="notice notice-success is-dismissible"><p>قالب "<?php echo $res_title; ?>" با موفقیت بارگذاری شد. برای فعال سازی <a href="<?php echo admin_url(); ?>themes.php">اینجا کلیک کنید.</a>
      <a href="<?php echo $res_support; ?>" target="_new">پشتیبانی از این قالب</a>
      </p></div>

      <?php
    }
}

function wpmeg_error($type, $text) {
    ?>
    <div class="notice <?php echo $type; ?> is-dismissible">
        <p><?php echo $text; ?></p>
    </div>
    <?php
}

function wpmeg_check_curl() {
  if  (in_array ('curl', get_loaded_extensions())) {
      return true;
  }
  else {
      return false;
  }
}
?>
