<?php   
    include 'connect.php';
    mysql_select_db('note_content');
    /*
    $user_uid = 43;
    $DeviceUID ="E35B3E737FF64EE9A43442C096A7036F";
    $json_notelist = $_GET["json_notelist"];*/
    
    
    $user_uid = $_POST["user_uid"];
    $DeviceUID = $_POST["deviceUID"];
    $DeviceUID_array=explode("-",$DeviceUID);
    $DeviceUID =implode("", $DeviceUID_array);
    $json_notelist = $_POST["json_notelist"];
    //echo $json_notelist;
    //echo $json_notelist;
    //echo "<br>";
     
    //$user_uid = 29;
    //$DeviceUID = 44444;
    //$json_notelist =$_GET["json_notelist"];
    $notelist = json_decode( $json_notelist );
    $divisor = 100;
    $index = 0;
    
    $remain_user = $user_uid%$divisor;
    while($notelist[$index])
    {
        //check note if exist
        $data = $notelist[$index];
        
        $sticky_uid = $data ->sticky_uid;
        $sender_uid = $data -> sender_uid;
        $send_time = $data ->send_time;
        $alert_time = $data -> alert_time;
        $location = $data ->location;
        $context = $data -> context;
        $archived = $data -> archived;
        $read = $data -> read;
        $accepted = $data ->accepted;
        $note_sync = $data ->note_sync;
        //some media file need to sync, set this note to need sync
        if($note_sync==1)
        {
            //�N�o�x�˸m�]���ݭn�P�B
            mysql_select_db("notelist_$remain_user");
            $sql = "update notelist_$remain_user set DeviceUID_$DeviceUID = 0 where note_uid = $sticky_uid";
            mysql_query($sql) or die("faile to update notelist_$remain_user set DeviceUID_$DeviceUID = 0 where note_uid = $sticky_uid");
        }
        //create note
        else if($sticky_uid == -1)
        {           
            mysql_select_db('note_content');
            //get sticky_uid
            $sql="SELECT MAX(uid) AS uid FROM sticky_uid";
            $result = mysql_query($sql) or die('MySQL query error in get sticky_uid');
            while($row = mysql_fetch_array($result)){
                //echo $row['uid'];
                $sticky_uid = $row['uid'];
            }  
            $sql = "insert into sticky_uid set uid=NULL";
            $result = mysql_query($sql) or die('MySQL query error in insert sticky_uid');
            
            $remain_note = $sticky_uid%$divisor;
            //set sticky attribute
            if($location)
            {
                $sql = "insert into sticky_attribute_$remain_note (sticky_uid,sender_uid,send_time,alert_time,location,context) values('$sticky_uid','$sender_uid','$send_time','$alert_time','$location','$context')";
                mysql_query($sql) or die("can't insert sticky into database");
            }
            else
            {
                $sql = "insert into sticky_attribute_$remain_note (sticky_uid,sender_uid,send_time,alert_time,context) values('$sticky_uid','$sender_uid','$send_time','$alert_time','$context')";
                mysql_query($sql) or die("can't insert sticky into database");
            }
    
            //set sticky receiver
            $receiver_num = count ($data -> receiver_list) -1;
            while ($receiver_num >=0)
            {
                $receiver_uid = $data -> receiver_list[$receiver_num] -> receiver_uid;
                $sql = "insert into sticky_receiver_$remain_note (sticky_uid,receiver_uid,accepted,if_read) values('$sticky_uid','$receiver_uid','0','0')";
                mysql_query($sql) or die("can't set receiver into database");
                //�Nsticky ��J�i receiver �� �P�B�M�椧��
                $remain_receiver = $receiver_uid%$divisor;
                mysql_select_db("notelist_$remain_receiver");
                $sql = "insert into notelist_$receiver_uid (note_uid) values('$sticky_uid')";
                mysql_query($sql) or die("fail to insert into notelist_$receiver_uid (note_uid) values('$sticky_uid')");
                $sql = "insert into notelist_archived_$receiver_uid (note_uid) values('$sticky_uid')";
                mysql_query($sql) or die("fail to insert into notelist_archived_$receiver_uid (note_uid) values('$sticky_uid')");
                mysql_select_db("note_content");
                
                $receiver_num = $receiver_num-1;
            }
    
            //set sticky ���a�ɮ�
            $file_num = count ($data -> file_list) -1;
            while ($file_num >=0)
            {
                $file_name = $data -> file_list[$file_num] -> file_name;
                $file_type = $data -> file_list[$file_num] -> file_type;
                $sql = "insert into sticky_upload_file_$remain_note (sticky_uid,file_name,file_type) values('$sticky_uid','$file_name','$file_type')";
                mysql_query($sql) or die("can't set upload file into database");
                $file_num = $file_num-1;
            }
            //�N�Ҧ��x�˸m�]���ݭn�P�B
            mysql_select_db("notelist_$remain_user");
            $sql = "insert into notelist_$user_uid (note_uid) values('$sticky_uid')";
            mysql_query($sql) or die("fail to insert into notelist_$user_uid (note_uid) values('$sticky_uid')");
            $sql = "insert into notelist_archived_$user_uid (note_uid) values('$sticky_uid')";
            mysql_query($sql) or die("fail to insert into notelist_archived_$user_uid (note_uid) values('$sticky_uid')");
        }
        //modified note content
        else
        {
            
            $remain_note = $sticky_uid%$divisor;
            //��note �O��sender ���
            if(strcasecmp($user_uid,$sender_uid)==0)
            {
                //echo "bingo";
                mysql_select_db("notelist_$remain_user");
                //�ϥΪ̥i�঳���archived �ݩ�
                $sql = "select * from notelist_archived_$user_uid where note_uid = $sticky_uid";
                $result = mysql_query($sql) or die("fail to select * from notelist_archived_$user_uid where note_uid = $sticky_uid");
                $record = mysql_fetch_array($result);
                $old_archived = $record['archived'];
                if($old_archived==0 && $archived==1 || $old_archived==1 && $archived==0)
                {
                    //���parchived ����ʡA�N��L�x�˸m�]���ݭn�P�B
                    $sql = "delete from notelist_$user_uid where note_uid = $sticky_uid";
                    mysql_query($sql) or die("fail to delete from notelist_$user_uid where note_uid = $sticky_uid");
                    $sql = "insert into notelist_$user_uid (note_uid,DeviceUID_$DeviceUID) values('$sticky_uid','1')";
                    mysql_query($sql) or die("fail to insert into notelist_$user_uid (note_uid,DeviceUID_$DeviceUID) values('$sticky_uid','1')");
                    
                    $sql = "update notelist_archived_$user_uid set archived = $archived where note_uid = $sticky_uid";
                    mysql_query($sql) or die("fail to update notelist_archived_$user_uid set archived = $archived where note_uid = $sticky_uid");
                    /*
                    $index = $index + 1;
                    continue;*/
                }
                mysql_select_db('note_content');
                //�T�w�ϥΪ̭ק�Fnote
                
                //delete old sticky
                $sql = "delete from sticky_attribute_$remain_note where sticky_uid = $sticky_uid";
                mysql_query($sql) or die("fail to delete from sticky_attribute_$remain_note where sticky_uid = $sticky_uid");
                $sql = "delete from sticky_receiver_$remain_note where sticky_uid = $sticky_uid";
                mysql_query($sql) or die("fail to delete from sticky_receiver_$remain_note where sticky_uid = $sticky_uid");
                //echo $sql;
                
                
                //set sticky attribute
                if($location)
                {
                    $sql = "insert into sticky_attribute_$remain_note (sticky_uid,sender_uid,send_time,alert_time,location,context) values('$sticky_uid','$sender_uid','$send_time','$alert_time','$location','$context')";
                    mysql_query($sql) or die("can't insert sticky into database");
                }
                else
                {
                    $sql = "insert into sticky_attribute_$remain_note (sticky_uid,sender_uid,send_time,alert_time,context) values('$sticky_uid','$sender_uid','$send_time','$alert_time','$context')";
                    mysql_query($sql) or die("can't insert sticky into database");
                }
    
                //set sticky receiver
                $receiver_num = count ($data -> receiver_list) -1;

                while ($receiver_num >=0)
                {
                    mysql_select_db('note_content');
                    $receiver_uid = $data -> receiver_list[$receiver_num] -> receiver_uid;
                    $sql = "insert into sticky_receiver_$remain_note (sticky_uid,receiver_uid,accepted,if_read) values('$sticky_uid','$receiver_uid','0','0')";
                    mysql_query($sql) or die("fail to insert into sticky_receiver_$remain_note (sticky_uid,receiver_uid,accepted,if_read) values('$sticky_uid','$receiver_uid','0','0')");
                    
                    //�Nsticky ��J�i receiver �� �P�B�M��
                    $remain_receiver = $receiver_uid%$divisor;
                    mysql_select_db("notelist_$remain_receiver");
                    
                    //�M���ª����
                    $sql = "delete from notelist_$receiver_uid where note_uid=$sticky_uid";
                    mysql_query($sql) or die("fail to delete from notelist_$receiver_uid where note_uid=$sticky_uid");
                    //��J�s�����
                    $sql = "insert into notelist_$receiver_uid (note_uid) values('$sticky_uid')";
                    mysql_query($sql) or die("fail to insert into notelist_$receiver_uid (note_uid) values('$sticky_uid')");
                    $receiver_num = $receiver_num-1;
                }
    
    
                //set sticky ���a�ɮ�
                mysql_select_db("note_content");
                $file_num = count ($data -> file_list) -1;
                while ($file_num >=0)
                {
                    $file_name = $data -> file_list[$file_num] -> file_name;
                    $file_type = $data -> file_list[$file_num] -> file_type;
                    //�ˬdfile ��ƬO�_�w�s�b
                    $sql = "select * from sticky_upload_file_$remain_note where sticky_uid = $sticky_uid and file_name = '$file_name'";
                    $result = mysql_query($sql) or die("fail to select * from sticky_upload_file_$remain_note where sticky_uid = $sticky_uid and file_name = '$file_name'");
                    if(mysql_fetch_array($result))
                    {
                        echo "bingo";
                        //�w�s�b�o�� file ����T
                    }
                    else
                    {
                        $sql = "insert into sticky_upload_file_$remain_note (sticky_uid,file_name,file_type) values('$sticky_uid','$file_name','$file_type')";
                        mysql_query($sql) or die("can't set upload file into database");
                    }
                    $file_num = $file_num-1;
                }
                mysql_select_db("notelist_$remain_user");
                //�N�Ҧ��x�˸m�]���ݭn�P�B
                $sql = "delete from notelist_$user_uid where note_uid = $sticky_uid";
                mysql_query($sql) or die("fail to delete from notelist_$user_uid where note_uid = $sticky_uid");
                $sql = "insert into notelist_$user_uid (note_uid) values('$sticky_uid')";
                mysql_query($sql) or die("fail to insert into notelist_$user_uid (note_uid) values('$sticky_uid')");
        
            }
            //��note �O��receiver ���
            else
            {
                //echo "bingo";
                mysql_select_db('note_content');
                //get old_read
                $sql = "select * from sticky_receiver_$remain_note where sticky_uid = $sticky_uid and receiver_uid = $user_uid";
                $result = mysql_query($sql) or die("fail to select * from sticky_receiver_$remain_note where sticky_uid = $sticky_uid and receiver_uid = $user_uid");
                $record = mysql_fetch_array($result);
                $old_accepted = $record['accepted'];
                $old_read = $record['if_read'];
                
                $sync_sender=0;
                $sync_self = 0;
                //�T�{read �O�_�ܰ�
                if($read ==1 && $old_read ==0 || $read==0 && $old_read == 1)
                {
                    $sql = "update sticky_receiver_$remain_note set if_read = 1 where sticky_uid = $sticky_uid and receiver_uid = $user_uid";
                    mysql_query($sql) or die("fail to update sticky_receiver_$remain_note set if_read = 1 where sticky_uid = $sticky_uid and receiver_uid = $user_uid");
                    
                    $sync_sender=1;
                    $sync_self =1;
                    
                }
                //�T�{accepted �O�_�ܰ�
                if($accepted==1 && $old_accepted==0 || $accepted==0 && $old_accepted==1)
                {
                    $sync_sender=1;
                    $sync_self =1;
                    mysql_select_db('note_content');
                    $sql = "update sticky_receiver_$remain_note set accepted = 1 where sticky_uid = $sticky_uid and receiver_uid = $user_uid";
                    mysql_query($sql) or die("fail to update sticky_receiver_$remain_note set accepted = $accepted where sticky_uid = $sticky_uid and receiver_uid = $user_uid");
                }
                
                mysql_select_db("notelist_$remain_user");
                //�T�{�ϥΪ̬O�_���archived �ݩ�
                $sql = "select * from notelist_archived_$user_uid where note_uid = $sticky_uid";
                $result = mysql_query($sql) or die("select * from notelist_archived_$user_uid where note_uid = $sticky_uid");
                $record = mysql_fetch_array($result);
                $old_archived = $record['archived'];
                if($old_archived==0 && $archived==1 || $old_archived==1 && $archived==0)
                {               
                    $sql = "update notelist_archived_$user_uid set archived = $archived where note_uid = $sticky_uid";
                    mysql_query($sql) or die("fail to update notelist_archived_$user_uid set archived = $archived where sticky_uid = $sticky_uid");
                    $sync_self =1;
                }
                
                if($sync_sender==1)
                {
                    //�N�o�e�̳]���ݭn�P�B
                    $remain_sender = $sender_uid%$divisor;
                    mysql_select_db("notelist_$remain_sender");
                    $sql = "delete from notelist_$sender_uid where note_uid = $sticky_uid";
                    mysql_query($sql) or die("fail to delete from notelist_$sender_uid where note_uid = $sticky_uid");
                    $sql = "insert into notelist_$sender_uid (note_uid) values('$sticky_uid')";
                    mysql_query($sql) or die("fail to insert into notelist_$sender_uid (note_uid) values('$sticky_uid')");
                }
                if($sync_self==1)
                {
                    //�N�Ҧ��x�˸m�]���ݭn�P�B
                    mysql_select_db("notelist_$remain_user");
                    $sql = "delete from notelist_$user_uid where note_uid = $sticky_uid";
                    mysql_query($sql) or die("fail to delete from notelist_$user_uid where note_uid = $sticky_uid");
                    $sql = "insert into notelist_$user_uid (note_uid) values('$sticky_uid')";
                    mysql_query($sql) or die("fail to insert into notelist_$user_uid (note_uid) values('$sticky_uid')");
                }
                
            }
        }      
       
        $index = $index + 1;
    }

?>
