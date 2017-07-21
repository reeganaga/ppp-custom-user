<?php 
/**
* admin page 
*/
class ppp_admin_page
{
	
	function __construct()
	{
     	add_action('init', array($this, 'init'));
		add_action( 'admin_menu', array($this,'ppp_admin_menu') );

	}

	function init(){
		if (isset($_POST['ppp_process_admin'])) {
			$this->ppp_process_admin();
		}
	}

	function ppp_admin_menu() {
		add_menu_page( 'PPP Custom Login', 'PPP Custom Login', 'manage_options', 'ppp-custom-user', array($this,'ppp_admin_form'), 'dashicons-unlock', 6  );
	}
	function ppp_admin_form(){ ?>
		<div class="wrap">
			<h2>configuration</h2>
			<div class="flash_message"></div>
			<form class="form" method="post" action="" id="ppp_form_configuration">
				<table class="form-table">
			        <tbody>
			        	<?php 
			        	include 'page/form_admin.php';
			        	 ?>
			        	<?php foreach ($forms as $key => $value): ?>

							<?php 
							switch ($value['type']) {
								case 'theme_select': ?>
								<tr valign="top">
									<th scope="row"><?php echo $value['title']; ?></th>
									<td>
										<select class="form_input" name="<?php echo $value['name'] ?>" id="<?php echo $value['name']; ?>">
											<?php 
									        	$dir = plugin_dir_path(__FILE__).'repo_themes/';
												// Open a known directory, and proceed to read its contents
												if (is_dir($dir)) {
												    if ($dh = opendir($dir)) {
												        while (($file = readdir($dh)) !== false) {
												        	if ($file != "." && $file != "..") {
												            	echo "<option value='".$file."'>".$file."</option>";
												        	}
												        }
												        closedir($dh);
												    }
												}
								        	?>
										</select>
									</td>
								</tr>
								<?php break;
								case 'text':?>
									<tr valign="top">
										<th scope="row"><?php echo $value['title']; ?></th>
										<td>
											<input class="form_input" type="text" <?php echo (isset($value['action']))?'data-action="'.$value['action'].'"':''; ?> name="<?php echo $value['name']; ?>" id="<?php echo $value['name']; ?>" value="<?php echo (isset($value['std'])) ? $value['std'] : get_option($value['name']); ; ?>" >
											<?php if ($value['desc']): ?>
												<p><?php echo $value['desc'] ?></p>
											<?php endif ?>
											<div class="message"></div>
										</td>
									</tr>
								<?php break;
								default:?>
								UNDEFINED TYPE
								<?php break;
							}
							 ?>
			        	<?php endforeach ?>
						<tr valign="top">
							<th scope="row">
								<input type="submit" name="ppp_process_admin" class="button">
								<div class="spinner" style="margin-right: 100px;"></div>
							</th>
						</tr>
					</tbody>
			    </table>
			</form>
			<div id="process_message"></div>
		</div>
		<?php
	}

	function ppp_process_admin(){
		$data = $_POST;
		// var_dump($data);
		$protected_field= array('ppp_process_admin');
		foreach ($data as $key => $value) {
			if ( in_array($key, $protected_field)) continue;
			update_option( $key, $value);
		}
	}
}

$ppp_admin_page = new ppp_admin_page;
 ?>