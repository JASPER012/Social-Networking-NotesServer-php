<?php   
    include 'connect.php';
    
    
    $user_uid = $_POST["user_uid"];
    $facebook_uid = $_POST["facebook_uid"];
    $DeviceUID = $_POST["deviceUID"];
    $DeviceUID_array=explode("-",$DeviceUID);
    $DeviceUID =implode("", $DeviceUID_array);
    $access_token = $_POST["access_token"];
    
    /*
    $user_uid = "67";
    $facebook_uid = "100002564501814";
    $DeviceUID= "123321";
    $access_token = "CAACEdEose0cBAE1ZBI6pkHPzynym7yLF7t7sGZCpz8TciPjv4edjMrCq3eMsCCZAhP9XzamYh7yGzZB4KT7J9glaTlb7BRLA1HUq4eg2ZABkDwWSgBdOPD6y0hQZBHSZCaJY8JFnEZB4WNncJJcanYBlFCJGUUBOxKd917ITCZB63IQAngN7eHR6gsbJFaHxAP6x4GtKNW6ZCz6AZDZD";
    */
    //use access token to get fbfriendlist
    $appid = '174186899453258';
    $secret = 'be626f06a5f43899a4eb6b614ffe970e';
    //$url = "graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id=$appid&client_secret=$secret&fb_exchange_token=$access_token";
    $url = "https://graph.facebook.com/$facebook_uid/friends?access_token=$access_token";
    $page_num = 0;
    $curl = @curl_init($url);
    @curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $json_fbfriendlist[$page_num] = @curl_exec($curl);
    @curl_close($curl);
    
    /*
    $url = "https://graph.facebook.com/$facebook_uid";
    $curl = @curl_init($url);
    @curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $json_data = @curl_exec($curl);
    @curl_close($curl);
    $user_data = json_decode($json_data);
    $user_facebook_name = $user_data ->name;
    
    //use access token to get self picture
    $url = "https://graph.facebook.com/$facebook_uid?fields=picture.width(100).height(100)&access_token=$access_token";
    $curl = @curl_init($url);
    @curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $json_picture = @curl_exec($curl);
    @curl_close($curl);
    $picture_data = json_decode($json_picture);
    $picture_url = $picture_data ->picture ->data ->url;
    
    $curl = @curl_init($picture_url);
    @curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $picture = @curl_exec($curl);
    @curl_close($curl);
    $value = base_convert($picture, 16, 2);
    echo $value;
    
    mysql_select_db("user_account");
    $divisor = 100;
    $remain_user = $user_uid%$divisor;
    $sql = "update user_account_$remain_user set facebook_name = '$user_facebook_name'  where user_uid = '$user_uid'";
    $result = mysql_query($sql) or die ("fail to update user_account_$remain_user set facebook_name = '$user_facebook_name'  where user_uid = '$user_uid'");
    
    $sql = "update user_account_$remain_user set facebook_picture = '$picture_bin' where user_uid = '$user_uid'";
    $result = mysql_query($sql) or die ("fail to update user_account_$remain_user set facebook_picture = '$picture_bin' where user_uid = '$user_uid'");*/
    
    
    while(true)
    {
        $fbfriendlist = json_decode($json_fbfriendlist[$page_num]);

        $next_page_url = $fbfriendlist ->paging -> next;
        //echo $next_page_url;
        //確認這頁是否好友名單的最後一頁
        if($next_page_url== null)
        {
            //echo "bingo";
            break;
        }
        //尚未到最後一頁
        //繼續索取資料
        $culr = @curl_init($next_page_url);
        @curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $page_num = $page_num + 1;
        
        $json_fbfriendlist[$page_num] = @curl_exec($curl);
        @curl_close($curl);
    }
    
    $index = 0;
    mysql_select_db("user_account");
    $divisor = 100;
    $remain_user = $user_uid%$divisor;
    $num_new_friend = 0;
    $app_friendlist_num = 0;
    //檢查好友名單中有哪些人使用app
    while($index <=$page_num)
    {
        $fbfriendlist = json_decode($json_fbfriendlist[$index]);
        $fbfriendlist_data = $fbfriendlist ->data;
        
        $friend_index = 0;
        while($fbfriendlist_data[$friend_index])
        {
            $fb_uid =  $fbfriendlist_data[$friend_index] ->id;
            $remain_fb = $fb_uid%100;
            $sql = "select * from user_account_indexbyfacebook_$remain_fb where facebook_uid = $fb_uid";
            //echo $sql;
            $result = mysql_query($sql) or die ("fail to select * from user_account_indexbyfacebook_$remain_fb");
            //這位fb 好友有使用app
            if($record = mysql_fetch_array($result))
            {
                //echo $fb_uid;
                //echo "<br>";
                $app_friendlist[$app_friendlist_num] = $fb_uid;
                $app_friendlist_contact_uid[$app_friendlist_num] = $record['user_uid'];
                $app_friendlist_name [$app_friendlist_num] = $fbfriendlist_data[$friend_index] ->name;
                $app_friendlist_num = $app_friendlist_num +1;
            }
            $friend_index = $friend_index + 1;
        }
        
        $index = $index + 1;
    }
    //確認是否與舊的名單人數一樣(假如一樣則直接回傳)
    mysql_select_db("contactlist_$remain_user");
    $sql = "select count(*) from contactlist_$user_uid";
    $result = mysql_query($sql) or die ("fail to select count(*) from contactlist_$user_uid");
    $record =  mysql_fetch_array($result);
    $old_friend_num = $record[0];
    //echo $old_friend_num;
    if($old_friend_num == $app_friendlist_num)
    {
        //代表好友個數不變
        
    }
    else
    {
        //get old app's friendlist        
        $sql = "select * from contactlist_content_$user_uid";
        //echo $sql;
        $result = mysql_query($sql) or die ("fail to select * from contactlist_content_$user_uid");
        $num = 0;
        while($record = mysql_fetch_array($result))
        {
            $app_friendlist_old[$num] = $record['facebook_uid'];
            $app_friendlist_old_user_uid[$num]=$record['contact_uid'];
            $num = $num +1 ;
        }
            
        //開始比對
        $n = 0;
        while($n<$app_friendlist_num)
        {
            $exist = 0;
            for($x=0;$x<$num;$x++)
            {
                if($app_friendlist[$n]==$app_friendlist_old[$x])
                {
                    //echo $app_friendlist[$n];
                    //echo "<br>";
                    //echo $app_friendlist_old[$x];
                    //echo "<br>";
                    $exist= 1;
                    break;
                }
            }
            //echo $exist;
            //將這位好友添加進好友名單
            if($exist == 0)
            {
                    
                $sql = "insert into contactlist_content_$user_uid (contact_uid,facebook_uid,facebook_name) values('$app_friendlist_contact_uid[$n]','$app_friendlist[$n]','$app_friendlist_name[$n]')";
                mysql_query($sql)or die ("fail to insert into contactlist_content_$user_uid (contact_uid,facebook_uid,facebook_name) values('$app_friendlist_contact_uid[$n]','$app_friendlist[$n]','$app_friendlist_name[$n]')");
                    
                $sql = "insert into contactlist_$user_uid (contact_uid,DeviceUID_$DeviceUID) values('$app_friendlist_contact_uid[$n]',1)";
                mysql_query($sql)or die ("fail to insert into contactlist_$user_uid (contact_uid,DeviceUID_$DeviceUID) values($app_friendlist_contact_uid[$n],1)");
                
                $sql = "insert into facebook_picture_sync_$user_uid (contact_uid) values ($app_friendlist_contact_uid[$n])";
                mysql_query($sql)or die ("fail to insert into facebook_picture_sync_$user_uid (contact_uid) values ($app_friendlist_contact_uid[$n])");
                //將這位好友加入json物件
                //echo "$app_friendlist_old_user_uid[$num]";
                $json_contactlist[$num_new_friend] = array( 
                "contact_uid" =>$app_friendlist_contact_uid[$n] ,
                "facebook_uid" => $app_friendlist[$n],
                "facebook_name" => $app_friendlist_name[$n]
                );
                $num_new_friend = $num_new_friend + 1 ;
            }
            $n = $n+1;
        }
    }
    
    mysql_select_db("contactlist_$remain_user");
    //找出需要同步的contact
    $sql = "select * from contactlist_$user_uid where DeviceUID_$DeviceUID = 0";
    $result = mysql_query($sql) or die("fail to select * from contactlist_$user_uid where DeviceUID_$DeviceUID = 0");
    //$num = 0;
    while($record = mysql_fetch_array($result))
    {
        //get contact's information
        $contact_uid = $record['contact_uid'];
        $sql = "select * from contactlist_content_$user_uid where contact_uid=$contact_uid";
        $result_2 = mysql_query($sql) or die("fail to select * from contactlist_content_$user_uid where contact_uid=$contact_uid");
        $record_2 = mysql_fetch_array($result_2);
        //轉成boolean 
        $isvip = $record_2['isvip'];
        if($isvip == 1)
        {
            $isvip = true;
        }
        else
        {
            $isvip = false;
        }
        $tmp = $record_2['contact_uid'];
        $nick_name = $record_2['nick_name'];
        if($nick_name)
        {
            $json_contactlist[$num_new_friend] = array( 
                "contact_uid" => $record_2['contact_uid'],
                "facebook_uid" => $record_2['facebook_uid'],
                "facebook_name" => $record_2['facebook_name'],
                "isvip" => $isvip,
                "nick_name"  => $record_2['nick_name']
            );
        }
        else
        {
            $json_contactlist[$num_new_friend] = array( 
                "contact_uid" => $record_2['contact_uid'],
                "facebook_uid" => $record_2['facebook_uid'],
                "facebook_name" => $record_2['facebook_name'],
                "isvip" => $isvip
            );
        }
        $num_new_friend = $num_new_friend + 1 ;
    }
    
    //將所有這台device 需要同步的contact消除
    $sql = "update contactlist_$user_uid set DeviceUID_$DeviceUID = '1' ";
    mysql_query($sql) or die("fail to update contactlist_$user_uid set DeviceUID_$DeviceUID = '1'");
    
    
    //確認是否為空
    if($num_new_friend == 0){echo "[]";exit();} 
    array_walk_recursive($json_contactlist, function(&$value, $key) {
    if(is_string($value)) {
        $value = urlencode($value);
    }
    });
    $total = urldecode(json_encode($json_contactlist));
    echo $total;
?>