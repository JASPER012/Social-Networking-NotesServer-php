<?php   
    include 'connect.php';
    
    mysql_select_db('user_account');
    
    $fb_uid = $_POST["facebook_uid"];
    $DeviceUID = $_POST["deviceUID"];
    $DeviceUID_array=explode("-",$DeviceUID);
    $DeviceUID =implode("", $DeviceUID_array);
    
    //$fb_uid = "100001008830550";
    //$DeviceUID = "123321";
    
    $divisor = 100;
    $remain_fb = $fb_uid%$divisor;
    //check if fb_uid �w�Q�ϥ�
    $sql = "select * from user_account_indexByFacebook_$remain_fb where facebook_uid = $fb_uid";
    //echo $sql;
    $result = mysql_query($sql) or die('MySQL query error in select * from user_account_indexByFacebook_$remain_fb where facebook_uid = $fb_uid');
    //fb_uid �w�Q�ϥ� 
    if($row = mysql_fetch_array($result)){
        $user_uid = $row['user_uid'];
        $remain = $user_uid%$divisor;
        
        //�Ncontactlist �H�� notelist �����X�R
        mysql_select_db("notelist_$remain");
        //�T�{�o�xdevice �O�_�w�g���U�L
        $sql = "alter table notelist_$user_uid add DeviceUID_$DeviceUID int DEFAULT 0";
        if(mysql_query($sql)==0)
        {
            echo "{\"user_uid\": \"$user_uid\"}";
            exit();
        }
        //mysql_query($sql) or die("MySQL query error in alter table notelist_$user_uid add DeviceUID_$DeviceUID int");
        $sql = "update notelist_$user_uid set DeviceUID_$DeviceUID = 0";
        mysql_query($sql) or die("MySQL query error in update notelist_$user_uid set DeviceUID_$DeviceUID = 0");
         
        mysql_select_db("contactlist_$remain");
         
        $sql = "alter table contactlist_$user_uid add DeviceUID_$DeviceUID int DEFAULT 0";
        mysql_query($sql) or die("MySQL query error in alter table contactlist_$user_uid add DeviceUID_$DeviceUID int DEFAULT 0");
        $sql = "update contactlist_$user_uid set DeviceUID_$DeviceUID = 0";
        mysql_query($sql) or die("MySQL query error in update contactlist_$user_uid set DeviceUID_$DeviceUID = 0");
        
        $sql = "alter table facebook_picture_sync_$user_uid add DeviceUID_$DeviceUID int DEFAULT 0";
        mysql_query($sql) or die("MySQL query error in alter table facebook_picture_sync_$user_uid add DeviceUID_$DeviceUID int DEFAULT 0");
         
        echo "{\"user_uid\": \"$user_uid\"}";
        exit();
    }
    //
    
    
    //get user_uid  
    $sql="SELECT MAX(uid) AS uid FROM user_uid";
    $result = mysql_query($sql) or die("MySQL query error in get user_uid");
    while($row = mysql_fetch_array($result)){
        //echo $row['uid'];
        $user_uid = $row['uid'];
        
    }
    $sql = "insert into user_uid set uid=NULL";
    $result = mysql_query($sql) or die('MySQL query error in insert user_uid');
    //insert user information into database
    $remain = $user_uid%$divisor;

    $sql = "insert into user_account_$remain (user_uid,facebook_uid) values('$user_uid','$fb_uid')";
    //echo $sql;
    mysql_query($sql) or die("MySQL query error in insert user_account");
    
    $sql = "insert into user_account_indexByFacebook_$remain_fb (user_uid,facebook_uid) values('$user_uid','$fb_uid')";
    mysql_query($sql) or die("MySQL query error in insert user_account");
    
    //�s�W�ϥΪ̭ӤH���(�ΨӦP�B)
    mysql_select_db("notelist_$remain");
    $sql = "create table notelist_$user_uid (note_uid int primary key,DeviceUID_$DeviceUID int DEFAULT 0)";
    mysql_query($sql) or die("MySQL query error create table notelist_$user_uid (note_uid int primary key,DeviceUID_$DeviceUID int)");
    $sql = "create table notelist_archived_$user_uid (note_uid int primary key,archived int DEFAULT 0)";
    mysql_query($sql) or die("MySQL query error create table notelist_archived_$user_uid (note_uid int primary key,archived int DEFAULT 0)");
    
    
    mysql_select_db("contactlist_$remain");
    $sql = "create table contactlist_$user_uid (contact_uid int primary key,DeviceUID_$DeviceUID int DEFAULT 0)";
    mysql_query($sql) or die("MySQL query error create table contactlist_$user_uid (note_uid int primary key,DeviceUID_$DeviceUID int)");
    $sql = "create table contactlist_content_$user_uid (contact_uid int primary key,facebook_uid varchar(40),nick_name varchar(40) ,isvip int DEFAULT 0,facebook_name varchar(40))";
    mysql_query($sql) or die("MySQL query error create table contactlist_content_$user_uid (contact_uid int primary key,facebook_uid varchar(40),nick_name varchar(40) ,isvip int DEFAULT 0,facebook_name varchar(40))");
    
    $sql = "create table facebook_picture_sync_$user_uid (contact_uid int primary key,DeviceUID_$DeviceUID int DEFAULT 0)";
    mysql_query($sql) or die("MySQL query error create table facebook_picture_sync_$user_uid (contact_uid int primary key,DeviceUID_$DeviceUID int DEFAULT 0)");
    $sql = "insert into facebook_picture_sync_$user_uid (contact_uid,DeviceUID_$DeviceUID) values ($user_uid,0)";
    mysql_query($sql) or die("MySQL query fail to insert into facebook_picture_sync_$user_uid (contact_uid,DeviceUID_$DeviceUID) values ($user_uid,0)");
    
    //�o��user�� fb_name
    $url = "https://graph.facebook.com/$fb_uid";
    $curl = @curl_init($url);
    @curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $json_data = @curl_exec($curl);
    @curl_close($curl);
    $user_data = json_decode($json_data);
    $user_facebook_name = $user_data ->name;
    
    //�N�ϥΪ̴��J�ۤv���n�ͦW��
    $sql = "insert into contactlist_$user_uid (contact_uid,DeviceUID_$DeviceUID) values('$user_uid','1')";
    mysql_query($sql)or die("MySQL query error insert into contactlist_$user_uid (contact_uid,DeviceUID_$DeviceUID) values('$user_uid','1')");;
    $sql = "insert into contactlist_content_$user_uid (contact_uid,facebook_uid,facebook_name) values('$user_uid','$fb_uid','$user_facebook_name')";
    mysql_query($sql)or die("MySQL query error insert into contactlist_content_$user_uid (contact_uid,facebook_uid,facebook_name) values('$user_uid','$fb_uid','$user_facebook_name");
    
    echo "{\"user_uid\": \"$user_uid\"}";
    
?>