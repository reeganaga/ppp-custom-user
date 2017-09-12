<?php
   /*
   Plugin Name: Private Property Custom login
   Plugin URI: http://agentpoint.com.au
   Description: create user custom register, login, profile, verification user - accept or reject from admin and email
   Version: 0.0.1
   Author: agentpoint
   Author URI: http://agentpoint.com.au
   License: GPL2
   */

  /**
  * ppp_custom_user
  * status :
  * pending,approved,rejected,valid
  */
  require_once('manage/admin.php');

  class ppp_custom_user
  {

  	function __construct()
  	{
			add_shortcode("ppp_load_page", array($this,"ppp_load_page"));
      add_action('init', array($this, 'init'));
      add_action( 'user_register', array($this,'ppp_update_user_meta_after_register'), 10, 1 );
      add_filter( 'user_row_actions', array( $this, 'ppp_user_table_action' ), 10, 2 );
      add_filter( 'manage_users_columns', array($this,'ppp_modify_user_column_table') );
      add_filter( 'manage_users_custom_column', array($this,'ppp_modify_user_table_row'), 10, 3 );
      add_action('wp_login', array($this,'ppp_login_check'), 10, 2);
      add_filter('ppp_redirect_url_login',[$this,'redirect_url_login']);
      // add_action( 'validate_password_reset' , array($this,'ppp_password_min_length_check') ,10, 2 );

      // add_action('wp_logout', array($this,'ppp_after_logout'), 10, 2);
      // add_filter( 'login_redirect', 'ppp_login_redirect', 10, 3 );

      register_activation_hook(__FILE__, array($this, 'install'));


      $this->default_admin_code = '5zoHg0u9QR8xLb318o9e';
      // add_action( 'wp_ajax_checkdirectory', array($this,'checkdirectory') );
      $this->email_admin = (!empty(get_option( 'ppp_admin_email')) )?get_option( 'ppp_admin_email'):get_option('admin_email') ;
      $this->message = '';
      $this->admin_code = (!empty(get_option('ppp_admin_code')))?get_option('ppp_admin_code'):$this->default_admin_code;
      $this->debug=false;
      $this->email_send=true; // if false it will create log email
      $this->arr_code=array();
      $this->user='';
      $this->logfile_path = plugin_dir_path( __FILE__ ).'log/';
      $this->logfile = 'email.log';
      // $this->logemail = true;

  	}

    function init(){
      if (isset($_POST['act']) && $_POST['act']=='ppp_register')
        $this->ppp_register_process();
      if (isset($_POST['act']) && $_POST['act']=='ppp_login')
        $this->ppp_login_process();


      //for activating user
      if (isset($_GET['ppp_code']))
        $this->ppp_after_register();

      //for updating user
      if (isset($_POST['act']) && $_POST['act']=='ppp_update_user' && wp_get_current_user()->ID!=0) { //user should login first
        $this->ppp_user_update();
      }

      if ($this->debug) { //enabling this function for testing only
        if (isset($_GET['test_email']))
          $this->test_email();
      }
      if(!empty($_GET['custom_logout_message'])){
       add_filter('login_message', array($this,'ppp_login_message'));
      }

      /*//for accept user
      if (isset($_GET['act']) && $_GET['act']=='accept_user' && isset($_GET['code']) && isset($_GET['user_id']))
        $this->ppp_accept_process();*/
    }

    function ppp_login_check( $user_login, $user ) {
      global $wpdb;
      /*echo "<pre>";
        var_dump($user_login);
        var_dump($user);
        echo "</pre>";*/
        if (!in_array( 'administrator', $user->roles )) {
          //check user if not admin
          //get metadata
          $status = get_user_meta( $user->ID, 'status', true );
          if ($status=='pending') {
            $message = 'Your account still pending';
          }elseif ($status=='approved') {
            $message = 'You need to activate your account, check your email for confirmation';
          }elseif ($status=='rejected') {
            $message = 'Your account has been denied by admin';
          }elseif ($status=='valid') {
            //succesfull login,

            //get user page id
            // $user_page_id=$wpdb->get_var("SELECT `ID` FROM $wpdb->posts where `post_content` like '%[ppp_load_page template=user]%' and post_status='publish'");
            //get user page url
            // $url = get_permalink( $user_page_id);
            $url = apply_filters('ppp_redirect_url_login','');
            // var_dump($url);
            //redirect user to member page
            wp_redirect( $url );exit();
          }
          if (isset($message)) { //fail login

            //add message for custom login
            // $this->message=esc_url($message,'');
            // $this->message=$message;
            $this->set_flash_message('logout_msg',$message);
            add_action('wp_logout', array($this,'ppp_after_logout'));
            wp_logout();
            // $WP_Error = new WP_Error();
            // $WP_Error->add('my_error', '<strong>Error</strong>: Something went wrong.');
            //logging out user_
          }
        }
        // die('you are login');
    }

    /**
    * custom filter url after login
    **/
    function redirect_url_login($url){
        global $wpdb;
        if (empty($url)) {
            $user_page_id=$wpdb->get_var("SELECT `ID` FROM $wpdb->posts where `post_content` like '%[ppp_load_page template=user]%' and post_status='publish'");
            //get user page url
            $url = get_permalink( $user_page_id);
            // var_dump($url);
        }
        return $url;
    }

    function ppp_after_logout(){
      wp_redirect( site_url().'/wp-login.php?custom_logout_message='.base64_encode($this->get_flash_message('logout_msg')) );
      exit();
    }

    //If the custom parameter is set display the message on the login screen
    function ppp_login_message() {
       $message = '<p class="message">'.base64_decode($_GET['custom_logout_message']).'</p><br />';
       return $message;
    }

    function install(){
      //setting default option
      update_option('ppp_admin_email' , get_option('admin_email' ));
      update_option('ppp_admin_code' , $this->default_admin_code);

      //generate default page
      $this->create_page();
    }

    function create_page(){
      global $wpdb;
      $arr_page = array('login','register','user','verification');

      //create new page
      foreach ($arr_page as $page) {
        $post_parent=$wpdb->get_var("select ID from $wpdb->posts where post_content='[ppp_load_page template=".$page."]' and post_status='publish' and post_type='page' order by ID limit 1");
        if(!$post_parent){
          // wp_insert_post(array('post_title'=>$page, 'post_content'=>'[ppp_load_page template='.$page.']'), true);
          wp_insert_post(array( 'post_status' => 'publish', 'post_type' => 'page', 'post_title'=>ucwords(strtolower($page)), 'post_content'=>'[ppp_load_page template='.$page.']'));
        }
      }

    }

    function ppp_user_update(){
      $user=wp_get_current_user();
       $userdata = array(
        'user_email'=>$_POST['email'],
        'ID'=>$user->ID,
      );
      //update user_login
      // $wpdb->update($wpdb->users, array('user_login' => $new_user_login), array('ID' => $user_id));


      //checking password minimum length
      /*$password_length = $this->minimum_password($_POST['password']);
      if (!$password_length) {
        // $error .= '<p>Minimum password is 8 character</p>';
        $this->set_flash_message('user','<p>Minimum password is 8 character</p>');
        return;
      }*/

      //checking week password
      if ($this->check_weak_password($user['email'],$user['password'])) {
        $this->set_flash_message('user','<strong>ERROR</strong>: Your password is too week');
      }

      //check if password and current password filled
      if (!empty($_POST['password']) && !empty($_POST['current_password'])) {
        require_once( ABSPATH . 'wp-includes/class-phpass.php' );
        $wp_hasher = new PasswordHash(8, TRUE);

        $password_hashed = $user->data->user_pass;
        $plain_password = $_POST['current_password'];

        //matching password
        if($wp_hasher->CheckPassword($plain_password, $password_hashed)) {
            $userdata['user_pass']=$_POST['password'];
        } else {
            // $this->message='Your current password is wrong';
            $this->set_flash_message('user','<p>Your current password is wrong</p>');
            return;
        }
      }elseif (!empty($_POST['password']) && empty($_POST['current_password'])) {
        // $this->message.='<p>You should add current password</p>';
        $this->set_flash_message('user','<p>Your current password is wrong</p>');
      }elseif (empty($_POST['password']) && !empty($_POST['current_password'])) {
        // $this->message.='<p>You should add new password</p>';
        $this->set_flash_message('user','<p>You should add new password</p>');
      }



      //update user
      $update = wp_update_user( $userdata );

      if (is_numeric($update)) {
        //update user succefully
        //now update user meta
        $user_meta = array(
          'first_name'=>$_POST['first_name'],
          'last_name'=>$_POST['last_name'],
          'nickname'=>$_POST['email'],
          'private_banker_name'=>$_POST['private_banker_name'],
        );
        $update_meta = $this->update_user_meta_key($user->ID,$user_meta);
        if ($update_meta!=true) { //there is error
          $this->set_flash_message('user',$update_meta);
        }else{
          $this->set_flash_message('user','<p>Your account has been updated</p>');
        }
        //success message
        // $this->message .= 'Your account has been updated';
      }else{
        $message='';
        foreach ($update->errors as $key => $error_type) {
          foreach ($error_type as $key_error => $value_error) {
            $message .=$value_error;
          }
        }
        //sending error message
        // $this->message = $message;
        $this->set_flash_message('user',$message);
      }
    }

    //updating user meta
    function update_user_meta_key($user_id,$data){
      $error='';
      foreach ($data as $key => $value) {
        $send = update_user_meta( $user_id, $key, $value );
        if (!$send) {
          $error .="<p>".str_replace('_',' ', $key)." cannot be updated</p>";
        }
        /*if (!$send) {
          $this->message.='<p>'.$key.' failed to updated</p>';
        }*/
      }
      if (empty($error))
        return true;
      else return $error;

    }

    function ppp_user_table_action( $actions, $user ) {
      if ( $user->ID == get_current_user_id() )
        return $actions;

      // $user_status = pw_new_user_approve()->get_user_status( $user->ID );
      $user_status = $this->ppp_check_user_status($user->ID);

      $approve_link = add_query_arg( array( 'action' => 'approve', 'user' => $user->ID ) );
      $approve_link = remove_query_arg( array( 'new_role' ), $approve_link );
      $approve_link = wp_nonce_url( $approve_link, 'new-user-approve' );

      $deny_link = add_query_arg( array( 'action' => 'deny', 'user' => $user->ID ) );
      $deny_link = remove_query_arg( array( 'new_role' ), $deny_link );
      $deny_link = wp_nonce_url( $deny_link, 'new-user-approve' );

      $url = $this->ppp_gen_url($user->ID,true);
      $approve_action = '<a href="' . esc_url( $url['accept_url'] ) . '">' . __( 'Approve ', 'new-user-approve' ) . '</a>';
      $deny_action = '<a href="' . esc_url( $url['reject_url'] ) . '">' . __( 'Deny', 'new-user-approve' ) . '</a>';

      if ( $user_status == 'pending' ) {
        $actions[] = $approve_action;
        $actions[] = $deny_action;
      } else if ( $user_status == 'approved' || $user_status == 'valid' ) {
        $actions[] = $deny_action;
      } else if ( $user_status == 'rejected' ) {
        $actions[] = $approve_action;
      }

      return $actions;
    }

    function ppp_check_user_status($user_id){
      // $user = get_userdata( $user_id );
      $user_meta = get_user_meta( $user_id, 'status', true );
      return $user_meta;
    }

    //generate shortcode to load page
  	function ppp_load_page($atts, $content = null){
  		extract(shortcode_atts(array(
        'template' => ''), $atts));
      $file = plugin_dir_path( __FILE__ ).'template/'.$template.'.php';

      if (!empty($template)) {
        ob_start();
        if (file_exists($file)) {
          require 'template/'.$template.'.php';
        }else{
          echo "Template Not found";
        }
        return ob_get_clean();
      }else{
        return false;
      }
  	}

    //handle form register process
    function ppp_register_process(){
      global $wpdb, $wp_hasher;
        // var_dump($_POST);
        $user=$_POST;

        //checking required field
        $arr_required = array('first_name','last_name','email','password','re_password','private_banker_name','private_banker_email_address');

        $error = '';
        foreach ($arr_required as $req ) {
          if (empty($user[$req])) {
            //retrive error message
            $error .= '<p>'.str_replace('_', ' ', $req).' is required'.'<p>';
          }
        }

        //checking validation banker
        $banker_list = get_option('ppp_banker_list');
        if ($banker_list) {
          $arr_banker_list = explode(',', $banker_list);
          if (!in_array($user['private_banker_email_address'], $arr_banker_list)) {
            //set error message
            $error .= '<p> banker email : '.$user['private_banker_email_address'].' is not valid<p>';
          }
        }

        //checking password minimum length
        /*$password_length = $this->minimum_password($user['password']);
        if (!$password_length) {
          $error .= '<p>Minimum password is 8 character</p>';
        }*/

        //checking week password
        $week_password = $this->check_weak_password($user['email'],$user['password']);
        if ($week_password) {
          $error .='<strong>ERROR</strong>: Your password is too week';
        }

        // var_dump($week_password);die();

        if (empty($error)) { //registering user
          $data = array(
            'user_pass'=>$user['password'],
            'user_login'=>$user['email'],
            'first_name'=>$user['first_name'],
            'last_name'=>$user['last_name'],
            'user_email'=>$user['email'],
          );
          $insert = wp_insert_user($data);
        }
        else {
          //returning error message
          $this->set_flash_message('register',$error);
          return;
        }

        if (is_numeric($insert)) { //succefully register

          //sending email to admin

          // wp_new_user_notification( $insert,'', 'both' );
          $user = get_userdata( $insert );
          $user_meta = get_user_meta($insert);
          // var_dump($user_meta);
          $subject = "New Member has registered";
          //message
          $url = $this->ppp_gen_url($user->ID);
          $message = $this->load_email_template('admin-new_member',array('user'=>$user,'user_meta'=>$user_meta,'url'=>$url));
          //sending message to admin and cc banker
          $send = $this->email($this->email_admin,array($user_meta['private_banker_email_address'][0]), $subject, $message);

          //sending passcode message to banker -> not use yet
          /*$subject = 'New Passcode for user '.$user->data->user_email;
          $message = $this->load_email_template('banker-new_passcode',array('user_meta'=>$user_meta));
          $send = $this->email($user_meta['private_banker_email_address'][0],'', $subject, $message); */

          //sending message to user
          $subject = get_bloginfo('name')." - Thankyou for your registration";
          $message = $this->load_email_template('user-register',array('user_meta'=>$user_meta));
          $send = $this->email($user->data->user_email,'', $subject, $message);
          // var_dump($send);
          // $this->message = "You've been registered, your account will be review first by admin";
          $this->set_flash_message('register_success',"<p>Thank you for registering. This site requires all users to be relationship managed high net worth customers. An email will be sent to your Private Banker to confirm that you are relationship managed and then you will be granted access to this site. Your privacy is of utmost importance to us and by verifying all users, maintains the exclusive use and privacy of this platform</p>");
        }else{
          $message ='';
          foreach ($insert->errors as $key => $error_type) {
            foreach ($error_type as $key_error => $value_error) {
              $message .=$value_error;
            }
          }
          $this->set_flash_message('register',$message);
          // $this->message = $message;
          // var_dump($insert->errors);
        }
        // die();
    }

    function load_email_template($template,$data=''){
      $file = plugin_dir_path( __FILE__ ).'email/'.$template.'.php';
      // var_dump($file);
      if (file_exists($file)) {

        ob_start();
        if ($data!='') {
          extract($data);
        }
        include $file;
        $content = ob_get_clean();
      }else{
        $content = 'Template not found';
      }
      return $content;
    }

    function ppp_login_process(){
      $message='';
      $data_user = get_user_by('email',$_POST['email']);
      // var_dump($_POST['email']);
      // var_dump($data_user);
      if ($data_user) {
        $status = get_user_meta( $data_user->ID, 'status', true );
          // var_dump($data_user);
          // var_dump($status);
        if ($status=='pending') {
          $this->set_flash_message('login',"Your account is still waiting approval");
        }elseif ($status=='approved') {
          $this->set_flash_message('login',"Please activate you identify from you email confirmation");
        }elseif ($status=='rejected') {
          $this->set_flash_message('login',"Your account is rejected by admin");
        }
        if (empty($this->get_flash_message('login'))) {

          //validation and login user
          $cred = array('email'=>$_POST['email'],'password'=>$_POST['password']);
          $this->ppp_login_validation($cred);
        }
      }else{
        $this->set_flash_message('login',"Email is not Exist");
      }
      // return $message;

    }

    function gen_activation_key($user_id=''){
      $activation_key = strtotime("now").$user_id;
      $activation_key = wp_hash_password($activation_key);
      return $activation_key;
    }
    function ppp_update_user_meta_after_register( $user_id ) {

      /*//updating status user for the first time
      update_user_meta($user_id, 'status', 'pending');*/

      //create hash for activating user
      $activation_key = $this->gen_activation_key($user_id);

      // update_user_meta($user_id, 'activation_key', $activation_key);

      /*if ( isset( $_POST['private_banker_name'] ) )
        update_user_meta($user_id, 'private_banker_name', $_POST['private_banker_name']);
      if ( isset( $_POST['phone'] ) )
        update_user_meta($user_id, 'phone', $_POST['phone']);*/

      $data_user = get_user_by('ID',$user_id);
      if (!in_array( 'administrator', $data_user->roles )) { //updating meta user if not admin role
        $user_meta = array(
          'private_banker_name'=>(isset( $_POST['private_banker_name'] ))?$_POST['private_banker_name']:'',
          'phone'=>(isset( $_POST['phone'] ))?$_POST['phone']:'',
          'private_banker_ph_number'=>(isset( $_POST['private_banker_ph_number'] ))?$_POST['private_banker_ph_number']:'',
          'private_banker_email_address'=>(isset( $_POST['private_banker_email_address'] ))?$_POST['private_banker_email_address']:'',
          'activation_key'=>$activation_key,
          'status'=>'pending',
          'show_admin_bar_front'=> 'false', //disable toolbar all user when registered
          'banker_code'=> rand ( 0,9999), //generate banker code to continue accept user
        );
        //updating all usermeta
        $this->update_user_meta_key($user_id,$user_meta);
      }
    }

    function test_email (){
      /*$activate_key = strtotime("now").'123';
      var_dump("admin code = ".$this->admin_code);
      var_dump($activate_key);*/

      /*$send1 = $this->email($this->email_admin,'', 'subject12', 'message12');
      var_dump('sending = '.$send1);
      var_dump("admin email = ".$this->email_admin);*/

      //test generate url
      /*$url = $this->ppp_gen_url($_GET['id'],true);
      var_dump($url);*/

      //get user
      /*$data_user = get_user_by('ID',$_GET['id']);
      echo "<pre>";
      var_dump($data_user);
      echo "</pre>";*/

      /*var_dump(get_bloginfo('name'));
      var_dump("From:".get_bloginfo('name')." <".$this->email_admin.">");
      $message = $this->load_email_template('test',array('user'=>'rega'));
      var_dump($message);*/

      global $wpdb;
      $sql = "SHOW COLUMNS FROM wp_users";
      // $query = $wpdb->query($sql,ARRAY_A) or die(mysql_error() . "<br>$sql<br>");
      $query = $wpdb->get_col("DESC wp_users",0);
      // $row = mysql_fetch_assoc($query);
      echo "<pre>";
      foreach ($query as $value) {
        var_dump($value);
      }
      echo "</pre>";
      die();
    }

    function email($to,$cc='',$subject,$message,$attachment=array()){
      $headers = array(
            "Content-Type: text/html; charset=UTF-8 ",
            "From:".get_bloginfo('name')." <".$this->email_admin.">",
            // "From:rega site <rega.blank@gmail.com>"
          );
      if (is_array($cc)) {
        $header_cc ='Cc: ';
        foreach ($cc as $value) {
          $header_cc.=$value.',';
        }
        $header_cc =substr($header_cc, 0,-1);
        $headers[]=$header_cc;
      }
      if ($this->email_send) {
        $send = wp_mail($to, $subject, $message ,$headers ,$attachment);
      }else {
        $send = true;
        //create logfile
        $content = 'Email:'.date('Y-m-d H:i:s').PHP_EOL;
        $content .= 'content: '.PHP_EOL.$message;
        $content .= PHP_EOL.'------------'.PHP_EOL;
        $this->do_log($content);
      }
      return $send;
    }

    function isJson($string) {
     json_decode($string);
     return (json_last_error() == JSON_ERROR_NONE);
    }

    function ppp_after_register(){
      global $wpdb;
      $hash_code = $_GET['ppp_code'];
      $code = base64_decode($hash_code);
      // var_dump($arr_code);
      /*var_dump($code);
      die();*/
      if (!$this->isJson($code)) {
        // $this->ppp_force_404();
        //redirect to verify page to get error message
        $this->ppp_verify_page();
      }
      $arr_code = json_decode($code,true);
      /*var_dump($arr_code);
      die();*/
      //set code to global
      $this->arr_code = $arr_code;
      $user_id = $arr_code['user_id'];
      $code = $arr_code['code'];
      $data_user = get_user_by('ID',$user_id);

      //set user to global
      $this->user = $data_user;

      $type_act = $arr_code['act'];
      $admin_code = isset($arr_code['admin_code'])?$arr_code['admin_code']:'';
      $admin_mode = (isset($arr_code['ppp_admin_mode']))?$arr_code['ppp_admin_mode']:'';

      if (!$data_user) { // if data user not existed
        // global $wp_query;
        // $this->ppp_force_404();

        //redirect to verify page to get error message
        $this->ppp_verify_page();
      }
      else{
        $user_meta = get_user_meta( $data_user->ID );
        //activating process
        // var_dump($user_meta);
        // var_dump($code);
        if ($type_act=='activate_user' && $code == $user_meta['activation_key'][0] && $user_meta['status'][0]=='approved') {
          //match activation key with code from user
          // if () {
            //updating user meta for validating user
            update_user_meta($user_id, 'status', 'valid');
            //empty activation key user
            // update_user_meta($user_id, 'activation_key', '');

            //loggin user
            //validation and login user
            /*echo "<pre>";
            var_dump($data_user);*/

            $cred = array('ID'=>$data_user->ID,'username'=>$data_user->data->user_login);
            //get user page id
            /*$user_page_id=$wpdb->get_var("SELECT `ID` FROM $wpdb->posts where `post_content` like '%[ppp_load_page template=user]%' and post_status='publish'");
            $url = get_permalink( $user_page_id);*/
            //get user page url
            // $redirect = $this->get_url('user');

            //sending admin email to notify this user has been confirm
            $message = $this->load_email_template('admin-user_activated',array('user'=>$data_user,'user_meta'=>$user_meta));
            $this->email($this->email_admin,'','Your user has been Activated',$message);
            //set success message
            //redirect on login page
            $location = $this->get_url('login');
            if (wp_redirect( $location )) {
              $this->set_message('activate_complete','Thanks for activating your account. please sign using your email and password');exit();
            }
            //user auto login after activated
            // $this->ppp_login_validation($cred,true,$redirect);
          /*}else{
            $this->ppp_force_404();
          }*/
        }elseif ($type_act=='accept_user' && $admin_code == $this->admin_code && $code == $user_meta['activation_key'][0]) { // maching admin code link with admin code database
          // if ($code == $user_meta['activation_key'][0]) {
            //updating user meta for validating user
            update_user_meta($user_id, 'status', 'approved');
            //sending email to user

            //email confirmation account
            $subject = "Confirmation Account from ".get_bloginfo();
            $url = $this->ppp_gen_url($user_id);
            $message = $this->load_email_template('user-confirmation',array('user_meta'=>$user_meta,'url'=>$url));
            $send = $this->email($data_user->data->user_email,'', $subject, $message );

            // var_dump($send);
            if (!empty($admin_mode) && $admin_mode==1) {
              add_action( 'admin_notices', array($this,'ppp_accept_notice') );
            }else{
              //redirect to activate url
              // $activation_url = $this->get_url('activation');
              $msg = base64_encode('User with email '.$data_user->data->user_email.' has been accepted ');
              //parameter user_id and code not use yet, maybe for future
              // wp_redirect( $activation_url.'?msg='.$msg.'&user_id='.$user_id.'&code='.$code, $status );exit();
              $this->ppp_verify_page($msg);

              // die('You have accept user with email '.$data_user->data->user_email);
            }
          /*}else
            $this->ppp_force_404();*/
        }elseif ($type_act=='reject_user' && $admin_code == $this->admin_code && $code == $user_meta['activation_key'][0]) { // maching admin code link with admin code database
          // if ($code == $user_meta['activation_key'][0]) {
            //updating user meta for validating user
            update_user_meta($user_id, 'status', 'rejected');
            //updating user meta activation_key to prevent user to activate with old link
            // $activation_key = $this->gen_activation_key($user_id);
            // update_user_meta($user_id, 'activation_key', $activation_key);


            /*//updating user meta remove activation key
            update_user_meta($user_id, 'activation_key', '');*/

            //sending email to user

            //email confirmation account
            $subject = "Rejected Account from ".get_bloginfo();
            $message = $this->load_email_template('user-rejected',array('user_meta'=>$user_meta));
            $send = $this->email($data_user->data->user_email,'', $subject, $message);
            // var_dump($send);
            if (!empty($admin_mode) && $admin_mode==1) {
              add_action( 'admin_notices', array($this,'ppp_deny_notice') );
            }else{
              //redirect to activate url
              /*$activation_url = $this->get_url('activation');
              //parameter user_id and code not use yet, maybe for future
              wp_redirect( $activation_url.'?msg='.$msg.'&user_id='.$user_id.'&code='.$this->get_arr_code()['code'], $status );*/

              $msg = base64_encode('User with email '.$data_user->data->user_email.' has been rejected ');
              $this->ppp_verify_page($msg);

              // die('You have reject user with email '.$data_user->data->user_email);
            }

          /*}else{
            if (!empty($admin_mode) && $admin_mode==1) {
              add_action( 'admin_notices', array($this,'ppp_error_notice') );
            }else{
              $this->ppp_force_404();
            }
          }*/
        }else{
          if (!empty($admin_mode) && $admin_mode==1) { //get error notice for wp-admin
            add_action( 'admin_notices', array($this,'ppp_error_notice') );
            // die('Yu are awesome');
          }else{
            //redirect to verification page for retrive error message
            $this->ppp_verify_page();
          }
        }
      }
    }

    function ppp_verify_page($msg=''){
      //redirect to verification page and display error message
      $activation_url = $this->get_url('verification');
      if (empty($msg)) $msg = base64_encode('Sorry Your link has been expired or cannot be use anymore, Thanks');

      //parameter user_id and code not use yet, maybe for future
      wp_redirect( $activation_url.'?msg='.$msg.'&user_id='.$this->user->ID.'&code='.$this->arr_code['code'] );exit();
    }

    //displaying error in admin
    function ppp_error_notice() {
        ?>
        <div class="error notice is-dismissible">
            <p><?php _e( 'There has been an error updating status user', 'my_plugin_textdomain' ); ?></p>
        </div>
        <?php
    }

    //displaying succefully accept in admin
    function ppp_accept_notice() {
        ?>
        <div class="updated notice is-dismissible">
            <p><?php _e( 'User Has been Accepted!', 'my_plugin_textdomain' ); ?></p>
        </div>
        <?php
    }

    //displaying succefully accept in admin
    function ppp_deny_notice() {
        ?>
        <div class="error notice is-dismissible">
            <p><?php _e( 'User Has been Denied!', 'my_plugin_textdomain' ); ?></p>
        </div>
        <?php
    }

    function ppp_gen_url($user_id,$admin=false){
      $data_user = get_user_by('ID',$user_id);
      if ($data_user) {
        $user_meta = get_user_meta( $data_user->ID );
        // var_dump($user_meta);
        $plain_activation_key = isset($user_meta['activation_key'][0])?$user_meta['activation_key'][0]:'';

        $arr_activation_key = array('act'=>'activate_user','code'=>$plain_activation_key,'user_id'=>$data_user->ID);
        if ($admin) $arr_activation_key['ppp_admin_mode']=1;
        $activation_key = json_encode($arr_activation_key);
        $hash_activation_key = base64_encode($activation_key);

        $arr_accept_key = array('act'=>'accept_user','code'=>$plain_activation_key,'user_id'=>$data_user->ID,'admin_code'=>$this->admin_code);
        if ($admin) $arr_accept_key['ppp_admin_mode']=1;
        $accept_key = json_encode($arr_accept_key);
        $hash_accept_key = base64_encode($accept_key);

        $arr_reject_key = array('act'=>'reject_user','code'=>$plain_activation_key,'user_id'=>$data_user->ID,'admin_code'=>$this->admin_code);
        if ($admin) $arr_reject_key['ppp_admin_mode']=1;
        $reject_key = json_encode($arr_reject_key);
        $hash_reject_key = base64_encode($reject_key);

        if ($admin) {
          $ppp_base_url=get_admin_url('','users.php');
        }else{
          $ppp_base_url=site_url();
        }
        $url = array(
          'activation_url' => $ppp_base_url.'?ppp_code='.$hash_activation_key,
          'accept_url' => $ppp_base_url.'?ppp_code='.$hash_accept_key,
          'reject_url' => $ppp_base_url.'?ppp_code='.$hash_reject_key,
        );
        return $url;
      }else{
        return false;
      }
    }
    function ppp_force_404(){
      status_header( 404 );
      nocache_headers();
      include( get_query_template( '404' ) );
      die();
    }

    function ppp_login_validation($creds,$bypass=false,$url=''){
      //login process begin
      if ($bypass) {
        wp_set_current_user( $creds['ID'], $creds['username'] );
        wp_set_auth_cookie( $creds['ID'] );
        if (!empty($url)) {
          wp_redirect($url);exit;
        }else{
          wp_redirect(site_url());exit;
        }
      }else{
        $creds_login=array();
        $creds_login['user_login'] = $creds['email'];
        $creds_login['user_password'] = $creds['password'];
        $creds_login['remember'] = (isset($creds['remember']))?$creds['remember']:true;
        $user = wp_signon( $creds_login, false );
        if ( is_wp_error($user) ){
          // $this->message = $user->get_error_message();
          $this->set_flash_message('login',$user->get_error_message());
          // die();
        }else{
          if (!empty($url)) {
            wp_redirect($url);exit;
          }else{
            wp_redirect(site_url());exit;
          }
        }
      }
    }


    function ppp_modify_user_column_table( $column ) {
        $column['status'] = 'Status';
        // $column['xyz'] = 'XYZ';
        return $column;
    }

    function ppp_modify_user_table_row( $val, $column_name, $user_id ) {
        switch ($column_name) {
            case 'status' :
                return get_user_meta( $user_id, 'status', true );
                break;
            default:
        }
        return $val;
    }
    //set message for global
    function set_flash_message($name='msg',$content=''){
      $this->message= array($name=>$content);
    }

    function set_message($name='msg',$content){
      // $_SESSION[$name]=$content;
      setcookie($name, $content, time() + 10,COOKIEPATH); // 86400 = 1 day
    }

    function get_message($name='msg'){
      $temp_message = !empty($_COOKIE[$name])?$_COOKIE[$name]:false;

      //destroy message
      /*$pageWasRefreshed = isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
      if($pageWasRefreshed ) {
        if (isset($_COOKIE[$name])) {
          setcookie($name, "", time() -3600 ,COOKIEPATH,COOKIE_DOMAIN,false); // 86400 = 1 day
        }
      }*/
      return $temp_message;
      // setcookie($name, $content, time() + 5, "/"); // 86400 = 1 day
    }

    //get message for global
    function get_flash_message($name){
      // var_dump($this->message);
      if (isset($this->message[$name])) {
        return $this->message[$name];
      }else return false;
    }

    //get url page by template
    function get_url($page){
      global $wpdb;
      //get user page id
      $user_page_id=$wpdb->get_var("SELECT `ID` FROM $wpdb->posts where `post_content` like '%[ppp_load_page template=$page]%' and post_status='publish'");
      //get user page url
      if (!empty($user_page_id)) {
        return get_permalink( $user_page_id);
      }else{
        return false;
      }
    }

    function do_log($msg){
      if ($this->email_send==false) {
        if (!file_exists($this->logfile_path)) { //check log folder
          # code...
          mkdir($this->logfile_path);
          chmod($this->logfile_path, 0755);
        }else chmod($this->logfile_path, 0755);

        //create logfile or open it
        if (!file_exists($this->logfile_path.$this->logfile)) { //check log file
          $handle = fopen($this->logfile_path.$this->logfile, 'w');
          //write logfile
          fwrite($handle, $msg);
          // file_put_contents($this->logfile_path.$this->logfile, $msg);
        }else{
          $handle = fopen($this->logfile_path.$this->logfile, 'a');
          //write logfile
          // file_put_contents($this->logfile_path.$this->logfile, $msg);
          fwrite($handle, $msg);
        }

      }

    }

    /*function ppp_password_min_length_check( $errors, $user){
        if(strlen($_POST['password']) < 8)
            $errors->add( 'password_too_short', 'ERROR: password is too short. Minimum is 8' );
    }*/

    /*function minimum_password($password){
      if (strlen($password) < 8) {
        return false;
      }else return true;
    }*/

    //check week password, it come from zoo updater
    function check_weak_password($username,$password){
      $forbidden = array(
      $username,
        '123',
        '1234',
        '12345',
        '123456',
        '1234567',
        '12345678',
        '123456789',
        '1234567890',
        '654321',
        'admin',
        'weak liver',
        'password',
        'admin123',
        '123123',
        'abc123',
        'qwerty',
        '111111',
        'iloveyou',
        'master',
        'password1',
        'pass',
        'qazwsx',
        'administrator',
        'qwe123',
        'root',
        'adminadmin',
        'monkey',
        'dragon',
        'letmein',
        'trustno1',
        'superman',
        'admin1',
      );
      if (in_array(strtolower($password), $forbidden)){
        return true;
      }else return false;
    }
  }

  $ppp_custom_user = new ppp_custom_user;

 ?>
