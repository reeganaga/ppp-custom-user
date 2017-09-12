<?php
global $ppp_custom_user;
// echo $ppp_custom_user->message;
$banker_list = get_option('ppp_banker_list');
$banker_list = explode(',',$banker_list);
 ?>
<?php if ($ppp_custom_user->get_flash_message('register_success')): ?>
	<?php echo $ppp_custom_user->get_flash_message('register_success'); ?>
<?php else: ?>

	<?php echo ($ppp_custom_user->get_flash_message('register'))?'<p>'.$ppp_custom_user->get_flash_message('register').'<p>':''; ?>
<div class="register-form">
	<form action="" method="post">
    <ul class="list-unstyled">
		<li><label>First name</label>
		<input required="required" type="text" name="first_name" value="<?php echo (isset($_POST['first_name']))?$_POST['first_name']:''; ?>"></li>
		<li><label>Last name</label>
		<input required="required" type="text" name="last_name" value="<?php echo (isset($_POST['last_name']))?$_POST['last_name']:''; ?>"></li>
		<li><label>Email address</label>
		<input required="required" type="email" name="email" value="<?php echo (isset($_POST['email']))?$_POST['email']:''; ?>" ></li>

		<li><label>Password</label>
		<input required="required" type="password" name="password" id="txtNewPassword" onchange="checkPasswordMatch();"></li>
		<li><label>Re type Password</label>
		<input required="required" type="password" name="re_password"  id="txtConfirmPassword" onchange="checkPasswordMatch();"></li>
		<div class="registrationFormAlert" id="divCheckPasswordMatch"></div>

		<li><label>Private Banker Name</label>
		      <input required="required" type="text" name="private_banker_name" value="<?php echo (isset($_POST['private_banker_name']))?$_POST['private_banker_name']:''; ?>">
        </li>
		<li><label>Private Banker Ph number</label>
		<input type="text" name="private_banker_ph_number" value="<?php echo (isset($_POST['private_banker_ph_number']))?$_POST['private_banker_ph_number']:''; ?>"></li>
		<li><label>Private Banker Email address</label>
        <select required="required" class="" name="private_banker_email_address">
            <?php foreach ($banker_list as $list): ?>
                <?php $select_banker = selected($_POST['private_banker_name'],$list); ?>
                <option <?php echo $select_banker; ?> value="<?php echo $list; ?>"><?php echo $list; ?></option>
            <?php endforeach; ?>
        </select>
		<input type="hidden" name="act" value="ppp_register">
		<li><input type="submit" name="register" disabled="disabled" id="submit_register"></li>
  </ul>
	</form>
</div>
	<script type="text/javascript">
		function checkPasswordMatch() {
		    var password = jQuery("#txtNewPassword").val();
		    var confirmPassword = jQuery("#txtConfirmPassword").val();
		    if (password != confirmPassword){
		        jQuery("#divCheckPasswordMatch").html("Passwords do not match!");
		        jQuery("#txtConfirmPassword").css({
		        	border: '1px solid red',
		        });
		        jQuery("#submit_register").removeAttr('disabled');
		    }/*</li>
		    else if(password.length<8){
		    	jQuery("#divCheckPasswordMatch").html("Minimum password is 8 character!");
		        jQuery("#txtConfirmPassword").css({
		        	border: '1px solid red',
		        });
		        jQuery("#submit_register").removeAttr('disabled');
		    }*/
		    else{
		        jQuery("#divCheckPasswordMatch").html("Passwords match.");
		        jQuery("#txtConfirmPassword").css({
		        	border: '1px solid green',
		        });
		    }
		}
		jQuery(document).ready(function () {
		   jQuery("#txtConfirmPassword").keyup(checkPasswordMatch);
		});
	</script>
<?php endif ?>
