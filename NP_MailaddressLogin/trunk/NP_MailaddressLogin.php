<?php

class NP_MailaddressLogin extends NucleusPlugin
{
    function getName()
    {
        return 'Login by Mailaddress';
    }

    function getAuthor()
    {
        return 'shizuki';
    }

    function getURL()
    {
        return 'http://www.souhoudou.jp/';
    }

    function getVersion()
    {
        return '1.0';
    }

    function getDescription()
    {
        return 'Login to NucleusCMS via mailaddress';
    }

    function supportsFeature($what)
    {
        switch ($what) {
            case 'SqlTablePrefix':
                return 1;
            default:
                return 0;
        }
    }

    function getEventList()
    {
        return array(
            'ValidateForm',
            'PostRegister',
            'CustomLogin',
        );
    }

    function event_CustomLogin($data)
    {
        $query = '
            SELECT
                mname as result
            FROM
                ' . sql_table('member') . '
            WHERE
                memail = "' . sql_real_escape_string($data['login']) .    '" and
                mpassword = "' . md5($data['password']) . '"';
        if ($mname = quickQuery($query)) {
            $data['login']      = $mname;
            $data['success']    = 1;
            $data['allowlocal'] = 1;
        }
    }

    function event_PostRegister($data)
    {
        $member   = $data['member'];
        $mailAddr = $member->getEmail();
        if (empty($mailAddr)) {
            return;
        }
        $userMail = quickQuery('
            SELECT
                COUNT(*) as result
            FROM
                ' . sql_table('member') . '
            WHERE
                memail = "' . sql_real_escape_string($mailAddr) . '"'
        );
        if ($userMail > 1) {
            $adm = new ADMIN;
            $adm->deleteOneMember($member->getID());
            return;
        }
    }

    function event_ValidateForm($data)
    {
        global $CONF;
        $accountCreate = ($data['type'] == 'membermail' && $data['error'] === 1);
        if (!$CONF['AllowMemberCreate'] || !$accountCreate) {
            return;
        }
        $mailAddr = postVar('email');
        if (empty($mailAddr)) {
            $data['error'] = 'Mail address is empty';
            return;
        }
        $userMail = quickQuery('
            SELECT
                COUNT(*) as result
            FROM
                ' . sql_table('member') . 
                '
            WHERE
                memail = "' . sql_real_escape_string($mailAddr) . '"'
        );
        if ($userMail > 0) {
            $data['error'] = 'Mail address is avaiable';
            return;
        }
    }


}

