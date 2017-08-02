<?php 
global $ppp_custom_user;
if (isset($_GET['msg'])) {
	$msg_string =base64_decode($_GET['msg']);
}else{
	return;
}
?>

<?php if ($msg_string): ?>
	<p class="message-alert"><?php echo $msg_string; ?></p>
<?php endif ?>
