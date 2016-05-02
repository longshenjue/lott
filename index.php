<?php


$mysqli=new mysqli();
  $mysqli->connect('192.168.0.103','root','','ting');
   // 创建查询
   $sqlstr='select * from l_user';
  //发送查询给MySql
   $result=$mysqli->query($sqlstr);
    while($row=$result->fetch_object())
    { 
      $id=$row->l_uId;
       echo $id;
	   $name=$row->l_uName;
       echo $name;
    }
	exit;
$uid = 111;
// 需要验证用户
if ($uid < 1) {
    return false;
}
$uid = $rand = rand(1, 100000);
$redis = new Redis();
$redis->pconnect("localhost");
$uidLockKey = 'card_lock_' . $uid;

$isLock = $redis->setnx($uidLockKey, 1);
if (!$isLock) {
    echo '不要点太快哦';die;
    return false;
}
$redis->expire($uidLockKey, 1);



$key = 'card_cangetcard_' . $uid;
$can = $redis->get($key);

if ($can > 1) {
    echo '没有获奖次数';
    die;
    return false;
}
$palyTime = 2;//getPlayTime($uid);// 判断是否剩余游戏次数
$key = 'card_play_times_' . $uid;
$palyTime = $redis->incr($key);
$redis->expireAt($key, strtotime('tomorrow'));

if ($palyTime >= 10) {
    echo '今天抽奖次数已达上限！';
    return false;
}

$getCards = [];//getCard($uid); // 已经获得的卡片 考虑之后 还是查询 数据库比较靠谱
$getCards = [
    'cardInfo' => [
        1 => '1',
        2 => '1',
        3 => '0',
        4 => '0',
        5 => '0'],
    'cardNum' => 2
];
if ($getCards['cardNum'] == 5) {
    echo '卡片收集成功！';
    die;
    return false;
}

$rand = rand(1, 100);
echo '随机数:'.$rand;
if ($rand == 1) {
    $cardKey = 4;
    if (date('ymd') == '160505'){
        $cardKey = 5;
    }
    $rand_prize = rand(1, $cardKey);
    echo 'roll到的数字:'.$rand_prize;
    foreach ($getCards['cardInfo'] as $info) {
        if ($getCards['cardInfo'][$rand_prize] == 1) {
            echo '获奖重复';
            die;
            return false;
        } else {
// 成功获得卡片，写入数据库
//self::setNoPrize($uid); // 每天只能中一次奖
            $key = 'card_cangetcard_' . $uid;
            $redis->incr($key);
            $redis->expireAt($key, strtotime('tomorrow'));
            return true;
        }
    }
} else {
    echo '失败了！';
    return false;
}


exit;
