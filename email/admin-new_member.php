New Member Has been registered, here's the detail :<br>
Email : <?php echo $user->user_email; ?><br>
Name : <?php echo $user->first_name.' '.$user->last_name; ?><br>
Phone : <?php echo isset($user_meta['phone'][0])?$user_meta['phone'][0]:''; ?><br>
Private Banker Name : <?php echo isset($user_meta['private_banker_name'][0])?$user_meta['private_banker_name'][0]:''; ?><br>
-----------<br>
<a href='<?php echo $url['accept_url']; ?>'>Accept</a> | <a href='<?php echo $url['reject_url']; ?>'>Reject</a><br>
-----------
