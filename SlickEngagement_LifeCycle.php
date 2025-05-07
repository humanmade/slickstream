<?php 
declare(strict_types=1);
namespace Slickstream;

require_once 'SlickEngagement_InstallIndicator.php';

class PluginLifecycle extends InstallIndicator {

    public function __construct() {
        parent::__construct();
    }

    public function install(): void {

        // Initialize Plugin Options
        $this->initOptions();

        // Initialize DB Tables used by the plugin
        $this->installDatabaseTables();

        // Other Plugin initialization - for the plugin writer to override as needed
        $this->otherInstall();

        // Record the installed version
        $this->saveInstalledVersion();

        // To avoid running install() more then once
        $this->markAsInstalled();
    }

    public function uninstall(): void {
        $this->otherUninstall();
        $this->unInstallDatabaseTables();
        $this->deleteSavedOptions();
        $this->markAsUnInstalled();
    }

    /**
     * Perform any version-upgrade activities prior to activation (e.g. database changes)
     * @return void
     */
    public function upgrade(): void {}

    /**
     * @return void
     */
    public function activate(): void {
        $this->slickLog("Activating");
    }

    /**
     * @return void
     */
    public function deactivate(): void {
        $this->slickLog("Deactivating");
        // $this->uninstall();
    }

    /**
     * @return void
     */
    protected function initOptions(): void {}

    public function addActionsAndFilters(): void {}

    /**
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables(): void {}

    /**
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables(): void {}

    /**
     * Override to add any additional actions to be done at install time
     * @return void
     */
    protected function otherInstall(): void {}

    /**
     * Override to add any additional actions to be done at uninstall time
     * @return void
     */
    protected function otherUninstall(): void {}

    protected function requireExtraPluginFiles(): void {
        require_once ABSPATH . 'wp-includes/pluggable.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    /**
     * @param  $name string name of a database table
     * @return string input prefixed with the WordPress DB table prefix
     * plus the prefix for this plugin (lower-cased) to avoid table name collisions.
     * The plugin prefix is lower-cases as a best practice that all DB table names are lower case to
     * avoid issues on some platforms
     */
    protected function prefixTableName($name): string {
        global $wpdb;
        return $wpdb->prefix . strtolower($this->prefix($name));
    }

    /**
     * Convenience function for creating AJAX URLs.
     *
     * @param $actionName string the name of the ajax action registered in a call like
     * add_action('wp_ajax_actionName', array(&$this, 'functionName'));
     *     and/or
     * add_action('wp_ajax_nopriv_actionName', array(&$this, 'functionName'));
     *
     * If have an additional parameters to add to the Ajax call, e.g. an "id" parameter,
     * you could call this function and append to the returned string like:
     *    $url = $this->getAjaxUrl('myaction&id=') . urlencode($id);
     * or more complex:
     *    $url = sprintf($this->getAjaxUrl('myaction&id=%s&var2=%s&var3=%s'), urlencode($id), urlencode($var2), urlencode($var3));
     *
     * @return string URL that can be used in a web page to make an Ajax call to $this->functionName
     */
    public function getAjaxUrl($actionName): string {
        return admin_url('admin-ajax.php') . '?action=' . $actionName;
    }

    public function slickLog($msg, $name = ''): void {
        // Print the name of the calling function if $name is left empty
        $trace = debug_backtrace();
        $name = ('' === $name) ? $trace[1]['function'] : $name;
        $msg = print_r($msg, true);
        $log = "Slickstream: $name | $msg\n";
        error_log($log);
    }
}
