<?php   
    include 'connect.php';
    /*
    $user_uid = 44;
    $DeviceUID ="9E3F59A51A924C8D9B7AFC00E6A07E32";
    $json_contactlist =$_GET["json_contactlist"];*/
    
    $user_uid = $_POST["user_uid"];
    $DeviceUID = $_POST["deviceUID"];  
    $DeviceUID_array=explode("-",$DeviceUID);
    $DeviceUID =implode("", $DeviceUID_array);
    $json_contactlist =$_POST["json_contactlist"];
    echo $json_contactlist;

    $contactlist = json_decode( $json_contactlist );
    $divisor = 100;
    $index = 0;

    $remain_user = $user_uid%$divisor;
    mysql_select_db("contactlist_$remain_user");
    while($contactlist[$index])
    {
        $isvip = $contactlist[$index] -> isvip;
        $nick_name = $contactlist[$index] -> nick_name;
        $contact_uid = $contactlist[$index] -> contact_uid;
        $facebook_uid = $contactlist[$index] -> facebook_uid;
        //echo $contact_uid;
        //delete old contact
        $sql = "delete from contactlist_content_$user_uid where contact_uid = $contact_uid";
        mysql_query($sql);//or die("fail to delete from contactlist_content_$user_uid where contact_uid = $contact_uid");
        
        //insert new contact
        //$nick_name !=nil
        if($nick_name)
        {
            $sql = "insert into contactlist_content_$user_uid (contact_uid,facebook_uid,nick_name,isvip) values('$contact_uid','$facebook_uid','$nick_name','$isvip')";
            mysql_query($sql) ;//or die("fail to insert into contactlist_content_$user_uid (contact_uid,facebook_uid,nick_name,isvip) values('$contact_uid','$facebook_uid','$nick_name','$isvip");
        }
        else
        {
            $sql = "insert into contactlist_content_$user_uid (contact_uid,facebook_uid,isvip) values('$contact_uid','$facebook_uid','$isvip')";
            mysql_query($sql) ;//or die("fail to insert into contactlist_content_$user_uid (contact_uid,facebook_uid,isvip) values('$contact_uid','$facebook_uid','$isvip");
        }
        
        //將所有device 設成 需要同步
        $sql = "delete from contactlist_$user_uid where contact_uid = $contact_uid";
        mysql_query($sql) ;//or die("fail to delete from contactlist_$user_uid where contact_uid = $contact_uid");
        $sql = "insert into contactlist_$user_uid (contact_uid) values ('$contact_uid')";
        mysql_query($sql) ;//or die("fail to insert into contactlist_$user (contact_uid) values ($contact_uid)");
        $index = $index +1;
    }
    
    
?>