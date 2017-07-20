<?php 
global $ppp_custom_user;
echo $ppp_custom_user->message;
$current_user = wp_get_current_user();
 ?>
<?php if ($current_user->ID!=0): 
$meta_data = get_user_meta( $current_user->ID );
echo "<pre>";
var_dump($current_user);
var_dump($meta_data);
echo "</pre>";
?>
	
<form action="?act=ppp_update_user" method="post">
	<label>First name</label>
	<input type="text" name="first_name" value="<?php echo (isset($meta_data['first_name'][0]))?$meta_data['first_name'][0]:''; ?>">
	<label>Last name</label>
	<input type="text" name="last_name" value="<?php echo (isset($meta_data['last_name'][0]))?$meta_data['last_name'][0]:''; ?>">
	<label>Email</label>
	<input type="text" name="email" value="<?php echo (isset($current_user->data->user_email))?$current_user->data->user_email:''; ?>" >
	<label>Phone</label>
	<input type="text" name="phone" value="<?php echo (isset($meta_data['phone'][0]))?$meta_data['phone'][0]:''; ?>" >
	<label>Private Banker Name</label>
	<input type="text" name="private_banker_name" value="<?php echo (isset($meta_data['private_banker_name'][0]))?$meta_data['private_banker_name'][0]:''; ?>">
	<label>New Password</label>
	<input type="password" name="password">
	<label>Current Password</label>
	<input type="password" name="current_password">
	<input type="hidden" name="act" value="ppp_update_user">
	<input type="submit" name="register">
</form>
<?php else: ?>
	<p>You are not allow to access this page</p>
<?php endif ?>