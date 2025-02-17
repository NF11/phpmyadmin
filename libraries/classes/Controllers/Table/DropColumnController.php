<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function _ngettext;
use function count;

final class DropColumnController extends AbstractController
{
    private DatabaseInterface $dbi;

    private FlashMessages $flash;

    private RelationCleanup $relationCleanup;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        FlashMessages $flash,
        RelationCleanup $relationCleanup
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
        $this->flash = $flash;
        $this->relationCleanup = $relationCleanup;
    }

    public function __invoke(ServerRequest $request): void
    {
        $selected = $_POST['selected'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $selectedCount = count($selected);
        if (($_POST['mult_btn'] ?? '') === __('Yes')) {
            $i = 1;
            $statement = 'ALTER TABLE ' . Util::backquote($GLOBALS['table']);

            foreach ($selected as $field) {
                $this->relationCleanup->column($GLOBALS['db'], $GLOBALS['table'], $field);
                $statement .= ' DROP ' . Util::backquote($field);
                $statement .= $i++ === $selectedCount ? ';' : ',';
            }

            $this->dbi->selectDb($GLOBALS['db']);
            $result = $this->dbi->tryQuery($statement);

            if (! $result) {
                $message = Message::error($this->dbi->getError());
            }
        } else {
            $message = Message::success(__('No change'));
        }

        if (empty($message)) {
            $message = Message::success(
                _ngettext(
                    '%1$d column has been dropped successfully.',
                    '%1$d columns have been dropped successfully.',
                    $selectedCount
                )
            );
            $message->addParam($selectedCount);
        }

        $this->flash->addMessage($message->isError() ? 'danger' : 'success', $message->getMessage());
        $this->redirect('/table/structure', ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']]);
    }
}
