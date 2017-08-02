<?php 
global $ppp_custom_user;
echo $ppp_custom_user->get_flash_message('login');
 ?>
<?php if ($ppp_custom_user->get_message('activate_complete')): ?>
	<p><?php echo $ppp_custom_user->get_message('activate_complete'); ?></p>
<?php else: ?>
	<h4>please sign in using your email address and chosen password</h4>
<?php endif ?>
<form action="" method="post">
	<label>Email address</label>
	<input required="required" type="text" name="email" value="<?php echo (isset($_POST['email']))?$_POST['email']:''; ?>" >
	<label>Password</label>
	<input required="required" type="password" name="password">
	<input type="hidden" name="act" value="ppp_login">
	<input type="submit" name="register" value="Sign In">
	<a href="<?php echo wp_lostpassword_url( site_url() ); ?>">Forgot password?</a>

</form>
<?php if (!$ppp_custom_user->get_message('activate_complete')): ?>
	<p>New user</p>
	<a href="<?php echo $ppp_custom_user->get_url('register'); ?>" class="button">Register</a>
<?php endif ?>