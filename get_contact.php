<?php   
    include 'connect.php';
    
    $user_uid = $_POST["user_uid"];
    $DeviceUID = $_POST["deviceUID"];
    $DeviceUID_array=explode("-",$DeviceUID);
    $DeviceUID =implode("", $DeviceUID_array);
    
    //$user_uid = 67;
    //$DeviceUID = "123321";
    
    $divisor = 100;
    $remain_user = $user_uid%$divisor;  
    mysql_select_db("contactlist_$remain_user");
    
    $sql = "select * from facebook_picture_sync_$user_uid where DeviceUID_$DeviceUID = 0";
    $result = mysql_query($sql) or die("fail to select * from facebook_picture_sync_$user_uid where DeviceUID_$DeviceUID = 0");
    //�T�{�O�_�ݭn�i��P�BFB�j�Y�K
    if($record = mysql_fetch_array($result))
    {
        //�}�l����n��fb�j�Y�K
        $sql = "select * from facebook_picture_sync_$user_uid where DeviceUID_$DeviceUID = 0";
        $result = mysql_query($sql) or die("fail to select * from facebook_picture_sync_$user_uid where DeviceUID_$DeviceUID = 0");
        $num = 0;
        while($record = mysql_fetch_array($result))
        {
            $contact_uid = $record['contact_uid'];
            $remain_contact_uid = $contact_uid%$divisor;
            
            $sql ="select * from contactlist_content_$user_uid where contact_uid = $contact_uid";
            $result_2 = mysql_query($sql) or die("fail to select * from contactlist_content_$user_uid");
            $record_2 = mysql_fetch_array($result_2);
            $facebook_uid = $record_2['facebook_uid'];
            
            $url = "https://graph.facebook.com/$facebook_uid/?fields=picture.type(square)";
            //get picture source link
            $curl = @curl_init($url);
            @curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $json_data = @curl_exec($curl);
            @curl_close($curl);
            
            $picture_data = json_decode($json_data);
            $picture_url = $picture_data ->picture ->data->url;
            //get picture content
            $curl = @curl_init($picture_url);
            @curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $picture = @curl_exec($curl);
            $picture = base64_encode($picture);
            $new_facebook_picture_hashvalue = sha1("$picture");
            //����sdatabase ��picture��hashvalue
            mysql_select_db("user_account");
            $sql ="update user_account_$remain_contact_uid set facebook_picture_hashvalue = '$new_facebook_picture_hashvalue' where user_uid = '$contact_uid'";
            mysql_query($sql) or die("fail to update user_account_$remain_contact_uid set facebook_picture_hashvalue = '$new_facebook_picture_hashvalue' where user_uid = '$contact_uid'");
            mysql_select_db("contactlist_$remain_user");
            //�}�l����json����
            //�Nisvip �qnumber �নboolean 
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
                $json_contactlist[$num] = array( 
                    "contact_uid" => $record_2['contact_uid'],
                    "facebook_uid" => $record_2['facebook_uid'],
                    "facebook_name" => $record_2['facebook_name'],
                    "facebook_picture"=>$picture,
                    "isvip" => $isvip,
                    "nick_name"  => $record_2['nick_name']
                );
            }
            else
            {
                $json_contactlist[$num] = array( 
                    "contact_uid" => $record_2['contact_uid'],
                    "facebook_uid" => $record_2['facebook_uid'],
                    "facebook_name" => $record_2['facebook_name'],
                    "facebook_picture"=>$picture,
                    "isvip" => $isvip
                );
            }
            $num = $num + 1 ;
        }
        
        //�N�ݭn�P�B��device �]���w�P�B
        
        mysql_select_db("contactlist_$remain_user");
        $sql = "update facebook_picture_sync_$user_uid set DeviceUID_$DeviceUID = 1";
        mysql_query($sql) or die("fail to update facebook_picture_sync_$user_uid set DeviceUID_$DeviceUID = 1");
        //�T�{�O�_����
        if($num == 0){echo "[]";exit();} 
        array_walk_recursive($json_contactlist, function(&$value, $key) {
        if(is_string($value)) {
            $value = urlencode($value);
        }
        });
        $total = urldecode(json_encode($json_contactlist));
        echo $total;
        exit();
    }
    mysql_select_db("contactlist_$remain_user");
    //���p���ݭn��s�j�Y�K�A�h��X�ݭn�P�B��contact
    $sql = "select * from contactlist_$user_uid where DeviceUID_$DeviceUID = 0";
    $result = mysql_query($sql) or die("fail to select * from contactlist_$user_uid where DeviceUID_$DeviceUID = 0");
    $num = 0;
    while($record = mysql_fetch_array($result))
    {
        //get contact's information
        $contact_uid = $record['contact_uid'];
        $sql = "select * from contactlist_content_$user_uid where contact_uid=$contact_uid";
        $result_2 = mysql_query($sql) or die("fail to select * from contactlist_content_$user_uid where contact_uid=$contact_uid");
        $record_2 = mysql_fetch_array($result_2);
        //�নboolean 
        $isvip = $record_2['isvip'];
        if(strcasecmp($isvip,"true") == 0)
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
            $json_contactlist[$num] = array( 
                "contact_uid" => $record_2['contact_uid'],
                "facebook_uid" => $record_2['facebook_uid'],
                "facebook_name" => $record_2['facebook_name'],
                "isvip" => $isvip,
                "nick_name"  => $record_2['nick_name']
            );
        }
        else
        {
            $json_contactlist[$num] = array( 
                "contact_uid" => $record_2['contact_uid'],
                "facebook_uid" => $record_2['facebook_uid'],
                "facebook_name" => $record_2['facebook_name'],
                "isvip" => $isvip
            );
        }
        $num = $num + 1 ;
    }
    
    //�N�Ҧ��o�xdevice �ݭn�P�B��contact����
    $sql = "update contactlist_$user_uid set DeviceUID_$DeviceUID = '1' ";
    mysql_query($sql) or die("fail to update contactlist_$user_uid set DeviceUID_$DeviceUID = '1'");
    
    //�T�{�O�_����
    if($num == 0){echo "[]";exit();} 
    array_walk_recursive($json_contactlist, function(&$value, $key) {
    if(is_string($value)) {
        $value = urlencode($value);
    }
    });
    $total = urldecode(json_encode($json_contactlist));
    echo $total;
?>