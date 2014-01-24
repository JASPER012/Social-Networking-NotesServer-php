<?php
    include 'connect.php';
    $sticky_uid = $_GET["sticky_uid"];
    $divisor = 100;
    if($sticky_uid==NULL){exit();}
    $path = "./UpLoad/$sticky_uid";
    if(!file_exists($path))
    {
        mkdir($path);
        chmod($path,0777);
    }
    
    $path = "./UpLoad/$sticky_uid/";
    $save_path = $path.$_FILES["File"]["name"];
    $filename = $_FILES["File"]["name"];
    echo $filename;
    if( $_FILES["File"]["error"] == UPLOAD_ERR_OK )
    {          
        if(move_uploaded_file($_FILES["File"]["tmp_name"],$save_path))
        {
            chmod($save_path,0777);
            
            mysql_select_db('note_content');
            $remain_note = $sticky_uid%$divisor;
            $sql = "update sticky_upload_file_$remain_note set exist = 1 where sticky_uid=$sticky_uid and file_name = '$filename'";
            mysql_query($sql) or die("fail to update sticky_upload_file_$remain_note set exist = 1 where sticky_uid=$sticky_uid and file_name = '$filename'");
            echo "success";
        }
        else
        {
            echo "move file fail";
        }
    }
    else
    {
        echo "error number:".$_FILES["File"]["error"];
        echo "fail";
    }
?>