<h1>Condition-based Code Snippet Injection</h1>

<?php

function condition_injection_request($name, $default = null) {
    if (!isset($_REQUEST[$name]))
        return $default;
    return stripslashes_deep($_REQUEST[$name]);
}

function condition_injection_add_field_checkbox_only($name, $tips = '', $attrs = '', $link = null) {
    global $options;
    echo '<td><input type="checkbox" ' . $attrs . ' name="options[' . $name . ']" value="1" ' .
    (isset($options[$name]) && (boolean)$options[$name] ? 'checked' : '') . '/>';
    echo ' ' . $tips;
    if ($link) {
        echo '<br><a href="' . $link . '" target="_blank">Read more</a>.';
    }
    echo '</td>';
}

function condition_injection_add_field_textarea($name, $label = '', $tips = '', $attrs = '') {
    global $options;

    if (!isset($options[$name]))
        $options[$name] = '';

    if (is_array($options[$name]))
        $options[$name] = implode("\n", $options[$name]);

    if (strpos($attrs, 'cols') === false)
        $attrs .= 'cols="70"';
    if (strpos($attrs, 'rows') === false)
        $attrs .= 'rows="5"';

    echo '<th scope="row">';
    echo '<label for="options[' . $name . ']">' . $label . '</label></th>';
    echo '<td><textarea style="width: 100%; height: 60px" wrap="off" name="options[' . $name . ']">' .
    htmlspecialchars($options[$name]) . '</textarea>';
    echo '<p class="description">' . $tips . '</p>';
    echo '</td>';
}

if (isset($_POST['save'])) {
    if (!wp_verify_nonce($_POST['my_nonce'], 'save_form'))
        die('Page expired');
    $options = condition_injection_request('options');
    update_option('condition_injection', $options);
} else {
    $options = get_option('condition_injection');
}

if (isset($_REQUEST['reset']) && (isset($_POST['my_nonce']) || isset($_GET['my_action_reset_nonce']))) {
    if (isset($_POST['my_nonce']) && !wp_verify_nonce($_POST['my_nonce'], 'save_form'))
        die('Page expired');
    if (isset($_GET['my_action_reset_nonce']) && !wp_verify_nonce($_GET['my_action_reset_nonce'], 'reset_action'))
        die('Page expired');

    update_option('condition_injection', null);

    $options_page = get_admin_url(null, 'options-general.php?page=condition-injection/options.php');
    echo '<p>Restored to default. <a href="' . $options_page . '">Back</a></p>';
} else {

?>

<form method="post" action="">
    <?php wp_nonce_field('save_form', 'my_nonce') ?>
    <div id="tab-container" class="tab-container">
        <div class="panel-container">
            <table class="form-table">
                <tr valign="top">
                    <?php
                    condition_injection_add_field_textarea('snippet', 'Code Snippet', ''
                            , 'rows="10"');
                    ?>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable Mobile Detection</th>
                    <?php condition_injection_add_field_checkbox_only('enable_mobile_snippet', 'Inject the "Mobile Code Snippet" instead of the "Code Snippet" if a mobile device could be detected.'); ?>
                </tr>
                <tr valign="top">
                    <?php
                    condition_injection_add_field_textarea('mobile_snippet', 'Mobile Code Snippet', '', 'rows="10"');
                    ?>
                </tr>
                <tr valign="top">
                    <?php
                    condition_injection_add_field_textarea('fallback_mobile_user_agents', 'User-Agents to detect mobile phone', 'For coders: a regular expression is built with those values and the resulting code will be<br>'
                            . '<code>preg_match(\'/' . $options['fallback_mobile_user_agents'] . '/\', ...);</code><br>', 'rows="5"');
                    ?>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable User-Agent Condition</th>
                    <?php condition_injection_add_field_checkbox_only('enable_condition_user_agents', 'Define the User-Agents which are required to allow injecting the code-snippet.'); ?>
                </tr>
                <tr valign="top">
                    <?php
                    condition_injection_add_field_textarea('condition_user_agents', 'Required User-Agents', 'For coders: a regular expression is built with those values and the resulting code will be<br>'
                            . '<code>preg_match(\'/' . $options['condition_user_agents'] . '/\', ...);</code><br>', 'rows="5"');
                    ?>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable Referrer Condition</th>
                    <?php condition_injection_add_field_checkbox_only('enable_condition_referrers', 'Define the Referrers which are required to allow injecting the code-snippet.'); ?>
                </tr>
                <tr valign="top">
                    <?php
                    condition_injection_add_field_textarea('condition_referrers', 'Required Referrers', 'For coders: a regular expression is built with those values and the resulting code will be<br>'
                            . '<code>preg_match(\'/' . $options['condition_referrers'] . '/\', ...);</code><br>', 'rows="5"');
                    ?>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable IP Condition</th>
                    <?php condition_injection_add_field_checkbox_only('enable_condition_ip_trunkated', 'Define the IP-Addresses which are required from the visitor to allow injecting the code-snippet.'); ?>
                </tr>
                <tr valign="top">
                    <?php
                    condition_injection_add_field_textarea('condition_ip_trunkated', 'Required (trunkated) IPs', 'For coders: a regular expression is built with those values and the resulting code will be<br>'
                            . '<code>preg_match(\'/' . $options['condition_ip_trunkated'] . '/\', ...);</code><br>', 'rows="1"');
                    ?>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable Reverse DNS Lookup</th>
                    <?php condition_injection_add_field_checkbox_only('enable_condition_hostname', 'Define the Hostnames which are required from the visitor to allow injecting the code-snippet.<br> <b>Enabling affects the performance of your site.</b>'); ?>
                </tr>
                <tr valign="top">
                    <?php
                    condition_injection_add_field_textarea('condition_hostnames', 'Required Hostnames', 'For coders: a regular expression is built with those values and the resulting code will be<br>'
                            . '<code>preg_match(\'/' . $options['condition_hostnames'] . '/\', ...);</code><br>' .
                            '<a href="https://whatismyipaddress.com/ip-hostname" target="_blank" rel="norefferer noopener">Reverse DNS Lookup Tool</a>', 'rows="1"');
                    ?>
                </tr>
                <tr valign="top">
                    <th scope="row">Allow php exec</th>
                    <?php condition_injection_add_field_checkbox_only('condition_injection_php_exec', 'Enables you the ability to execute php code inside the Code Snippet. <p class="description">e.g.: <code>' . htmlentities('<?php echo "php test";?>') . '</code></p>'); ?>
                </tr>
            </table>
        </div>
    </div>
    <p class="submit">
        <input type="submit" class="button" name="save" value="Save">
        <input type="submit" class="button" name="reset" value="Reset">
    </p>
</form>

<?php
}
?>
