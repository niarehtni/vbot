<?php
/**
 * Created by PhpStorm.
 * User: Hanson
 * Date: 2016/12/15
 * Time: 0:12
 */

namespace Hanson\Robot\Message;


use Hanson\Robot\Core\Server;
use Hanson\Robot\Collections\Account;
use Hanson\Robot\Collections\ContactAccount;
use Hanson\Robot\Collections\OfficialAccount;
use Hanson\Robot\Collections\SpecialAccount;
use Hanson\Robot\Models\Content;
use Hanson\Robot\Models\Sender;

class Message
{

    public $from;

    /**
     * @var array 当from为群组时，sender为用户发送者
     */
    public $sender;

    public $to;

    public $content;

    public $time;

    /**
     * @var string 消息发送者类型
     */
    public $FromType;

    /**
     * @var string 消息类型
     */
    public $type;

    static $message = [];

    const USER_TYPE = [
        0 => 'Init',
        1 => 'Self',
        2 => 'FileHelper',
        3 => 'Group',
        4 => 'Contact',
        5 => 'Public',
        6 => 'Special',
        99 => 'UnKnown',
    ];

    public $rawMsg;

//    const MESSAGE_TYPE = [
//        0 => 'Text',
//    ]

    public function make($selector, $msg)
    {

        $this->rawMsg = $msg;

//        $this->sender = new Sender();
//        $this->content = new Content();

//        $this->setSender();

        $this->setFrom();

        $this->setTo();

        $this->setContent();

        $this->setType();

        $this->setFromType();

        return $this;
    }

    /**
     * 设置消息发送者
     */
    private function setFrom()
    {
        $account = Account::getInstance();

        $from = $this->rawMsg['FromUserName'];

        $fromType = substr($this->rawMsg['FromUserName'], 0, 2) === '@@' ? Account::GROUP_MEMBER : Account::NORMAL_MEMBER;

        $this->from = $account->getContact($from, $fromType);


//
//        if($this->sender->type !== 'Group'){
//            $this->sender->from = $account->getContact($this->rawMsg['FromUserName'], Account::NORMAL_MEMBER);
//        }
//
//        $this->sender->name = html_entity_decode($this->sender->name);
    }

    private function setTo()
    {
        $account = Account::getInstance();

        $from = $this->rawMsg['ToUserName'];

        $fromType = substr($this->rawMsg['ToUserName'], 0, 2) === '@@' ? Account::GROUP_MEMBER : Account::NORMAL_MEMBER;

        $this->to = $account->getContact($from, $fromType);
    }

    private function setFromType()
    {
        if ($this->rawMsg['MsgType'] == 51) {
            $this->FromType = 'System';
        } elseif ($this->rawMsg['MsgType'] == 37) {
            $this->FromType = 'FriendRequest';
        } elseif (Server::isMyself($this->rawMsg['FromUserName'])) {
            $this->FromType = 'Self';
        } elseif ($this->rawMsg['ToUserName'] === 'filehelper') {
            $this->FromType = 'FileHelper';
        } elseif (substr($this->rawMsg['FromUserName'], 0, 2) === '@@') { # group
            $this->FromType = 'Group';
        } elseif (ContactAccount::getInstance()->isContact($this->rawMsg['FromUserName'])) {
            $this->FromType = 'Contact';
        } elseif (OfficialAccount::getInstance()->isPublic($this->rawMsg['FromUserName'])) {
            $this->FromType = 'Public';
        } elseif (SpecialAccount::getInstance()->get($this->rawMsg['FromUserName'], false)) {
            $this->FromType = 'Special';
        } else {
            $this->FromType = 'Unknown';
        }
    }

    private function setType()
    {
//        $msgType = $msg['MsgType'];
        $this->rawMsg['Content'] = html_entity_decode($this->rawMsg['Content']);
//        $msgId = $msg['MsgId'];

        $this->setTypeByFrom();

        $this->handleMessageByType();
    }

    /**
     * 根据消息来源处理消息
     */
    private function setTypeByFrom()
    {
        if($this->FromType === 'System'){
            $this->type = 'Empty';
        }elseif ($this->FromType === 'FileHelper'){ # File Helper
            $this->type = 'Text';
            $this->content->msg = $this->formatContent($this->rawMsg['Content']);
        }elseif ($this->FromType === 'Group'){
            $this->handleGroupContent($this->rawMsg['Content']);
        }
    }

    /**
     * 处理消息类型
     */
    private function handleMessageByType()
    {
        switch($this->rawMsg['MsgType']){
            case 1:
                if(Location::isLocation($this->rawMsg['Content'])){
//                $this->setLocationMessage();
                    $this->type = 'Location';
                }else{
                    $this->type = 'Text';
                }
                break;
            case 3:
                $this->type = 'Image';
                break;
            case 34:
                $this->type = 'Voice';
                break;
            case 37:
                $this->type = 'AddUser';
                break;
            case 42:
                $this->type = 'Recommend';
                break;
            case 47:
                $this->type = 'Animation';
                break;
            case 49:
                $this->type = 'Animation';
                break;

        }
    }

    /**
     * 设置当前message 为 location
     */
    private function setLocationMessage()
    {
        $this->FromType = 'Location';
        $this->url = $this->rawMsg['Url'];
        $this->content->msg = Location::getLocationText($this->rawMsg['Content']);
    }

    /**
     * handle group content
     *
     * @param $content
     */
    private function handleGroupContent($content)
    {
        list($uid, $content) = explode('<br/>', $content, 2);

        $this->sender = Account::getInstance()->get('normalMember')[substr($uid, 0, -1)];
        $this->rawMsg['Content'] = $this->formatContent($content);
    }

    private function formatContent($content)
    {
        return str_replace('<br/>', '\n', $content);
    }

}