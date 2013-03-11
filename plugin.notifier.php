<?php
/**
 * WeNotif's subcription topic's notifier file
 * 
 * @package Dragooon:WeNotif-Subs-Topics
 * @author Shitiz "Dragooon" Garg <Email mail@dragooon.net> <Url http://smf-media.com>
 * @copyright 2012-2013, Shitiz "Dragooon" Garg <mail@dragooon.net>
 * @license
 *      Licensed under "New BSD License (3-clause version)"
 *      http://www.opensource.org/licenses/BSD-3-Clause
 * @version 1.0
 */

/**
 * Callback for notifiers to register themselves
 *
 * @param array &$subscribers
 * @return void
 */
function wenotif_subs_topics_hook_callback(&$notifiers)
{
    $notifiers['topicsubs'] = new TopicSubsNotifier();
}

/**
 * Notifier interface for topic subscriber
 */
class TopicSubsNotifier extends Notifier
{
    /**
     * Constructor, loads this plugin's language
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        loadPluginLanguage('Dragooon:WeNotif-Subs-Topics', 'plugin');
    }

    /**
     * Callback for returning the URL of the object
     *
     * @access public
     * @param Notification $notification
     * @return string
     */
    public function getURL(Notification $notification)
    {
        global $scripturl;

        $data = $notification->getData();

        return $scripturl . '?topic=' . $notification->getObject() . '.msg' . $data['msg'] . '#msg' . $data['msg'];
    }

    /**
     * Callback for getting the text to display on the notification screen
     *
     * @access public
     * @param Notification $notification
     * @return string The text this notification wants to display
     */
    public function getText(Notification $notification)
    {
        global $txt;

        $data = $notification->getData();

        return sprintf($txt['topicsubs_text'], $data['subject']);
    }

    /**
     * Returns the name of this notifier
     *
     * @access public
     * @return string
     */
    public function getName()
    {
        return 'topicsubs';
    }

    /**
     * Callback for handling multiple notifications on the same object
     *
     * @access public
     * @param Notification $notification
     * @param array &$data Reference to the new notification's data, if something needs to be altered
     * @return bool, if false then a new notification is not created but the current one's time is updated
     */
    public function handleMultiple(Notification $notification, array &$data)
    {
        return true; //@todo: Should handle multiples
    }

    /**
     * Returns the elements for notification's profile area
     * The third parameter of the array, config_vars, is same as the settings config vars specified in
     * various settings page
     *
     * @access public
     * @param int $id_member The ID of the member whose profile is currently being accessed
     * @return array(title, description, config_vars)
     */
    public function getProfile($id_member)
    {
        global $txt;

        return array(
            $txt['topicsubs_title_notif'],
            $txt['topicsubs_desc'],
            array(
                array(
                    'check', 'topicsubs_autotopic',
                    'value' => $this->getPref('autotopic', $id_member),
                    'text_label' => $txt['topicsubs_auto'],
                    'subtext' => $txt['topicsubs_auto_desc'],
                ),
            ),
        );
    }

    /**
     * Saves the profile preferences
     *
     * @access public
     * @param int $id_member
     * @param array $settings
     * @return void
     */
    public function saveProfile($id_member, array $settings)
    {
        $this->savePref('autotopic', $settings['topicsubs_autotopic'], $id_member);
    }

    /**
     * E-mail handler, must be present since the user has the ability to receive e-mail
     * from any notifier
     *
     * @access public
     * @param Notification $notification
     * @return array(subject, body)
     */
    public function getEmail(Notification $notification)
    {
        return array('nope', 'nada'); //@todo: Implement this freaking thing
    }
}