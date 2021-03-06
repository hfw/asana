<?php

namespace Helix\Asana\Task;

use CURLFile;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\DeleteTrait;
use Helix\Asana\Base\AbstractEntity\ImmutableInterface;
use Helix\Asana\Task;

/**
 * A file attachment.
 *
 * @immutable Attachments can only be created and deleted.
 *
 * @see https://developers.asana.com/docs/asana-attachments
 * @see https://developers.asana.com/docs/attachment
 *
 * @method string   getCreatedAt    () RFC3339x
 * @method string   getDownloadUrl  ()
 * @method string   getHost         ()
 * @method string   getName         ()
 * @method Task     getParent       ()
 * @method string   getPermanentUrl () Short, human-friendly.
 * @method string   getViewUrl      ()
 */
class Attachment extends AbstractEntity implements ImmutableInterface {

    use DeleteTrait;

    const DIR = 'attachments';
    const TYPE = 'attachment';

    protected const MAP = [
        'parent' => Task::class
    ];

    /**
     * Creates the attachment by uploading a file.
     *
     * @see https://developers.asana.com/docs/upload-an-attachment
     *
     * @param string $file
     * @return $this
     */
    public function create (string $file) {
        assert(!$this->hasGid());
        // api returns compact version. reload.
        $remote = $this->api->call('POST', "{$this->getParent()}/attachments", [
            CURLOPT_POSTFIELDS => ['file' => new CURLFile(realpath($file))] // multipart/form-data
        ])['data'];
        $this->data['gid'] = $remote['gid'];
        $this->reload();
        return $this;
    }
}