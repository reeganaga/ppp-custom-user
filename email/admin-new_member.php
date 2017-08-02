<?php echo $user->first_name.' '.$user->last_name.' - '.$user->user_email; ?> has requested access to PrivatePropertyPortal.com.au to view properties advertised for sale or lease for relationship managed customers only. Your client has requested that you press the confirm tab below, confirming only that you are their banker. This ensures your  client  is eligible to access this site that is for the exclusive use of relationship managed clients.
<p>
here's the detail :<br>
Email : <?php echo $user->user_email; ?><br>
Name : <?php echo $user->first_name.' '.$user->last_name; ?><br>
Private Banker Name : <?php echo isset($user_meta['private_banker_name'][0])?$user_meta['private_banker_name'][0]:''; ?><br>
Private Banker Ph Name : <?php echo isset($user_meta['private_banker_ph_number'][0])?$user_meta['private_banker_ph_number'][0]:''; ?><br>
Private Banker Email  : <?php echo isset($user_meta['private_banker_email_address'][0])?$user_meta['private_banker_email_address'][0]:''; ?><br>
-----------<br>
<a href='<?php echo $url['accept_url']; ?>'>Approved User</a> | <a href='<?php echo $url['reject_url']; ?>'>Reject User</a><br>
-----------<br>
