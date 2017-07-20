<?php 
global $ppp_custom_user;
echo $ppp_custom_user->message;
 ?>
<form action="" method="post">
	<label>Email</label>
	<input type="text" name="email" value="<?php echo (isset($_POST['email']))?$_POST['email']:''; ?>" >
	<label>Password</label>
	<input type="password" name="password">
	<input type="hidden" name="act" value="ppp_login">
	<input type="submit" name="register">
</form>