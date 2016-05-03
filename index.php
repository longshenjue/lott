<?php

//$uid = $_GET['uid'];
//$uid = intval($uid);
// 需要验证用户
//if ($uid < 1) {
//    return false;
//}

$uid = $rand = rand(1, 200000);
$redis = new Redis();
$redis->pconnect("localhost");
// 统计次数
$redis->incr('total_times');


$uidLockKey = 'card_lock_' . $uid;

$isLock = $redis->setnx($uidLockKey, 1);
if (!$isLock) {
    echo '不要点太快哦';
    die;
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
// 已经获得的卡片 考虑之后 还是查询 数据库比较靠谱
$getCards = $redis->lRange("card_infos_" . $uid, 0, -1);


//$db = new mdlDb($uid);
//$getCards = $db->select($uid);
$cardNum = 0;

if (in_array('card_1', $getCards)) {
    $cardNum++;
}
if (in_array('card_2', $getCards)) {
    $cardNum++;
}
if (in_array('card_3', $getCards)) {
    $cardNum++;
}
if (in_array('card_4', $getCards)) {
    $cardNum++;
}
if (in_array('card_5', $getCards)) {
    $cardNum++;
}

//$getCards['cardNum'] = $cardNum;

if ($cardNum == 5) {
    echo '卡片收集成功！';
    die;
    return false;
}

$rand = rand(1, 140);
echo '随机数:' . $rand . "<br/>";
if ($rand == 1) {
    $cardKey = 4;
    if (rand(1, 800) == 1) {
        $cardKey = 5;
    }
    $rand_prize = rand(1, $cardKey);
    echo 'roll到的数字:' . $rand_prize;
    if (in_array('card_' . $rand_prize, $getCards)) {
        echo '获奖重复';
        die;
        return false;
    } else {
//        if ($card_surplus = $redis->get('card_surplus_' .date('ymd') . '_' . $rand_prize) > 0) {
        $key = 'card_cangetcard_' . $uid;
        $redis->incr($key);
        $redis->expireAt($key, strtotime('tomorrow'));

        // 成功获得卡片，写入数据库
        $db = new mdlDb($uid);
        $db->add($uid, $rand_prize);
        $redis->rPush("card_infos_" . $uid, "card_$rand_prize");
//        $redis->set('card_surplus_' .date('ymd') . '_' . $rand_prize, $card_surplus-1);
        echo '!!!!!!!!!!!!!!!!!!';
        return true;
    }
} else {
    echo 'ROLL失败了！';
    return false;
}

class mdlDb
{
    public $db;

    function __construct($uid)
    {
        $mysqli = new mysqli();
        $mysqli->connect('192.168.0.103', 'root', '', 'ting');
        $this->db = $mysqli;

        $sql = 'select * from l_user';
        if ($uid) {
            $sql .= " where uid = $uid";
        }
        $result = $mysqli->query($sql);
        if ($result->num_rows == 0) {
            $sql_insert = "insert into l_user (uid) VALUE ('$uid')";
            $this->db->query($sql_insert);
        }
    }

    function select($uid)
    {
        $sql = 'select * from l_user';
        if ($uid) {
            $sql .= " where uid = $uid";
        }
        $result = $this->db->query($sql);
        if ($row = $result->fetch_assoc()) {
            return $row;
        } else {
            $sql_insert = "insert into l_user (uid) VALUE ('$uid')";
            $this->db->query($sql_insert);
            $result = $this->db->query($sql);
            return $result->fetch_assoc();
        }
    }

    function add($uid, $cid)
    {
        $sql_update = "update l_user set card_$cid = '1' WHERE uid = $uid";
        $this->db->query($sql_update);
    }
}

