<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use App\Config\Config;
use App\Database\DBConnection;
use App\Database\DBCore;
use App\Utility\Authentication;
use App\Utility\Html;
use App\Utility\Formatter;
use App\Utility\Security;
use App\Core\MTTNotification;
use App\Core\MTTNotificationCenter;
use Monolog\Logger;
use Exception;

class ListsController extends BaseControllerApi
{
    public function __construct(Logger $logger)
    {
        parent::__construct($logger);
        $this->log = $this->log->withName('ListsController');
    }

    /**
     * Get all lists
     * @return void
     * @throws Exception
     */
    public function get(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        // $this->log->notice('Inside ListsController GET');
        Authentication::checkToken();
        $db = DBConnection::instance();

        $t = array();
        $t['total'] = 0;
        $haveWriteAccess = Authentication::haveWriteAccess();

        // $this->log->info('haveWriteAccess Set', ['haveWriteAccess' => $haveWriteAccess]);
        if (!$haveWriteAccess) {
            $sqlWhere = 'WHERE published=1';
        } else {
            $sqlWhere = '';
            $t['list'][] = $this->prepareAllTasksList(); // show alltasks lists only for authorized user
            $t['total'] = 1;
        }

        $t['time'] = time();
        $q = $db->dq("SELECT * FROM {$db->prefix}lists $sqlWhere ORDER BY ow ASC, id ASC");

        while ($r = $q->fetchAssoc()) {
            $t['total']++;
            $t['list'][] = $this->prepareList($r, $haveWriteAccess);
        }

        /* ===================================================================================================================== */

        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(json_encode($t));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'application/json');
    }


    /**
     * Create new list and Actions with all lists
     * Code 201 on success
     * @return void
     * @throws Exception
     */
    public function post(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $this->log->info('Post Started');
        Authentication::checkWriteAccess();
        $action = $request->getParsedBody()['action'] ?? '';
        $this->log->info('action', ['action' => $action]);

        switch ($action) {
            case 'order':
                $data = $this->changeListOrder($request);
                break; //compatibility
            case 'new':
                $data = $this->createList($request);
                break;
            default:
                $data = $this->createList($request);
        }

        /* ===================================================================================================================== */

        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(json_encode($data));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'application/json');
    }

    /**
     * Actions with all lists
     * @return void
     * @throws Exception
     */
    public function put(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        Authentication::checkWriteAccess();
        $action = $request->getParsedBody()['action'] ?? '';

        switch ($action) {
            case 'order':
                $data = $this->changeListOrder($request);
                break;
            default:
                $data = ['total' => 0]; // error 400 ?
        }

        /* ===================================================================================================================== */

        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(json_encode($data));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'application/json');
    }


    /* Single list */
    /**
     * Get single list by Id
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    public function getId(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = (int)$args['id'] ?? -1;
        Authentication::checkReadAccess($id);
        $db = DBConnection::instance();
        $r = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=?", array($id));

        if (!$r) {
            $data = null;
        }

        $t = $this->prepareList($r, Authentication::haveWriteAccess());
        $data = $t;

        /* ===================================================================================================================== */

        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(json_encode($data));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'application/json');
    }

    /**
     * Delete list by Id
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    public function deleteId(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = (int)$args['id'] ?? 0;
        Authentication::checkWriteAccess();

        /* ===================================================================================================================== */

        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(json_encode($this->deleteList($id)));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'application/json');
    }


    /**
     * Edit some properties of List
     * Actions: rename, ...
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    public function putId(ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        $id = (int)$args['id'] ?? 0;
        Authentication::checkWriteAccess();

        $action = $request->getParsedBody()['action'] ?? '';

        switch ($action) {
            case 'rename':
                $data = $this->renameList($request, $id);
                break;
            case 'sort':
                $data = $this->sortList($request, $id);
                break;
            case 'publish':
                $data = $this->publishList($request, $id);
                break;
            case 'enableFeedKey':
                $data = $this->enableFeedKey($request, $id);
                break;
            case 'showNotes':
                $data = $this->showNotes($request, $id);
                break;
            case 'hide':
                $data = $this->hideList($request, $id);
                break;
            case 'clearCompleted':
                $data = $this->clearCompleted($id);
                break;
            case 'delete':
                $data = $this->deleteList($id);
                break; //compatibility
            default:
                $data = ['total' => 0];
        }

        /* ===================================================================================================================== */

        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(json_encode($data));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'application/json');
    }


    /* Private Functions */

    private function prepareAllTasksList(): array
    {
        //default values
        $hidden = 1;
        $sort = 3;
        $showCompleted = 1;

        $opts = Config::requestDomain('alltasks.json');

        if (isset($opts['hidden'])) {
            $hidden = (int)$opts['hidden'] ? 1 : 0;
        }

        if (isset($opts['sort'])) {
            $sort = (int)$opts['sort'];
        }

        if (isset($opts['showCompleted'])) {
            $showCompleted = (int)$opts['showCompleted'];
        }

        return array(
            'id' => -1,
            'name' => Html::htmlarray(Formatter::__('alltasks')),
            'sort' => $sort,
            'published' => 0,
            'showCompl' => $showCompleted,
            'showNotes' => 0,
            'hidden' => $hidden,
            'feedKey' => '',
        );
    }

    private function getListRowById(int $id): array
    {
        $r = DBCore::default()->getListById($id);

        if (!$r) {
            throw new Exception("Failed to fetch list data");
        }

        return $this->prepareList($r, true);
    }

    private function prepareList($row, bool $haveWriteAccess): array
    {
        $taskview = (int)$row['taskview'];
        $feedKey = '';

        if ($haveWriteAccess) {
            $extra = json_decode($row['extra'] ?? '', true, 10, JSON_INVALID_UTF8_SUBSTITUTE);
            if ($extra === false) {
                error_log("Failed to decodes JSON data of list extra listId=" . (int)$row['id'] . ": " . json_last_error_msg());
                $extra = [];
            }
            $feedKey = (string) ($extra['feedKey'] ?? '');
        }

        return array(
            'id' => $row['id'],
            'name' => Html::htmlarray($row['name']),
            'sort' => (int)$row['sorting'],
            'published' => $row['published'] ? 1 : 0,
            'showCompl' => $taskview & 1 ? 1 : 0,
            'showNotes' => $taskview & 2 ? 1 : 0,
            'hidden' => $taskview & 4 ? 1 : 0,
            'feedKey' => $feedKey,
        );
    }

    private function createList(ServerRequestInterface $request): ?array
    {
        $t = array();
        $t['total'] = 0;
        $id = DBCore::default()->createListWithName($request->getParsedBody()['name'] ?? '');

        if (!$id) {
            return $t;
        }

        $db = DBConnection::instance();
        $t['total'] = 1;
        $r = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$id");
        $oo = $this->prepareList($r, true);
        MTTNotificationCenter::postNotification(MTTNotification::DIDCREATELIST, $oo);
        $t['list'][] = $oo;

        return $t;
    }

    private function renameList(ServerRequestInterface $request, int $id): ?array
    {
        $db = DBConnection::instance();
        $t = array();
        $t['total'] = 0;

        $name = str_replace(
            array('"',"'",'<','>','&'),
            array('','','','',''),
            trim($request->getParsedBody()['name'] ?? '')
        );

        $db->dq("UPDATE {$db->prefix}lists SET name=?,d_edited=? WHERE id=$id", array($name, time()));
        $t['total'] = $db->affected();
        $r = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$id");
        $t['list'][] = $this->prepareList($r, true);

        return $t;
    }

    private function sortList(ServerRequestInterface $request, int $listId): ?array
    {
        $sort = (int)($request->getParsedBody()['sort'] ?? 0);
        self::setListSortingById($listId, $sort);

        return ['total' => 1];
    }

    /* ===================================================================================================================== */

    public static function setListSortingById(int $listId, int $sort): void
    {
        $db = DBConnection::instance();

        if ($sort < 0 || ($sort > 5 && $sort < 100) || $sort > 105) {
            $sort = 0;
        }

        if ($listId == -1) {
            $opts = Config::requestDomain('alltasks.json');
            $opts['sort'] = $sort;
            Config::saveDomain('alltasks.json', $opts);
        } else {
            $db->ex("UPDATE {$db->prefix}lists SET sorting=$sort,d_edited=? WHERE id=$listId", array(time()));
        }
    }

    public static function setListShowCompletedById(int $listId, bool $showCompleted): void
    {
        $db = DBConnection::instance();

        if ($listId == -1) {
            $opts = Config::requestDomain('alltasks.json');
            $opts['showCompleted'] = (int)$showCompleted;
            Config::saveDomain('alltasks.json', $opts);
        } else {
            $bitwise = $showCompleted ? 'taskview | 1' : 'taskview & ~1';
            $db->dq("UPDATE {$db->prefix}lists SET taskview=$bitwise WHERE id=?", [$listId]);
        }
    }

    /* ===================================================================================================================== */

    private function publishList(ServerRequestInterface $request, int $listId): ?array
    {
        $db = DBConnection::instance();
        $publish = (int)($request->getParsedBody()['publish'] ?? 0);
        $db->ex("UPDATE {$db->prefix}lists SET published=?,d_edited=? WHERE id=$listId", array($publish ? 1 : 0, time()));

        return ['total' => 1];
    }

    private function enableFeedKey(ServerRequestInterface $request, int $listId): ?array
    {
        $db = DBConnection::instance();
        $flag = (int)($request->getParsedBody()['enable'] ?? 0);
        $json = $db->sq("SELECT extra FROM {$db->prefix}lists WHERE id=$listId") ?? '';
        $extra = strlen($json) > 0 ? json_decode($json, true, 10, JSON_INVALID_UTF8_SUBSTITUTE) : [];

        if ($extra === false) {
            error_log("Failed to decodes JSON data of list extra listId=$listId: " . json_last_error_msg());
            $extra = [];
        }

        if ($flag == 0) {
            $extra['feedKey'] = '';
        } else {
            $extra['feedKey'] = Security::randomString();
        }

        $json = json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $db->ex("UPDATE {$db->prefix}lists SET extra=?,d_edited=? WHERE id=$listId", array($json, time()));

        return [
            'total' => 1,
            'list' => [[
                'id' => $listId,
                'feedKey' => $extra['feedKey']
            ]]
        ];
    }

    private function showNotes(ServerRequestInterface $request, int $listId): ?array
    {
        $db = DBConnection::instance();
        $flag = (int)($request->getParsedBody()['shownotes'] ?? 0);
        $bitwise = ($flag == 0) ? 'taskview & ~2' : 'taskview | 2';
        $db->dq("UPDATE {$db->prefix}lists SET taskview=$bitwise WHERE id=$listId");

        return ['total' => 1];
    }

    private function hideList(ServerRequestInterface $request, int $listId): ?array
    {
        $db = DBConnection::instance();
        $flag = (int)($request->getParsedBody()['hide'] ?? 0);

        if ($listId == -1) {
            $opts = Config::requestDomain('alltasks.json');
            $opts['hidden'] = $flag ? 1 : 0;
            Config::saveDomain('alltasks.json', $opts);
        } else {
            $bitwise = ($flag == 0) ? 'taskview & ~4' : 'taskview | 4';
            $db->dq("UPDATE {$db->prefix}lists SET taskview=$bitwise WHERE id=$listId");
        }

        return ['total' => 1];
    }

    private function clearCompleted(int $listId): ?array
    {
        $db = DBConnection::instance();
        $t = array();
        $t['total'] = 0;
        $db->ex("BEGIN");
        $db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id IN (SELECT id FROM {$db->prefix}todolist WHERE list_id=? and compl=1)", array($listId));
        $db->ex("DELETE FROM {$db->prefix}todolist WHERE list_id=$listId and compl=1");
        $t['total'] = $db->affected();
        $db->ex("COMMIT");

        if (MTTNotificationCenter::hasObserversForNotification(MTTNotification::DIDDELETECOMPLETEDINLIST)) {
            $list = $this->getListRowById($listId);
            MTTNotificationCenter::postNotification(MTTNotification::DIDDELETECOMPLETEDINLIST, [
                'total' => $t['total'],
                'list' => $list
            ]);
        }

        return $t;
    }

    private function changeListOrder(ServerRequestInterface $request): ?array
    {
        $t = array();
        $t['total'] = 0;

        if (!is_array($request->getParsedBody()['order'])) {
            return $t;
        }

        $db = DBConnection::instance();
        $order = $request->getParsedBody()['order'];
        $a = array();
        $setCase = '';

        foreach ($order as $ow => $id) {
            $id = (int)$id;
            $a[] = $id;
            $setCase .= "WHEN id=$id THEN $ow\n";
        }

        $ids = implode(',', $a);
        $db->dq("UPDATE {$db->prefix}lists SET d_edited=?, ow = CASE\n $setCase END WHERE id IN ($ids)", array(time()));
        $t['total'] = 1;

        return $t;
    }

    private function deleteList(int $id): ?array
    {
        $db = DBConnection::instance();
        $t = array();
        $t['total'] = 0;
        $id = (int)$id;
        $list = null;

        if (MTTNotificationCenter::hasObserversForNotification(MTTNotification::DIDDELETELIST)) {
            $list = $this->getListRowById($id);
        }

        $db->ex("BEGIN");
        $db->ex("DELETE FROM {$db->prefix}lists WHERE id=$id");
        $t['total'] = $db->affected();

        if ($t['total']) {
            $db->ex("DELETE FROM {$db->prefix}tag2task WHERE list_id=$id");
            $db->ex("DELETE FROM {$db->prefix}todolist WHERE list_id=$id");
        }

        $db->ex("COMMIT");

        if ($t['total'] && MTTNotificationCenter::hasObserversForNotification(MTTNotification::DIDDELETELIST)) {
            MTTNotificationCenter::postNotification(MTTNotification::DIDDELETELIST, $list);
        }

        return $t;
    }
}
