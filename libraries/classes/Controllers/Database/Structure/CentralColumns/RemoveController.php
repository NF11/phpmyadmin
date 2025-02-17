<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure\CentralColumns;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;

final class RemoveController extends AbstractController
{
    private DatabaseInterface $dbi;

    private StructureController $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
        $this->structureController = $structureController;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;

        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $centralColumns = new CentralColumns($this->dbi);
        $error = $centralColumns->deleteColumnsFromList($_POST['db'], $selected);

        $GLOBALS['message'] = $error instanceof Message ? $error : Message::success(__('Success!'));

        unset($_POST['submit_mult']);

        ($this->structureController)($request);
    }
}
