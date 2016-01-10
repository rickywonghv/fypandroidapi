<?php
session_start();
$fbuid=$_POST['id'];
$email=$_POST['email'];
$name=$_POST['name'];
$fbgender=$_POST['gender'];
$mobtoken=$_POST['token'];
$senduid=$_POST['uid'];
if($_POST['action']==md5('profile')){
  viewprofile($mobtoken);
}
elseif($_POST['action']==md5('login')){
  login($fbuid,$email,$name,$fbgender,$mobtoken);
}
elseif ($_POST['action']==md5('albumlist')) {
  albumlist($mobtoken,$senduid);
}
elseif($_POST['action']==md5('users')&&isset($mobtoken)){
  users();
}
elseif($_POST['action']==md5('listsong')&&isset($mobtoken)){
  listsong($mobtoken);
}

function listsong($mobtoken){
  include '../config/db.php';
    $sql="select type from user where uToken=?";
    $stmt=$conn->prepare($sql);
    $stmt->bind_param("s",$mobtoken);
    $stmt->execute();
    $stmt->bind_result($retype);
    $stmt->fetch();
    if($retype==1){
      $sql="SELECT music.songid,music.title, music.songPath, music.singer FROM music INNER JOIN user ON music.userid=user.userid WHERE user.type=1";
      $stmt=mysqli_query($conn,"SET NAMES UTF8");
      $stmt=$conn->prepare($sql);
      $stmt->execute();
      $stmt->bind_result($songid, $title,$songPath,$singer);
      $array= array();
      while ($stmt->fetch()) {
          $array[]= array('id' =>$songid ,'singer'=>$singer ,'title'=>$title,'path'=>'http://musixcloud.xyz/asset/php/play.php?url='.$songPath);
          $songjson=json_encode($array,JSON_UNESCAPED_UNICODE);
      }
      print_r($songjson);
    }elseif($retype==2){
      $sql="SELECT music.songid,music.title, music.songPath, music.singer FROM music INNER JOIN user ON music.userid=user.userid WHERE user.type=2";
      $stmt=mysqli_query($conn,"SET NAMES UTF8");
      $stmt=$conn->prepare($sql);
      $stmt->execute();
      $stmt->bind_result($songid, $title,$songPath,$singer);
      $array= array();
      while ($stmt->fetch()) {
          $array[]= array('id' =>$songid ,'singer'=>$singer ,'title'=>$title,'path'=>'http://musixcloud.xyz/asset/php/play.php?url='.$songPath);
          $songjson=json_encode($array,JSON_UNESCAPED_UNICODE);
      }
    }
}
function login($fbuid,$email,$name,$fbgender,$mobtoken){
  session_start();
      $status;
      include '../config/db.php';
      $sql="select userid,fbuid,email from user where fbuid=?";
      $stmt=$conn->prepare($sql);
      $stmt->bind_param('s',$fbuid);
      $stmt->execute();
      $stmt->bind_result($reuid,$rfbuid,$remail);
      $stmt->fetch();
      if($rfbuid==""){
        $uid=null;
        $dob=null;
        $intro=null;
        $type=1;
        $expdate=null;
        $regdate=date('Y-m-d');
        $regip=$_SERVER['REMOTE_ADDR'];
        $pass=rand();
        $pwd=md5($pass);
        $token=$mobtoken;
          $query="INSERT INTO user (userid,fbuid,email,password,type,fullname,gender,dob,intro,expDate,regDate,regIp,uToken)VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
          if ($stmt=$conn->prepare($query)) {
            $stmt->bind_param("isssissssssss",$uid,$fbuid,$email,$pwd,$type,$name,$fbgender,$dob,$intro,$expdate,$regdate,$regip,$token);
            $stmt->execute();
            $status='true';

            $profile = array('uid'=>$uid,'fbuid'=>$fbuid,'email'=>$email,'name'=>$name,'type'=>$type,'gender'=>$fbgender,'dob'=>$dob,'intro'=>$intro,'expdate'=>$expdate,'regdate'=>$regdate);
            $result = array('status' => $status,'error'=>$stmt->error,'profile'=>$profile);
            printf(json_encode($result));
          }else{
            $status="false";
            $result = array('status' => $status,'error'=>$stmt->error);
            printf(json_encode($result));
          }
      }else{
        include '../config/db.php';
        $query="select * from user where fbuid=?";
        $stmt=$conn->prepare($query);
        $stmt->bind_param("s",$fbuid);
        $stmt->execute();
        $stmt->bind_result($reuid,$refbuid,$refbemail,$repwd,$retype,$refullname,$regender,$redob,$reintro,$reexpdate,$reregdate,$reregip,$rtoken,$block);
        $stmt->fetch();
        if($rtoken!=$mobtoken){
          include '../config/db.php';
          $sql="update user set uToken=? where fbuid=?";
          $stmt=$conn->prepare($sql);
          $stmt->bind_param("ss",$mobtoken,$fbuid);
          $stmt->execute();
          $status='true';
            $profile = array('uid'=>$reuid,'fbuid'=>$refbuid,'email'=>$refbemail,'name'=>$refullname,'type'=>$retype,'gender'=>$regender,'dob'=>$redob,'intro'=>$reintro,'expdate'=>$reexpdate,'regdate'=>$reregdate);
            $result = array('status' => $status, 'profile'=>$profile);
          printf(json_encode($result));
          printf($stmt->error);
        }elseif($rtoken==$mobtoken){
          $status='true';
          //$result = array('status'=> $status,'uid'=>$reuid,'fbuid'=>$refbuid,'email'=>$refbemail,'name'=>$refullname,'type'=>$retype,'gender'=>$regender,'dob'=>$redob,'intro'=>$reintro,'expdate'=>$reexpdate,'regdate'=>$reregdate);
          $profile = array('uid'=>$reuid,'fbuid'=>$refbuid,'email'=>$refbemail,'name'=>$refullname,'type'=>$retype,'gender'=>$regender,'dob'=>$redob,'intro'=>$reintro,'expdate'=>$reexpdate,'regdate'=>$reregdate);
          $result = array('status' => $status, 'profile'=>$profile);
          printf(json_encode($result));
          printf($stmt->error);
        }
      }
}

function viewprofile($mobtoken){
  include '../config/db.php';
  session_start();
  $sql="select * from user where uToken=?";
  if($stmt=$conn->prepare($sql)){
    $stmt->bind_param('s',$mobtoken);
    $stmt->execute();
    $data = $stmt->get_result();
  	     $result = array();
  	     while($row = $data->fetch_assoc()) {
  	          $result = $row;
              $res = array('status' => 'true', 'profile'=>$result);
              echo json_encode($res);
  	      }
  }
}

function users(){
  include '../config/db.php';
  $sql="select userid,fullname from user";
  $stmt=$conn->prepare($sql);
  $stmt=bind_param("i",$senduid);
  $stmt->execute();
  $data = $stmt->get_result();
       $result = array();
       while($row = $data->fetch_assoc()) {

            $result[] = $row;
            echo json_encode($result,JSON_UNESCAPED_UNICODE);
        }

}

function listall($mobtoken){
  include '../config/db.php';
  $sql="select type from user where uToken=?";
  if($stmt=$conn->prepare($sql)){
    $stmt->bind_param('s',$mobtoken);
    $stmt->execute();
    $stmt->bind_result($retype);
    $stmt->fetch();
    if($retype==1){
      $sql="select ";
    }
}

function albumlist($mobtoken,$senduid){
  include '../config/db.php';
  $sql="select type from user where uToken=?";
  if($stmt=$conn->prepare($sql)){
    $stmt->bind_param('s',$mobtoken);
    $stmt->execute();
    $stmt->bind_result($retype);
    $stmt->fetch();
    if($retype==1){
      $sql="SELECT music.title, music.songPath,music.album  FROM music INNER JOIN user ON music.userid=user.userid WHERE user.type=1 and music.album!='' and user.userid=?";
      $stmt=$conn->prepare($sql);
      $stmt=bind_param("i",$senduid);
      $stmt->execute();
      $data = $stmt->get_result();
    	     $result = array();
    	     while($row = $data->fetch_assoc()) {

    	          $result[] = $row;
                echo json_encode($result,JSON_UNESCAPED_UNICODE);
    	      }

    }elseif ($retype==2) {
      $sql="SELECT music.title, music.songPath,music.album  FROM music INNER JOIN user ON music.userid=user.userid WHERE user.type=2 and music.album!=''and user.userid=?";
      $stmt=$conn->prepare($sql);
      $stmt=bind_param("i",$senduid);
      $stmt->execute();
      $data = $stmt->get_result();
           $result = array();
           while($row = $data->fetch_assoc()) {

                $result[] = $row;
                echo json_encode($result,JSON_UNESCAPED_UNICODE);
            }
    }
  }
}}
 ?>
