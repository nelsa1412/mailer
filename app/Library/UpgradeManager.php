<?php

/**
 * UpgradeManager class.
 *
 * Tool for upgrading the entire system source
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   Acelle Library
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace Acelle\Library;

use ZipArchive;
use Illuminate\Support\Facades\Log as LaravelLog; // something wrong, cannot use the default name Log

class UpgradeManager
{
    protected $source;
    protected $target;

    const META_FILE = 'meta.json';

    /**
     * Constructor, specify the source, target and load the meta information
     *
     */
    function __construct()
    {
        $this->source = storage_path('tmp/patch');
        $this->target = base_path();
    }

    /**
     * Constructor, specify the source, target and load the meta information
     *
     */
    public function load($path)
    {
        // Check WRITE permission
        $this->cleanup();

        try {
            // Extract the zip file
            $old = umask(0);
            $zip = new ZipArchive();
            $res = $zip->open($path);
            if ($res === true) {
                $zip->extractTo($this->source);
                $zip->close();
                umask($old);

                // test the patch, throw an exception in case meta.json does not exist
                $this->validate();
            } else {
                umask($old);
                throw new \Exception("Invalid upgrade package");
            }
        } catch (\Exception $e) {
            $this->rm($this->source);
            throw $e;
        }

    }

    /**
     * Read the meta data from a patch package
     *
     */
    public function getNewVersion()
    {
        if ($this->isNewVersionAvailable()) {
            return $this->getMetaInfo()['version'];
        } else {
            return null;
        }
    }

    /**
     * Get last supported version for upgrade
     *
     */
    public function getLastSupportedVersion()
    {
        if ($this->isNewVersionAvailable()) {
            return $this->getMetaInfo()['last_supported'];
        } else {
            return null;
        }
    }

    /**
     * Read the meta data from a patch package
     *
     */
    public function cleanup()
    {
        // Check WRITE permission
        if (!$this->isWritable($this->source)) {
            throw new \Exception("Cannot write to folder {$this->source}");
        } else {
            // Clean up the target folder
            $this->rm($this->source);
        }
    }

    /**
     * Read the meta data from a patch package
     *
     */
    public function validate()
    {
        $meta = $this->getMetaInfo();
        if (version_compare($this->getNewVersion(), $this->getCurrentVersion(), '=')) {
            throw new \Exception(sprintf('The version you uploaded is the same as the current one (%s)', $this->getNewVersion()));
        }

        if (version_compare($this->getNewVersion(), $this->getCurrentVersion(), '<')) {
            throw new \Exception(sprintf('The version you uploaded (%s) is older than the current one (%s)', $this->getNewVersion(), $this->getCurrentVersion()));
        }

        if (version_compare($this->getLastSupportedVersion(), $this->getCurrentVersion(), '>')) {
            throw new \Exception(sprintf('You are on a version that is not supported by this update, last supported version is %s', $this->getLastSupportedVersion() ));
        }

        // DRYRUN to see if there is any error
        $this->test();
    }

    /**
     * Read the meta data from a patch package
     *
     */
    private function getMetaInfo()
    {
        $path = join_paths($this->source, self::META_FILE);
        if (!file_exists($path)) {
            throw new \Exception("Unknown package format");
        }

        return json_decode(file_get_contents($path), true);
    }

    /**
     * Actually run the upgrade process
     *
     */
    public function run()
    {
        LaravelLog::info('Start upgrading');
        $meta = $this->getMetaInfo();
        $updates = $meta['updated'];
        $deletes = $meta['deleted'];
        $packages = $meta['packages'];

        foreach($updates as $file) {
            $source = join_paths($this->source, $file);
            $target = join_paths($this->target, $file);
            LaravelLog::info("Replacing {$target}");
            if (!copy($source, $target)) {
                LaravelLog::error("Cannot replace file {$target}");
                throw new \Exception("Cannot replace file {$target}");
            }
        }

        foreach($deletes as $file) {
            $target = join_paths($this->target, $file);
            LaravelLog::info("Deleting {$target}");
            @unlink($target);
        }

        foreach($packages as $dir) {
            $source = join_paths($this->source, 'vendor', $dir);
            $target = join_paths($this->target, 'vendor', $dir);
            LaravelLog::info("Replacing {$target}");
            $this->rm($target);
            $this->copy($source, $target);
        }

        LaravelLog::info("Cleaning up");
        // cleanup
        $this->cleanup();

        // reload the config & run migration
        LaravelLog::info("Start caching & migrating");
        artisan_config_cache();
        artisan_migrate();
        LaravelLog::info("All done!");
    }

    /**
     * Test the upgrade process (DRYRUN)
     *
     */
    public function test()
    {
        $errors = [];
        $meta = $this->getMetaInfo();
        $updates = $meta['updated'];
        $deletes = $meta['deleted'];
        $packages = $meta['packages'];

        foreach ($updates as $file) {
            $path = join_paths($this->target, $file);
            if (!$this->isWritable($path)) {
                $errors[] = $path;
            }
        }

        foreach ($deletes as $file) {
            $path = join_paths($this->target, $file);
            if (!$this->isWritable($path)) {
                $errors[] = $path;
            }
        }

        foreach ($packages as $dir) {
            $path = join_paths($this->target, 'vendor', $dir);
            if (!$this->isWritable($path)) {
                $errors[] = $path;
            }
        }

        return $errors;
    }

    /**
     * Get current app version
     *
     */
    public function getCurrentVersion()
    {
        return trim(file_get_contents(base_path('VERSION')));
    }

    /**
     * Check if new version is available
     *
     */
    public function isNewVersionAvailable()
    {
        return file_exists($this->source);
    }

    /**
     * Check if an existing file is writable or a new path can be created
     *
     * @input string file path
     * @output boolean
     */
    private function isWritable($path)
    {
        if (is_writable($path)) {
            return true;
        } elseif (!file_exists($path) && $this->canCreateFile($path)) {
            return true;
        } else {
            // file exists but not writable
            // file not exist nor creatable
            return false;
        }
    }

    /**
     * Check if the specified path can be created
     *
     * @output boolean
     */
    private function canCreateFile($path)
    {
        $a = explode(DIRECTORY_SEPARATOR, $path);
        $parent = null;
        for ($i = 0; $i < sizeof($a); $i += 1) {
            $tmppath = implode(DIRECTORY_SEPARATOR, array_slice($a, 0, $i));

            if (empty($tmppath)) {
                continue;
            }

            if (!file_exists($tmppath)) {
                break;
            } else {
                $parent = $tmppath;
            }
        }

        return is_writable($parent);
    }

    /**
     * Delete a directory recursively
     *
     */
    private function rm($src)
    {
        if (!file_exists($src)) {
            return;
        }

        if (!is_dir($src)) {
            unlink($src);
            return;
        }

        $dir = opendir($src);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $src . '/' . $file;
                if ( is_dir($full) ) {
                    $this->rm($full);
                }
                else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }

    /**
     * Copy a directory recursively
     *
     */
    private function copy($src, $dst)
    {
        if (!is_dir($src)) {
            copy($src, $dst);
            return;
        }

        $dir = opendir($src);
        mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir(join_paths($src, $file)) ) {
                    $this->copy(join_paths($src,  $file), join_paths($dst, $file));
                } else {
                    copy(join_paths($src, $file), join_paths($dst,  $file));
                }
            }
        }
        closedir($dir);
    }
}
