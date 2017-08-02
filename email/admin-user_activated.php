Hi Admin, your member has been activated , here's the detail:<br>
Email : <?php echo $user->user_email; ?><br>
Name : <?php echo $user->first_name.' '.$user->last_name; ?><br>
Private Banker Name : <?php echo isset($user_meta['private_banker_name'][0])?$user_meta['private_banker_name'][0]:''; ?><br>
Private Banker Ph Number : <?php echo isset($user_meta['private_banker_ph_number'][0])?$user_meta['private_banker_ph_number'][0]:''; ?><br>
Private Banker Email Address : <?php echo isset($user_meta['private_banker_email_address'][0])?$user_meta['private_banker_email_address'][0]:''; ?><br>

Thank you
