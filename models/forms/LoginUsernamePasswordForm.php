<?php

namespace app\models\forms;

use app\components\UrlHelper;
use app\models\db\EMailLog;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\Login;
use app\models\exceptions\MailNotSent;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Site as SiteSettings;
use yii\base\Model;

class LoginUsernamePasswordForm extends Model
{
    const PASSWORD_MIN_LEN = 4;

    /** @var string */
    public $username;
    public $password;
    public $passwordConfirm;
    public $name;
    public $error;

    /** @var bool */
    public $createAccount = false;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['contact', 'required', 'message' => 'Du musst eine Kontaktadresse angeben.'],
            [['createAccount', 'hasComments', 'openNow'], 'boolean'],
            [['username', 'password', 'passwordConfirm', 'name', 'createAccount'], 'safe'],
        ];
    }

    /**
     * @param User $user
     * @throws MailNotSent
     */
    private function sendConfirmationEmail(User $user)
    {
        $bestCode = $user->createEmailConfirmationCode();
        $params   = ['user/confirmregistration', 'email' => $this->username, 'code' => $bestCode, 'subdomain' => null];
        $link     = UrlHelper::absolutizeLink(UrlHelper::createUrl($params));

        $sendText = "Hallo,\n\num deinen Antragsgrün-Zugang zu aktivieren, klicke entweder auf folgenden Link:\n";
        $sendText .= "%bestLink%\n\n"
            . "...oder gib, wenn du auf Antragsgrün danach gefragt wirst, folgenden Code ein: %code%\n\n"
            . "Liebe Grüße,\n\tDas Antragsgrün-Team.";

        \app\components\mail\Tools::sendWithLog(
            EMailLog::TYPE_REGISTRATION,
            null,
            $this->username,
            $user->id,
            'Anmeldung bei Antragsgrün',
            $sendText,
            '',
            [
                '%code%'     => $bestCode,
                '%bestLink%' => $link,
            ]
        );
    }


    /**
     * @param Site|null $site
     * @throws Login
     */
    private function doCreateAccountValidate($site)
    {
        if ($site) {
            $methods = $site->getSettings()->loginMethods;
        } else {
            $methods = SiteSettings::$SITE_MANAGER_LOGIN_METHODS;
        }

        if (!in_array(SiteSettings::LOGIN_STD, $methods)) {
            $this->error = 'Das Anlegen von Accounts ist bei dieser Veranstaltung nicht möglich.';
            throw new Login($this->error);
        }
        if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]+$/siu", $this->username)) {
            $this->error = 'Bitte gib eine gültige E-Mail-Adresse als Benutzer*innenname ein.';
            throw new Login($this->error);
        }
        if (strlen($this->password) < static::PASSWORD_MIN_LEN) {
            $this->error = 'Das Passwort muss mindestens sechs Buchstaben lang sein.';
            throw new Login($this->error);
        }
        if ($this->password != $this->passwordConfirm) {
            $this->error = 'Die beiden angegebenen Passwörter stimmen nicht überein.';
            throw new Login($this->error);
        }
        if ($this->name == '') {
            $this->error = 'Bitte gib deinen Namen ein.';
            throw new Login($this->error);
        }

        $auth     = 'email:' . $this->username;
        $existing = User::findOne(['auth' => $auth]);
        if ($existing) {
            /** @var User $existing */
            $this->error = 'Es existiert bereits ein Zugang mit dieser E-Mail-Adresse (' . $this->username . ')';
            throw new Login($this->error);
        }
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws Login
     */
    private function doCreateAccount($site)
    {
        $this->doCreateAccountValidate($site);

        $user         = new User();
        $user->auth   = 'email:' . $this->username;
        $user->name   = $this->name;
        $user->email  = $this->username;
        $user->pwdEnc = password_hash($this->password, PASSWORD_DEFAULT);

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if ($params->confirmEmailAddresses) {
            $user->status         = User::STATUS_UNCONFIRMED;
            $user->emailConfirmed = 0;
        } else {
            $user->status         = User::STATUS_CONFIRMED;
            $user->emailConfirmed = 1;
        }

        if ($user->save()) {
            if ($params->confirmEmailAddresses) {
                $user->refresh();
                try {
                    $this->sendConfirmationEmail($user);
                    return $user;
                } catch (MailNotSent $e) {
                    $this->error = $e->getMessage();
                    throw new Login($this->error);
                }
            } else {
                return $user;
            }
        } else {
            $this->error = \Yii::t('base', 'err_unknown');
            throw new Login($this->error);
        }
    }

    /**
     * @return User[]
     */
    private function getCandidatesWurzelwerk()
    {
        $wwlike = "openid:https://service.gruene.de/%";
        $auth   = "openid:https://service.gruene.de/openid/" . $this->username;
        $sql    = "SELECT * FROM user WHERE auth = '" . addslashes($auth) . "'";
        $sql .= " OR (auth LIKE '$wwlike' AND email = '" . addslashes($this->username) . "')";
        return User::findBySql($sql)->all();
    }

    /**
     * @return User[]
     */
    private function getCandidatesStdLogin()
    {
        $sql_where1 = "auth = 'email:" . addslashes($this->username) . "'";
        return User::findBySql("SELECT * FROM user WHERE $sql_where1")->all();
    }

    /**
     * @param Site|null $site
     * @return User[]
     */
    private function getCandidates($site)
    {
        if ($site) {
            $methods = $site->getSettings()->loginMethods;
        } else {
            $methods = SiteSettings::$SITE_MANAGER_LOGIN_METHODS;
        }

        /** @var AntragsgruenApp $app */
        $app        = \yii::$app->params;
        $candidates = [];
        if (in_array(SiteSettings::LOGIN_STD, $methods)) {
            $candidates = array_merge($candidates, $this->getCandidatesStdLogin());
        }
        if (in_array(SiteSettings::LOGIN_WURZELWERK, $methods) && $app->hasWurzelwerk) {
            $candidates = array_merge($candidates, $this->getCandidatesWurzelwerk());
        }
        return $candidates;
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws Login
     */
    private function checkLogin($site)
    {
        if ($site) {
            $methods = $site->getSettings()->loginMethods;
        } else {
            $methods = SiteSettings::$SITE_MANAGER_LOGIN_METHODS;
        }

        if (!in_array(SiteSettings::LOGIN_STD, $methods)) {
            $this->error = 'Das Login mit Benutzer*innenname und Passwort ist bei dieser Veranstaltung nicht möglich.';
            throw new Login($this->error);
        }
        $candidates = $this->getCandidates($site);

        if (count($candidates) == 0) {
            $this->error = 'Benutzer*innenname nicht gefunden.';
            throw new Login($this->error);
        }
        foreach ($candidates as $tryUser) {
            if ($tryUser->validatePassword($this->password)) {
                return $tryUser;
            }
        }
        $this->error = 'Falsches Passwort.';
        throw new Login($this->error);
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws Login
     */
    public function getOrCreateUser($site)
    {
        if ($this->createAccount) {
            return $this->doCreateAccount($site);
        } else {
            return $this->checkLogin($site);
        }
    }
}
