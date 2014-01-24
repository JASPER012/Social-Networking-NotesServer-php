<?php    
    include 'connect.php'; 
    
    $DeviceUID = $_POST["deviceUID"];
    $DeviceUID_array=explode("-",$DeviceUID);
    $DeviceUID =implode("", $DeviceUID_array);
    $receiver_uid = $_POST["user_uid"];
    
    //$receiver_uid = 43;
    //$DeviceUID ="E35B3E737FF64EE9A43442C096A7036F";
    
    $divisor = 100;
    $user_uid = $receiver_uid;
    $remain_user = $receiver_uid%$divisor;
    $num = 0;
    mysql_select_db("notelist_$remain_user");
    
    //索取需要同步的note
    $sql = "select * from notelist_$user_uid where DeviceUID_$DeviceUID = 0";
    $result=mysql_query($sql) or die("fail to select * from notelist_$user_uid where DeviceUID_$DeviceUID = 0");
    mysql_select_db('note_content');
    
    while ($record = mysql_fetch_array($result)) {
        mysql_select_db('note_content');
        $sticky_uid = $record['note_uid'];
        
        $remain_note =$sticky_uid%$divisor;
        //get sticky_attribute
        $sql = "select * from sticky_attribute_$remain_note where sticky_uid = $sticky_uid";
        $result_2 = mysql_query($sql) or die("can't search sticky_attribute");
        $record_2 = mysql_fetch_array($result_2);
        $sender_uid = $record_2['sender_uid'];
        
        //get sticky_receiver
        $sql = "select * from sticky_receiver_$remain_note where sticky_uid = $sticky_uid";
        $result_3 = mysql_query($sql) or die("can't search sticky_receiver");
        
        $num_3 = 0;
        while ($record_3 = mysql_fetch_array($result_3)){
            //轉成 boolean 的型態
            $accepted = $record_3['accepted'];
            $read = $record_3['if_read'];
            //echo $accepted;
            if($accepted)
            {
                $accepted=true;
            }
            else
            {
                $accepted=false;
            }
        
            if($read)
            {
                $read=true;
            }
            else
            {
                $read=false;
            }
            $receiver_list[$num_3] = array( "receiver_uid" => $record_3['receiver_uid'],
                                             "accepted"     => $accepted,
                                             "read"         => $read
            );
            $num_3 = $num_3+1;
        }     
        //get sticky_upload_file
        $sql = "select * from sticky_upload_file_$remain_note where sticky_uid = $sticky_uid";
        $result_4 = mysql_query($sql) or die("can't search sticky_upload_file");
        $num_4 = 0;
        while ($record_4 = mysql_fetch_array($result_4)){
            $file_list[$num_4] = array( "file_name" => $record_4['file_name'],
                                             "file_type" => $record_4['file_type'],
                                             "exist" => $record_4['exist'] );
            $num_4 = $num_4+1;
        }
        //get archieved property
        mysql_select_db("notelist_$remain_user");
        $sql = "select * from notelist_archived_$user_uid where note_uid =$sticky_uid";
        $result_5 = mysql_query($sql) or die("fail to select * from notelist_archived_$user_uid where note_uid =$sticky_uid");
        $record_5 = mysql_fetch_array($result_5);
        $archived = $record_5['archived'];
        if($archived==1)
        {
            $archived=true;
        }
        else
        {
            $archived=false;
        }
        //create json object
        $location = $record_2['location'];
        $context = $record_2['context'];
        if($location==null)
        {
            $location ="";
        }
        if($context==null)
        {
            $context ="";
        }
        if($num_4==0)
        {
            $json_note[$num] = array( 
            "sticky_uid" => $sticky_uid,
            "sender_uid" => $record_2['sender_uid'],
            "receiver_list" => $receiver_list,
            "send_time"  => $record_2['send_time'],
            "alert_time" => $record_2['alert_time'],
            "location" => $record_2['location'],
            "context" => $record_2['context'],
            "archived" => $archived
            );
        }
        else{
            $json_note[$num] = array( 
            "sticky_uid" => $sticky_uid,
            "sender_uid" => $record_2['sender_uid'],
            "receiver_list" => $receiver_list,
            "send_time"  => $record_2['send_time'],
            "alert_time" => $record_2['alert_time'],
            "file_list" => $file_list,
            "location" => $record_2['location'],
            "context" => $record_2['context'],
            "archived" => $archived
            );
        }
        unset($receiver_list);
        unset($file_list);
        $num =$num+1;
    }
    
    //以下為critical section
    mysql_select_db("notelist_$remain_user");
    //將所有這台device 需要同步的note消除
    $sql = "update notelist_$user_uid set DeviceUID_$DeviceUID = '1' ";
    mysql_query($sql) or die("fail to update notelist_$user_uid set DeviceUID_$DeviceUID = '1'");
    
    
    //確認是否為空
    if($num == 0){echo "[]";exit();} 
    array_walk_recursive($json_note, function(&$value, $key) {
    if(is_string($value)) {
        $value = urlencode($value);
    }
    });
    $total = urldecode(json_encode($json_note));
    echo $total;
?>