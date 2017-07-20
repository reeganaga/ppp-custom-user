<?php 
   /*
   Plugin Name: Private Property Custom login
   Plugin URI: http://agentpoint.com.au
   Description: a plugin to create awesomeness and spread joy
   Version: 0.0.1
   Author: rega@agentpoint.com.au
   Author URI: http://mrtotallyawesome.com
   License: GPL2
   */
  
  /**
  * ppp_custom_user
  * status :
  * pending,approved,rejected,valid
  */
  class ppp_custom_user
  {
  	
  	function __construct()
  	{
			add_shortcode("ppp_load_page", array($this,"ppp_load_page"));
      add_action('init', array($this, 'init'));
      add_action( 'user_register', array($this,'ppp_update_user_meta'), 10, 1 );
      add_filter( 'user_row_actions', array( $this, 'ppp_user_table_action' ), 10, 2 );
      add_filter( 'manage_users_columns', array($this,'ppp_modify_user_column_table') );
      add_filter( 'manage_users_custom_column', array($this,'ppp_modify_user_table_row'), 10, 3 );


			// add_action( 'wp_ajax_checkdirectory', array($this,'checkdirectory') );
      $this->email_admin = 'rega@softwareseni.com' ;
      $this->message = '';
      $this->admin_code = '5zoHg0u9QR8xLb318o9e';
  	}

    function init(){
      if (isset($_POST['act']) && $_POST['act']=='ppp_register') 
        $this->ppp_register_process();
      if (isset($_POST['act']) && $_POST['act']=='ppp_login') 
        $this->ppp_login_process();
      if (isset($_GET['test_email'])) 
        $this->test_email();

      //for activating user
      if (isset($_GET['ppp_code'])) 
        $this->ppp_after_register();

      //for updating user
      if (isset($_POST['act']) && $_POST['act']=='ppp_update_user' && wp_get_current_user()->ID!=0) { //user should login first 
        $this->ppp_user_update();
      }
      

      /*//for accept user
      if (isset($_GET['act']) && $_GET['act']=='accept_user' && isset($_GET['code']) && isset($_GET['user_id'])) 
        $this->ppp_accept_process();*/
    }

    function ppp_user_update(){
      $user=wp_get_current_user();
       $userdata = array(
        'user_email'=>$_POST['email'],
        'ID'=>$user->ID,
      );
      //update user_login
      // $wpdb->update($wpdb->users, array('user_login' => $new_user_login), array('ID' => $user_id));
      // 
      //check if password and current password filled
      if (!empty($_POST['password']) && !empty($_POST['current_password'])) {
        $wp_hasher = new PasswordHash(8, TRUE);

        $password_hashed = $user->data->user_pass;
        $plain_password = $_POST['current_password'];

        //matching password
        if($wp_hasher->CheckPassword($plain_password, $password_hashed)) {
            $userdata['user_pass']=$_POST['password'];
        } else {
            $this->message='Your current password is wrong';
            return;
        }
      }

     
      //update email
      $update = wp_update_user( $userdata );

      if (is_numeric($update)) {
        //success message
        $this->message = 'Your account has been updated';
      }else{
        $message='';
        foreach ($update->errors as $key => $error_type) {
          foreach ($error_type as $key_error => $value_error) {
            $message .=$value_error; 
          }
        }
        //sending error message
        $this->message = $message;
      }
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
      } else if ( $user_status == 'approved' ) {
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
        $data = array(
          'user_pass'=>$user['password'],
          'user_login'=>$user['email'],
          'first_name'=>$user['first_name'],
          'last_name'=>$user['last_name'],
          'user_email'=>$user['email'],
        );
        $insert = wp_insert_user($data);
        if (is_numeric($insert)) {

          //sending email to admin
          
          // wp_new_user_notification( $insert,'', 'both' );
          $user = get_userdata( $insert );
          $user_meta = get_user_meta($insert);
          // var_dump($user_meta);
          $subject = "New Member has registered";
          //message
          $url = $this->ppp_gen_url($user->ID);
          $message = $this->load_email_template('admin-new_member',array('user'=>$user,'user_meta'=>$user_meta,'url'=>$url));
          //sending message to admin
          $send = $this->email($this->email_admin, $subject, $message); 
          //sending message to user
          $subject = get_bloginfo('name')." - Thankyou for your registration";
          $message = $this->load_email_template('user-register',array('user_meta'=>$user_meta));
          $send = $this->email($user->data->user_email, $subject, $message); 
          // var_dump($send);
          $this->message = "You've been registered, your account will be review first by admin";
        }else{
          $message =''; 
          foreach ($insert->errors as $key => $error_type) {
            foreach ($error_type as $key_error => $value_error) {
              $message .=$value_error; 
            }
          }
          $this->message = $message;
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
          var_dump($status);
        if ($status=='pending') {
          $this->message="Your account Not activated";
        }elseif ($status=='approved') {
          $this->message="Please confirm you identify from you email confirmation";
        }elseif ($status=='rejected') {
          $this->message="Your account is rejected by admin";
        }
        if (empty($this->message)) {

          //validation and login user
          $cred = array('email'=>$_POST['email'],'password'=>$_POST['password']);
          $this->ppp_login_validation($cred);
        }
      }else{
        $this->message = "Email is not Exist"; 
      }
      // return $message;
        
    }

    function ppp_update_user_meta( $user_id ) {

      //updating status user for the first time
      update_user_meta($user_id, 'status', 'pending');

      //create hash for activating user 
      $activation_key = strtotime("now").$user_id;
      $activation_key = wp_hash_password($activation_key);
      update_user_meta($user_id, 'activation_key', $activation_key);
      
      if ( isset( $_POST['private_banker_name'] ) )
        update_user_meta($user_id, 'private_banker_name', $_POST['private_banker_name']);
      if ( isset( $_POST['phone'] ) )
        update_user_meta($user_id, 'phone', $_POST['phone']);
    }

    function test_email (){
      $activate_key = strtotime("now").'123';
      var_dump($activate_key);
      // $headers = array()'Content-Type: text/html; charset=UTF-8';    
      // $send1 = $this->email('rega1@mailinator.com', 'subject11', 'message11'); 
      $url = $this->ppp_gen_url($_GET['id'],true);
      var_dump($url);


      var_dump(get_bloginfo('name'));
      var_dump("From:".get_bloginfo('name')." <".$this->email_admin.">");
      var_dump('sending = '.$send1);
      $message = $this->load_email_template('test',array('user'=>'rega'));
      var_dump($message);

      die();
    }

    function email($to,$subject,$message,$attachment=array()){
      $headers = array(
            "Content-Type: text/html; charset=UTF-8 ",
            "From:".get_bloginfo('name')." <".$this->email_admin.">",
            // "From:rega site <rega.blank@gmail.com>"
          );
      $send = wp_mail($to, $subject, $message ,$headers ,$attachment); 
      return $send;
    }

    function isJson($string) {
     json_decode($string);
     return (json_last_error() == JSON_ERROR_NONE);
    }

    function ppp_after_register(){
      $hash_code = $_GET['ppp_code'];
      $code = base64_decode($hash_code);
      $arr_code = json_decode($code,true);
      // var_dump($arr_code);
      if (!$this->isJson($code)) {
        $this->ppp_force_404();
      }
      // var_dump($this->isJson($code));
      // die();
      $user_id = $arr_code['user_id'];
      $code = $arr_code['code'];
      $data_user = get_user_by('ID',$user_id);
      $type_act = $arr_code['act'];
      $admin_code = isset($arr_code['admin_code'])?$arr_code['admin_code']:'';
      $admin_mode = (isset($arr_code['ppp_admin_mode']))?$arr_code['ppp_admin_mode']:'';

      if (!$data_user) { // if data user not existed
        // global $wp_query;
        // $wp_query->set_404();
        $this->ppp_force_404();
      }
      else{
        $user_meta = get_user_meta( $data_user->ID );
        //activating process
        // var_dump($user_meta);
        // var_dump($code);
        if ($type_act=='activate_user') {
          //match activation key with code from user
          if ($code == $user_meta['activation_key'][0] && $user_meta['status'][0]=='approved') {
            //updating user meta for validating user
            update_user_meta($user_id, 'status', 'valid');
            //empty activation key user
            // update_user_meta($user_id, 'activation_key', '');

            //loggin user
            //validation and login user
            /*echo "<pre>";
            var_dump($data_user);*/
            
            $cred = array('ID'=>$data_user->ID,'username'=>$data_user->data->user_login);
            $this->ppp_login_validation($cred,true);          
          }else{
            $this->ppp_force_404();
          }
        }elseif ($type_act=='accept_user' && $admin_code == $this->admin_code) {
          if ($code == $user_meta['activation_key'][0]) {
            //updating user meta for validating user
            update_user_meta($user_id, 'status', 'approved');
            //sending email to user
            
            //email confirmation account
            $subject = "Confirmation Account from ".get_bloginfo();
            $url = $this->ppp_gen_url($user_id);
            $message = $this->load_email_template('user-confirmation',array('user_meta'=>$user_meta,'url'=>$url));
            $send = $this->email($data_user->data->user_email, $subject, $message ); 

            // var_dump($send);
            if (!empty($admin_mode) && $admin_mode==1) {
              add_action( 'admin_notices', array($this,'ppp_accept_notice') );
            }else{
              die('You have accept user with email '.$data_user->data->user_email);
            }
          }else
            $this->ppp_force_404();
        }elseif ($type_act=='reject_user' && $admin_code == $this->admin_code) {
          if ($code == $user_meta['activation_key'][0]) {
            //updating user meta for validating user
            update_user_meta($user_id, 'status', 'rejected');
            

            /*//updating user meta remove activation key
            update_user_meta($user_id, 'activation_key', '');*/

            //sending email to user
            
            //email confirmation account
            $subject = "Rejected Account from ".get_bloginfo();
            $message = $this->load_email_template('user-rejected',array('user_meta'=>$user_meta));
            $send = $this->email($data_user->data->user_email, $subject, $message); 
            // var_dump($send);
            if (!empty($admin_mode) && $admin_mode==1) {
              add_action( 'admin_notices', array($this,'ppp_deny_notice') );
            }else{
              die('You have reject user with email '.$data_user->data->user_email);
            }
            
          }else{
            if (!empty($admin_mode) && $admin_mode==1) {
              add_action( 'admin_notices', array($this,'ppp_error_notice') );
            }else{
              $this->ppp_force_404();
            }
          }
        }else{
          if (!empty($admin_mode) && $admin_mode==1) {
            add_action( 'admin_notices', array($this,'ppp_error_notice') );
            // die('Yu are awesome');
          }else{
            $this->ppp_force_404();
          }
        }
      }
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

    function ppp_login_validation($creds,$bypass=false){
      //login process begin
      if ($bypass) {
        wp_set_current_user( $creds['ID'], $creds['username'] );
        wp_set_auth_cookie( $creds['ID'] );
        wp_redirect(site_url());exit;
      }else{
        $creds_login=array();
        $creds_login['user_login'] = $creds['email'];
        $creds_login['user_password'] = $creds['password'];
        $creds_login['remember'] = (isset($creds['remember']))?$creds['remember']:true;
        $user = wp_signon( $creds_login, false );
        if ( is_wp_error($user) ){
          $this->message = $user->get_error_message();
          // die();
        }else{
          wp_redirect(site_url());exit;
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
  }

  $ppp_custom_user = new ppp_custom_user;

 ?>