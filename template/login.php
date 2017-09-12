<?php
global $ppp_custom_user;
echo $ppp_custom_user->get_flash_message('login');
 ?>
<?php if ($ppp_custom_user->get_message('activate_complete')): ?>
	<p><?php echo $ppp_custom_user->get_message('activate_complete'); ?></p>
<?php else: ?>
	<p>Please sign in using your email address and chosen password</p>
<?php endif ?>
<form action="" method="post">
    <div class="signin-section">
      <ul class="list-unstyled">
    	<li><label>Email address</label>
    	<input required="required" type="text" name="email" value="<?php echo (isset($_POST['email']))?$_POST['email']:''; ?>" ></li>
    	<li><label>Password</label>
    	<input required="required" type="password" name="password"></li>
    	<input type="hidden" name="act" value="ppp_login">
    	<li><input type="submit" name="register" value="Sign In"></li>
      </ul>
    	<div class="login-options">
         <div class="float-sm-left">Don't have an account? <a class="register" href="<?php echo $ppp_custom_user->get_url('register'); ?>">Sign up</a></div>
    	   <a href="<?php echo wp_lostpassword_url( site_url() ); ?>" class="forgot-password float-sm-right d-inline-block">Forgot password?</a>
         <div class="clear"></div>
      </div>
    </div>
    <div class="register-section d-none">
        <p>New User</p>
        <a class="btn btn-ppp button" href="<?php echo $ppp_custom_user->get_url('register'); ?>">Sign Up</a>
    </div>
</form>
<?php /* if (!$ppp_custom_user->get_message('activate_complete')): ?>
	<p>New user</p>
	<a href="<?php echo $ppp_custom_user->get_url('register'); ?>" class="button">Register</a>
<?php endif */ ?>
