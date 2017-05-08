<?php
namespace CakePhpMailer\Mailer;

use Cake\Core\StaticConfigTrait;
use Cake\View\ViewVarsTrait;
use PHPMailer;

class Email extends PHPMailer
{
    use StaticConfigTrait;
    use ViewVarsTrait;

    /**
     * Configuration profiles for transports.
     *
     * @var array
     */
    protected static $_transportConfig = [];

    /**
     * Sets transport configuration.
     *
     * Use this method to define transports to use in delivery profiles.
     * Once defined you cannot edit the configurations, and must use
     * Email::dropTransport() to flush the configuration first.
     *
     * When using an array of configuration data a new transport
     * will be constructed for each message sent. When using a Closure, the
     * closure will be evaluated for each message.
     *
     * The `className` is used to define the class to use for a transport.
     * It can either be a short name, or a fully qualified class name
     *
     * @param string|array $key The configuration name to write. Or
     *   an array of multiple transports to set.
     * @param array|\Cake\Mailer\AbstractTransport|null $config Either an array of configuration
     *   data, or a transport instance. Null when using key as array.
     * @return void
     * @throws \BadMethodCallException When modifying an existing configuration.
     */
    public static function setConfigTransport($key, $config = null)
    {
        if (is_array($key)) {
            foreach ($key as $name => $settings) {
                static::setConfigTransport($name, $settings);
            }

            return;
        }

        if (isset(static::$_transportConfig[$key])) {
            throw new BadMethodCallException(sprintf('Cannot modify an existing config "%s"', $key));
        }

        if (is_object($config)) {
            $config = ['className' => $config];
        }

        if (isset($config['url'])) {
            $parsed = static::parseDsn($config['url']);
            unset($config['url']);
            $config = $parsed + $config;
        }

        static::$_transportConfig[$key] = $config;
    }

    /**
     * Constructor.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $key = 'default';
        if (static::$_transportConfig[$key]['scheme'] === 'smtp') {
            $this->Mailer = static::$_transportConfig[$key]['scheme'];
            $this->Host = static::$_transportConfig[$key]['host'];
            $this->Port = static::$_transportConfig[$key]['port'];
        }

        $this->CharSet = static::$_config[$key]['charset'];
        $this->Encoding = 'quoted-printable';
        $this->WordWrap = 75;
        $this->ContentType = 'text/html';
        $this->Sender = static::$_config[$key]['sender'];

        $this->emailFormat(static::$_config[$key]['emailFormat']);
    }

    /**
     * email format as plain or html
     * @param  string $format format string
     * @return self
     */
    public function emailFormat($format)
    {
        if (preg_match('#(plain|^text$)#', $format)) {
            $this->isHtml(false);
        } else {
            $this->isHtml(true);
        }

        return $this;
    }

    /**
     * anonymize the email by setting sender and mail from to $email
     * @param  string $email sender email
     * @param  string $name  sender name
     * @return self
     * @throws InvalidArgumentException
     */
    public function anonymize($email, $name = null)
    {
        if (empty($email)) {
            throw new InvalidArgumentException('no email');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('bad email');
        }

        $this->From = $email;
        $this->FromName = $name;
        $this->Sender = $this->From;

        return $this;
    }

    /**
     * to
     * @param  string $email sender email
     * @param  string $name  sender name
     * @return self
     */
    public function to($email, $name = '')
    {
        parent::AddAddress($email, $name);

        return $this;
    }

    /**
     * subject
     * @param  string $subject subject email
     * @return self
     */
    public function subject($subject)
    {
        $this->Subject = $subject;

        return $this;
    }

    /**
     * set the email body
     * @param  text $content email
     * @return self
     */
    public function body($content)
    {
        $this->Body = $content;

        return $this;
    }

    /**
     * reset all recipients
     * @return self
     */
    public function clearAllRecipients()
    {
        parent::clearAllRecipients();

        return $this;
    }

    /**
     * completely reset email
     * @return self
     */
    public function reset()
    {
        $this->ClearAllRecipients();
        $this->From = $this->FromName = $this->Sender = '';
        $this->Subject = '';
        $this->Body = '';

        return $this;
    }

    /**
     * send email
     * can override recipients in case of testing
     * @return bool
     */
    public function send()
    {
        // change recipients if testing
        if (!empty($this->Testing)) {
            $this->ClearCustomHeaders();
            $this->AddCustomHeader('X-Mailer-Debug:1');
            if (isset($this->all_recipients)) {
                $this->AddCustomHeader('X-Mailer-Original-Rcpt:' . json_encode(array_keys($this->all_recipients)));
            }

            $this->ClearAllRecipients();
            foreach ($this->Testing as $rcpt) {
                $this->AddAddress($rcpt);
            }
        }

        return parent::send();
    }

    /**
     * echo the email Body and exit.
     * @return void
     */
    public function debug()
    {
        echo $this->Body;
        exit;
    }
}
