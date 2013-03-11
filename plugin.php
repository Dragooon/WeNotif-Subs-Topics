<?php
/**
 * WeNotif's subcription topic's main file
 * 
 * @package Dragooon:WeNotif-Subs-Topics
 * @author Shitiz "Dragooon" Garg <Email mail@dragooon.net> <Url http://smf-media.com>
 * @copyright 2012, Shitiz "Dragooon" Garg <mail@dragooon.net>
 * @license
 *      Licensed under "New BSD License (3-clause version)"
 *      http://www.opensource.org/licenses/BSD-3-Clause
 * @version 1.0
 */

/**
 * Callback for subscribers to register themselves
 *
 * @param array &$notifiers
 * @return void
 */
function wenotif_subs_topics_hook_subscription(&$subscribers)
{
    $subscribers['topicsubs'] = new TopicSubsSubscriber();
}

/**
 * Hook callback for create_post_after, actually initiates the notification
 *
 * @param array &$msgOptions
 * @param array &$topicOptions
 * @param array &$posterOptions
 * @param bool &$new_topic
 * @return void
 */
function wenotif_subs_topics_hook_post(&$msgOptions, &$topicOptions, &$posterOptions, &$new_topic)
{
    if ($new_topic && WeNotif::getNotifiers('topicsubs')->getPref('autotopic'))
        NotifSubscription::store(WeNotif_Subs::getSubscribers('topicsubs'), $topicOptions['id']);

    if ($new_topic)
        return;

    $object = WeNotif_Subs::getSubscribers('topicsubs')->getObjects(array($topicOptions['id']));
    $subject = $object[$topicOptions['id']]['title'];

    NotifSubscription::issue(WeNotif_Subs::getSubscribers('topicsubs'), $topicOptions['id'], WeNotif::getNotifiers('topicsubs'), array(
        'subject' => $subject,
        'msg' => $msgOptions['id'],
    ));
}

/**
 * Callback for display_main hook, adds the notify hook and also marks
 * unread notifications read
 *
 * @return void
 */
function wenotif_subs_topics_hook_display_main()
{
    global $context, $txt, $scripturl;

    Notification::markReadForNotifier(we::$id, WeNotif::getNotifiers('topicsubs'), $context['current_topic']);

    $is_subscribed = (bool) NotifSubscription::get(WeNotif_Subs::getSubscribers('topicsubs'), $context['current_topic'], we::$id);

    unset($context['nav_buttons']['normal']['notify']); //!!!Temporary

    $context['nav_buttons']['normal'][ ($is_subscribed ? 'unnotify' : 'notify')] = array(
        'text' => $is_subscribed ? 'unnotify' : 'notify', 
        'custom' => 'onclick="return ask(' . JavaScriptEscape($txt['notification_' . ($is_subscribed ? 'disable_topic' : 'enable_topic')]) . ', e);"',
        'url' => '<URL>?action=subscribe;' . ($is_subscribed ? 'unsubscribe;' : '') . 'object=' . $context['current_topic'] . ';type=topicsubs;' . $context['session_query'],
    );
}


class TopicSubsSubscriber implements NotifSubscriber
{
    /**
     * Returns a URL for the object
     *
     * @access public
     * @param int $object
     * @return string
     */
    public function getURL($object)
    {
        global $scripturl;

        return $scripturl . '?topic=' . (int) $object;
    }

    /**
     * Returns this subscription's name
     *
     * @access public
     * @return string
     */
    public function getName()
    {
        return 'topicsubs';
    }

    /**
     * Returns the Notifier object associated with this subscription
     *
     * @access public
     * @return Notifier
     */
    public function getNotifier()
    {
        return WeNotif::getNotifiers('topicsubs');
    }

    /**
     * Checks whether the passed object is valid or not for subscribing
     *
     * @access public
     * @param int $object
     * @return bool
     */
    public function isValidObject($object)
    {
        $request = wesql::query('
            SELECT t.id_topic, b.id_board
            FROM {db_prefix}topics AS t
                INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
            WHERE t.id_topic = {int:object}
                AND {query_see_board}
            LIMIT 1',
            array(
                'object' => (int) $object,
            )
        );
        if (wesql::num_rows($request) == 0)
            return false;
        wesql::free_result($request);

        return true;
    }

    /**
     * Returns text for profile areas which will be displayed to the user
     *
     * @access public
     * @param int $id_member
     * @return array
     */
    public function getProfile($id_member)
    {
        global $txt;

        return array(
            'label' => $txt['topicsubs_title'],
            'description' => $txt['topicsubs_desc'],
        );
    }

    /**
     * Returns the ID, name and an URL for the passed objects for this
     * subscriber. Returned array will be formatted like:
     *
     * @access public
     * @param array $objects IDs of the objects to fetch
     * @return array
     */
    public function getObjects(array $objects)
    {
        global $scripturl;

        $request = wesql::query('
            SELECT t.id_topic, b.id_board, m.subject
            FROM {db_prefix}topics AS t
                INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
                INNER JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_first_msg)
            WHERE t.id_topic IN ({array_int:topics})
                AND {query_see_board}',
            array(
                'topics' => (array) $objects,
            )
        );
        $objects = array();
        while ($row = wesql::fetch_assoc($request))
        {
            $objects[$row['id_topic']] = array(
                'id' => $row['id_topic'],
                'title' => $row['subject'],
                'link' => $scripturl . '?topic=' . $row['id_topic'],
            );
        }
        wesql::free_result($request);

        return $objects;
    }
}