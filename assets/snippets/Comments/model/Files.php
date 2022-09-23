<?php

namespace Comments;

class Files extends \autoTable
{
    protected $table = 'comments_files';
    protected $attachments_table = 'comments_attachments';
    protected $fs;
    protected $default_field = [
        'file'      => '',
        'hash'      => '',
        'createdon' => ''
    ];
    protected $thumbnailOptions = 'w=150&h=150&far=C&bg=FFFFFF';

    public function __construct($modx, $debug = false)
    {
        parent::__construct($modx, $debug);
        $this->fs = \Helpers\FS::getInstance();
    }

    public function setThumbnailOptions($options)
    {
        $this->thumbnailOptions = $options;
    }

    public function upload(array $data, $fire_events = true)
    {
        $this->create($data);
        $this->set('createdon', date('Y-m-d H:i:s', $this->getTime(time())));
        $this->set('hash', $this->generateRandomString());

        if ($this->save(true)) {
            $this->createThumb();
            return $this->toArray();
        } else {
            return false;
        }
    }

    public function createThumb()
    {
        $thumb = new \Helpers\PHPThumb();
        $file = $this->get('file');
        $inputFile = MODX_BASE_PATH . $this->fs->relativePath($file);
        $outputFile = $this->getThumbPath($inputFile);
        $thumb->create($inputFile, $outputFile, $this->thumbnailOptions);
        $this->set('thumb', $this->fs->relativePath($outputFile));
    }

    public function getThumbPath($file) {
        return preg_replace('#(^.*[\\\/])#','${1}' . 'thumbs' . '/', $file);
    }

    public function prepareUploadDir($dir)
    {
        $dir .= date('Y') . '/' . date('m') . '/' . date('d' . '/');
        if ($this->fs->makeDir($dir)) {
            $this->fs->makeDir($dir . 'thumbs/');
            return $dir;
        } else {
            return false;
        }
    }

    public function prepareName($path)
    {
        return $this->fs->getInexistantFilename($path);
    }

    public function checkFileType($file, array $allowed)
    {
        $ext = $this->fs->takeFileExt($file);

        return in_array($ext, $allowed);
    }

    public function checkFileSize($file, $maxSize)
    {
        return $this->fs->fileSize($file) < $maxSize;
    }

    public function stripName($name) {
        $filename = $this->fs->takeFileName($name);
        $ext = $this->fs->takeFileExt($name);
        return $this->modx->stripAlias($filename).'.'.$ext;
    }

    public function generateRandomString($length = 32)
    {
        if (function_exists('random_bytes')) {
            $result = bin2hex(random_bytes($length * 0.5));
        } else {
            if (function_exists('openssl_random_pseudo_bytes')) {
                $result = bin2hex(openssl_random_pseudo_bytes($length * 0.5));
            } else {
                $result = md5(rand() . rand() . rand());
            }
        }

        return substr($result, 0, $length);
    }

    public function getIdsByHash($ids) {
        $out = [];
        $_ids = [];
        if (!is_array($ids)) $ids = [$ids];
        foreach ($ids as $id) {
            $id = explode('-', $id);
            if (count($id) < 2) return $out;
            $id[0] = (int)$id[0];
            $id[1] = $this->escape($id[1]);
            $_ids[] = "({$id[0]},'{$id[1]}')";
        }
        if (!empty($_ids)) {
            $_ids = implode(',', $_ids);
            $q = $this->query("SELECT `id` FROM {$this->makeTable($this->table)} WHERE (`id`, `hash`) IN ({$_ids})");
            if ($ids = $this->modx->db->getColumn('id', $q)) {
                $out = $ids;
            }
        }

        return $out;
    }

    public function deleteFiles($ids, $fire_events = true)
    {
        $_ids = implode(',', $this->cleanIDs($ids));
        if (!$_ids) return;

        $result = $this->query("SELECT * FROM {$this->makeTable($this->table)} WHERE `id` IN ({$_ids})");

        while($row = $this->modx->db->getRow($result)) {
            $this->fs->unlink($row['file']);
            $this->fs->unlink($this->getThumbPath($row['file']));
        }

        $this->delete($ids, $fire_events);
    }

    public function createTable()
    {
        $this->modx->db->query("
            CREATE TABLE IF NOT EXISTS {$this->makeTable($this->table)} (
            `id` int(11) AUTO_INCREMENT,
            `file` varchar(255) NOT NULL,
            `hash` varchar(32) NOT NULL,
            `createdon` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `file` (`id`, `hash`),
            KEY `createdon` (`createdon`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function deleteLostFiles($ttl) {
        $ttl = (int)$ttl;
        if ($ttl) {
            $date = date('Y-m-d H:i:s', strtotime("-{$ttl} hours"));
            $q = $this->query("SELECT `f`.`id` FROM {$this->makeTable($this->files_table)} `f` LEFT JOIN {$this->makeTable($this->attachments_table)} `a` ON `f`.`id` = `a`.`attachment` WHERE ISNULL(`a`.`comment`) AND `createdon` < '{$date}'");
            $ids = $this->modx->db->getColumn('id', $q);
            $this->deleteFiles($ids);
        }
    }
}
