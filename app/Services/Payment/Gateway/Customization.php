<?php

namespace App\Services\Payment\Gateway;

class Customization
{
    /**
     * The default gateway title
     *
     * @var string
     */
    protected static $defaultTitle;

    /**
     * The default gateway logo
     *
     * @var string
     */
    protected static $defaultLogo;

    /**
     * The gateway title
     *
     * @var string
     */
    protected $title;

    /**
     * The gateway logo url
     *
     * @var string
     */
    protected $logo;

    /**
     * The gateway success callback
     *
     * @var string
     */
    protected $success;

    /**
     * The gateway cancel callback
     *
     * @var string
     */
    protected $cancel;

    /**
     * The gateway failed callback
     *
     * @var string
     */
    protected $failed;

    public function __construct(
        string $title = null,
        string $logo = null,
        string $success = null,
        string $cancel = null,
        string $failed = null
    ) {
        $this->title = $title ?? self::$defaultTitle;
        $this->logo = $logo ?? self::$defaultLogo;
        $this->success = $success;
        $this->cancel = $cancel;
        $this->failed = $failed;
    }

    /**
     * Get gateway title
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if ($key === 'title') {
            return (isset($this->title) && !empty($this->title)) ||
            (isset(self::$defaultTitle) && !empty(self::$defaultTitle));
        }

        if ($key === 'logo') {
            return (isset($this->logo) && !empty($this->logo)) ||
            (isset(self::$defaultLogo) && !empty(self::$defaultLogo));
        }

        return isset($this->{$key}) && !empty($this->{$key});
    }

    /**
     * Set default title
     *
     * @param string $title
     */
    public static function defaultTitle(string $title)
    {
        static::$defaultTitle = $title;
    }

    /**
     * Set default logo
     *
     * @param string $logo
     */
    public static function defaultLogo(string $logo)
    {
        static::$defaultLogo = $logo;
    }

    /**
     * Get gateway title
     *
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Get gateway logo
     *
     * @return string
     */
    public function logo(): string
    {
        return $this->logo;
    }

    /**
     * Get gateway success
     *
     * @return string
     */
    public function success(): string
    {
        return $this->success;
    }

    /**
     * Get gateway cancel
     *
     * @return string
     */
    public function cancel(): string
    {
        return $this->cancel;
    }

    /**
     * Get gateway failed
     *
     * @return string
     */
    public function failed(): string
    {
        return $this->failed;
    }
}
