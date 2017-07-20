<?php 
global $ppp_custom_user;
echo $ppp_custom_user->message;
 ?>
<form action="?act=ppp_register" method="post">
	<label>First name</label>
	<input type="text" name="first_name" value="<?php echo (isset($_POST['first_name']))?$_POST['first_name']:''; ?>">
	<label>Last name</label>
	<input type="text" name="last_name" value="<?php echo (isset($_POST['last_name']))?$_POST['last_name']:''; ?>">
	<label>Email</label>
	<input type="text" name="email" value="<?php echo (isset($_POST['email']))?$_POST['email']:''; ?>" >
	<label>Phone</label>
	<input type="text" name="phone" value="<?php echo (isset($_POST['phone']))?$_POST['phone']:''; ?>" >
	<label>Private Banker Name</label>
	<input type="text" name="private_banker_name" value="<?php echo (isset($_POST['private_banker_name']))?$_POST['private_banker_name']:''; ?>">
	<label>Password</label>
	<input type="password" name="password">
	<input type="hidden" name="act" value="ppp_register">
	<input type="submit" name="register">
</form>