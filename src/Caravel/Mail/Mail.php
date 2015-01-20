<?php

namespace Caravel\Mail;

use Caravel\Http\View;
use Caravel\Config\Config;

class Mail
{
    /**
     * Send a new message when only a plain part.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  mixed  $callback
     * @return void
     */
    public static function plain($view, array $data, $callback)
    {
        return self::send(array('text' => $view), $data, $callback);
    }

    /**
     * Send a new message using a view.
     *
     * @param  string $view
     * @param  array  $data
     * @param  Closure|string  $callback
     * @return void
     */
    public static function send($view, array $data, $callback)
    {
        list($html, $plain) = self::parseView($view);

        $mailer = self::getMailer();

        $data['message'] = $message = self::createMessage();

        call_user_func($callback, $message);

        self::addContent($message, $html, $plain, $data);

        $message = $message->getSwiftMessage();

        return $mailer->send($message);
    }

    protected static function createMessage()
    {
        // Create a message
        $message = new Message(new \Swift_Message);

        return $message;
    }

    protected static function getMailer()
    {
        $transport = self::getTransport();

        // Create the Mailer using your created Transport
        $mailer = \Swift_Mailer::newInstance($transport);

        return $mailer;
    }

    protected static function getTransport()
    {
        $config = Config::get("mail");

        if (empty($config->driver) || $config->driver == 'mail') {

            $transport = \Swift_MailTransport::newInstance();

        } elseif ($config->driver == 'sendmail') {

            $transport = Swift_SendmailTransport::newInstance($config->sendmail);

        } elseif ($config->driver == 'smtp') {

            $transport = \Swift_SmtpTransport::newInstance()
                ->setHost($config->host)
                ->setPort($config->port)
                ->setEncryption($config->encryption)
                ->setUsername($config->username)
                ->setPassword($password)
            ;

        } else {

            throw new \RuntimeException("Invalid transport.");

        }

        return $transport;
    }

    /**
     * Parse the given view name or array.
     *
     * @param  string|array  $view
     * @return array
     */
    protected static function parseView($view)
    {
        if (is_string($view)) {
            return array($view, null);
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since must should contain both views with numeric keys.
        if (is_array($view) && (isset($view[0]) || isset($view[1]))) {
            return array(
                isset($view[0]) ? $view[0] : null,
                isset($view[1]) ? $view[1] : null,
            );
        }

        // If the view is an array, but doesn't contain numeric keys, we will assume
        // the the views are being explicitly specified and will extract them via
        // named keys instead, allowing the developers to use one or the other.
        elseif (is_array($view)) {
            return array(
                isset($view['html']) ? $view['html'] : null,
                isset($view['text']) ? $view['text'] : null,
            );
        }

        throw new \RuntimeException("Invalid view.");
    }

    /**
     * Add the content to a given message.
     *
     * @param  \Caravel\Mail\Message  $message
     * @param  string  $view
     * @param  string  $plain
     * @param  array   $data
     * @return void
     */
    protected static function addContent($message, $view, $plain, $data)
    {
        if (isset($view)) {
            $message->setBody(self::getView($view, $data), 'text/html');
        }

        if (isset($plain)) {
            $message->addPart(self::getView($plain, $data), 'text/plain');
        }
    }

    protected static function getView($view, $data)
    {
        ob_start();

        $content = View::get($view, $data);

        ob_end_flush();

        return $content;
    }
}
