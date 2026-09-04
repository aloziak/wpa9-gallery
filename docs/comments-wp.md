It's time to move forward with the plugin review "aloziak"!

Your plugin is not yet ready to be approved, you are receiving this email because the volunteers have manually checked it and have found some issues in the code / functionality of your plugin.

Please check this email thoroughly, address any issues listed, test your changes, and upload a corrected version of your code if all is well.

List of issues found


## Use wp_enqueue commands

Your plugin is not correctly including JS and/or CSS. You should be using the built in functions for this:

When including JavaScript code you can use:
wp_register_script() and wp_enqueue_script() to add JavaScript code from a file.
wp_add_inline_script() to add inline JavaScript code to previous declared scripts.

When including CSS you can use:
wp_register_style() and wp_enqueue_style() to add CSS from a file.
wp_add_inline_style() to add inline CSS to previously declared CSS.

Note that as of WordPress 6.3, you can easily pass attributes like defer or async: https://make.wordpress.org/core/2023/07/14/registering-scripts-with-async-and-defer-attributes-in-wordpress-6-3/

Also, as of WordPress 5.7, you can pass other attributes by using this functions and filters: https://make.wordpress.org/core/2021/02/23/introducing-script-attributes-related-functions-in-wordpress-5-7/

If you're trying to enqueue on the admin pages you'll want to use the admin enqueues.

https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
https://developer.wordpress.org/reference/hooks/admin_print_scripts/
https://developer.wordpress.org/reference/hooks/admin_print_styles/

Example(s) from your plugin:
admin/views/export-import.php:126 <script>



## Saving data in the plugin folder and/or asking users to edit/write to plugin.

We cannot accept a plugin that forces (or tells) users to edit the plugin files in order to function, or saves data in the plugin folder.

Plugin folders are deleted when upgraded, so using them to store any data is problematic. Also bear in mind, any data saved in a plugin folder is accessible by the public. This means anyone can read it and use it without the site-owner’s permission.

It is preferable that you save your information to the database, via the Settings API, especially if it’s privileged data.

If that’s not possible, because you’re uploading media files, you should use the media uploader.

If you can’t do either of those, you must save the data outside the plugins folder. We recommend using the uploads directory, creating a folder there with the slug of your plugin as name, as that will make your plugin compatible with multisite and other one-off configurations.

Please refer to the following links:

https://developer.wordpress.org/plugins/settings/
https://developer.wordpress.org/reference/functions/media_handle_upload/
https://developer.wordpress.org/reference/functions/wp_handle_upload/
https://developer.wordpress.org/reference/functions/wp_upload_dir/

Example(s) from your plugin:
includes/class-wpa9-install.php:218 file_put_contents($index, "<?php // Silence is golden.\n");
# ✨ Creates a PHP index.php file in uploads instead of an empty anti-listing index file, and writing code files is not allowed outside the narrow exception.



## High position for your admin dashboard menu item

Your plugin registers its top-level admin menu item using a very high menu position, causing it to appear above or alongside core WordPress items.

WordPress has an established admin hierarchy that users rely on for consistency and navigation: Posts, Media, Pages, Appearance, Plugins, Users, Tools, Settings, etc. Placing a plugin in a prominent or unexpected position can disrupt this structure and create a cluttered experience, especially on sites with multiple plugins.

Plugins should integrate into WordPress, not compete with core items or other plugins for visibility.

Best practices for menu placement include:
Add configuration pages under Settings - add_options_page()
Add utility functionality under Tools - add_management_page()
Add extensions or integrations under the relevant parent plugin (for example, WooCommerce-related pages under WooCommerce) - add_submenu_page()
Use a lower menu position if a top-level item is necessary.

Positioning a menu item prominently is often perceived as a visibility tactic rather than a usability-driven decision. The WordPress admin interface is a workspace. Please adjust the menu placement accordingly.

Example(s) from your plugin:
includes/class-wpa9-admin.php:20 add_menu_page(__('WPA9 Gallery', 'wpa9-gallery'), __('WPA9 Gallery', 'wpa9-gallery'), self::CAP, self::DASHBOARD_SLUG, array($this, 'page_dashboard'), 'dashicons-format-gallery', 25);



## Data Must be Sanitized, Escaped, and Validated

When you include POST/GET/REQUEST/FILE calls in your plugin, it's important to sanitize, validate, and escape them. The goal here is to prevent a user from accidentally sending trash data through the system, as well as protecting them from potential security issues.

SANITIZE: Data that is input (either by a user or automatically) must be sanitized as soon as possible. This lessens the possibility of XSS vulnerabilities and MITM attacks where posted data is subverted.

VALIDATE: All data should be validated, no matter what. Even when you sanitize, remember that you don’t want someone putting in ‘dog’ when the only valid values are numbers.

ESCAPE: Data that is output must be escaped properly when it is echo'd, so it can't hijack admin screens. There are many esc_*() functions you can use to make sure you don't show people the wrong data.

To help you with this, WordPress comes with a number of sanitization and escaping functions. You can read about those here:

https://developer.wordpress.org/apis/security/sanitizing/
https://developer.wordpress.org/apis/security/escaping/

Remember: You must use the most appropriate functions for the context. If you’re sanitizing email, use sanitize_email(), if you’re outputting HTML, use wp_kses_post(), and so on.

An easy mantra here is this:

Sanitize early
Escape Late
Always Validate

Clean everything, check everything, escape everything, and never trust the users to always have input sane data. After all, users come from all walks of life.

Example(s) from your plugin:
includes/class-wpa9-ajax.php:173 'caption'     => isset( $_POST['caption'] ) ? wp_unslash( $_POST['caption'] ) : '',
 -----> wp_unslash($_POST['caption'])
includes/class-wpa9-admin.php:119 'author_name'     => isset( $_POST['author_name'] ) ? wp_unslash( $_POST['author_name'] ) : '',
 -----> wp_unslash($_POST['author_name'])
includes/class-wpa9-admin.php:118 'description'     => isset( $_POST['description'] ) ? wp_unslash( $_POST['description'] ) : '',
 -----> wp_unslash($_POST['description'])



Note: There are simple ways to sanitize arrays, in case you need to do so, you can do the following:
An array of post IDs: array_unique(array_map('absint', $_POST['post_ids']))
An array of emails: array_map('sanitize_email', $_POST['user_emails'])
A multidimensional array, being all the elements texts: map_deep( $_POST['arrays_of_texts'], 'sanitize_text_field' )
Sometimes you'll have an array that contains different types of data inside, which would require different types of sanitization.

$sanitized_orders = $_POST['orders']; // Sanitized below.
array_walk_recursive( $sanitized_orders, 'wpa9ga_sanitize_orders' );
function wpa9ga_sanitize_orders( &$item , $key ){
  switch ($key){
    case 'locator':
      $item = sanitize_key($item);
      break;
    case 'name':
      $item = sanitize_text_field($item);
      break;
    case 'price':
    case 'priceDiscounted':
      $item = (float)$item;
      break;
    default:
      $item = NULL;
  }
}

We have heuristically detected these cases of your plugin that might need array sanitization (might be false positives, please check them out):
includes/class-wpa9-admin.php:125 'data_atts'       => isset( $_POST['data_atts'] ) ? wp_unslash( $_POST['data_atts'] ) : '',



Note: escape functions cannot be used to sanitize. They serve different purposes. Even if they seem to be perfect for this purpose, most of the functions are filterable and people expect to use them to escape. Therefore, another plugin may change what they do and make yours at risk and exploitable.

If you are trying to echo the variable, you have to first sanitize it and then escape it, as for example:
echo esc_html(sanitize_text_field($_POST['example']));
Example(s) from your plugin:
includes/class-wpa9-admin.php:91 echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( wp_unslash( $_GET['wpa9_notice'] ) ) . '</p></div>';
 -----> esc_attr($type)
 -----> esc_html(wp_unslash($_GET['wpa9_notice']))
admin/views/export-import.php:92 <input type="hidden" name="pending_import" value="<?php echo esc_attr( $_GET['pending_import'] ); ?>">
 -----> esc_attr($_GET['pending_import'])


✔️ You can check this using Plugin Check.


## Unsafe SQL calls

When making database calls, it's highly important to protect your code from SQL injection vulnerabilities. You need to update your code to use wpdb calls and prepare() with your queries to protect them.

Please review the following:
https://developer.wordpress.org/reference/classes/wpdb/#protect-queries-against-sql-injection-attacks
https://codex.wordpress.org/Data_Validation#Database
https://make.wordpress.org/core/2012/12/12/php-warning-missing-argument-2-for-wpdb-prepare/
https://ottopress.com/2013/better-know-a-vulnerability-sql-injection/
Example(s) from your plugin:
includes/class-wpa9-importer-nextgen.php:82 $ngg_galleries  = $wpdb->get_results( "SELECT * FROM {$t['gallery']} ORDER BY gid ASC" );
# You cannot add variables like "$t['gallery']" directly to the SQL query. You need to use wpdb::prepare.
# Remember that using wpdb::prepare($query, $args) you will need to include placeholders for each variable within the query and include the variables in the second parameter.
# The SQL query needs to be included in a wpdb::prepare($query, $args) function.includes/class-wpa9-importer-nextgen.php:135 $rows   = $wpdb->get_results(
"SELECT * FROM {$t['pictures']} WHERE galleryid IN ($in) ORDER BY galleryid ASC, sortorder ASC, pid ASC"
);
# You cannot add variables like "$t['pictures']" directly to the SQL query. You need to use wpdb::prepare.
# Remember that using wpdb::prepare($query, $args) you will need to include placeholders for each variable within the query and include the variables in the second parameter.
# The SQL query needs to be included in a wpdb::prepare($query, $args) function.
... out of a total of 11 incidences.


👉 Continue with the review process.

Read this email thoroughly.

Take the time to thoroughly review and understand the issues identified. Examine the provided examples, consult the relevant documentation, and conduct any additional research necessary. The goal of our review process is to help you clearly understand the reported issues so you can resolve them effectively and prevent similar problems in future updates to your plugin.
Note that there may be false positives - we are humans and make mistakes, we apologize if there is anything we have gotten wrong. If you have doubts you can ask us for clarification, when asking us please be clear, concise, direct and include an example.

📋 Complete your checklist.

✔️ I fixed all the issues in my plugin based on the feedback I received and my own review, as I know that the Plugins Team may not share all cases of the same issue. I am familiar with tools such as Plugin Check, PHPCS + WPCS, and similar utilities to help me identify problems in my code.
✔️ I tested my updated plugin on a clean WordPress installation with WP_DEBUG set to true.
⚠️ Do not skip this step. Testing is essential to make sure your fixes actually work and that you haven’t introduced new issues.

✔️ I acknowledge that this review will be rejected if I overlook the issues or fail to test my code.
✔️ I went to "Add your plugin" and uploaded the updated version. I can continue updating the code there throughout the review process — the team will always check the latest version.
✔️ I replied to this email. I was concise and shared any clarifications or important context that the team needed to know.
I didn't list all the changes, as the team will review the entire plugin again and that is not necessary at all.

ℹ️ To help speed up the review process, we kindly ask that you carefully verify and address all reported issues before resubmitting your code.

While we try to make our reviews as exhaustive as possible we, like you, are humans and may have missed things. We appreciate your patience and understanding.

Review ID: R wpa9-gallery/aloziak/18Jun26/T2 21Jun26/4.0.1 (P0TDX328034HGN)


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/
{#HS:3359620812-1067853#} 
On Sun, Jun 21, 2026 at 11:11 AM UTC, WordPress.org Plugin Directory <plugins@wordpress.org> wrote:
This is an automated message to confirm that we have received your updated plugin file.

File updated by aloziak, version 1.3.0.
Comment: I´ve updated contributor name from wpa9 to aloziak which is correct as well as change the profile email addres to info@apollo1.cz. Thank you. Ales Loziak

https://wordpress.org/plugins/files/2026/06/21_11-11-10_wpa9-gallery.zip


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/
On Thu, Jun 18, 2026 at 8:58 AM UTC, WordPress.org Plugin Directory <plugins@wordpress.org> wrote:
👋 aloziak - Let’s improve your plugin!

Thank you for submitting your plugin, "WPA9 Gallery".

Our volunteer reviewers, tools, and/or AI aids identified issues in your plugin that require your attention.

We’ve pended your submission to give you a chance to review and fix these common issues.

This team handles approximately 1,500 plugin reviews each week. That's a lot. To make the most of this process, please do your part and help us help you; otherwise, your plugin won't be approved.

🤖 Please note that this message was generated using a combination of humans, algorithms, and AI in varying proportions. It may not have been reviewed by a human. All AI outputs are marked with the ✨ emoji. Pay attention to it, it's quite accurate.

Who are we?

We are a group of volunteers who help you identify common issues so that you can make your plugin more secure, compatible, reliable and compliant with the guidelines.

For consistency and better communication, your plugin review will be assigned to a single volunteer who will assist you throughout the entire review. However, response times may vary depending on how much time the volunteer is able to contribute to the team and if whether they need to consult something with the rest of the team.

The review process

A email envelope.	Please read this email in full and check each issue, as well as the links to the documentation and the provided examples. Also, search for any other similar occurrences of the same issue that are not explicitly mentioned in the email.
Make sure you understand the issues so that you can incorporate them into your existing skillset.
A plugin author fixing the issues.	If you decide to continue with the review process, you must fix any issues, test your plugin, upload a corrected version and then reply to this email.
In case of any doubt, please fix everything else and ask your questions alongside the update.
A volunteer reviewing the plugin.	Your plugin is manually checked by a volunteer who sends you the remaining identified issues in the plugin.
We will be devoting our time to reviewing your plugin, we ask that you honor this by following the instructions.
Note: Volunteers are not your QA team. They are here to help you identify and understand issues so that you can improve and maintain your plugin in the future. Fixing the issues is your responsibility.
A new review of the plugin.	If there are no further issues, the plugin will be approved 🎉
A warning.	Be brief and direct in your reply (please, avoid copy-pasting bloated AI responses, our AI is quite brief), be patient and make sure you have addressed all the issues and tested your plugin before responding.
It is disheartening to receive an updated plugin only to find that only a few issues have been resolved, or that it causes a fatal error upon activation.

When not making adequate progress in your review, it will be delayed and eventually rejected, for the sake of volunteers devoting their time and other plugin authors who correctly follow the review process. Once rejected under these circumstances, it will not be reviewed again.

Each volunteer can review up to 400 plugins per week, and they love to have life besides reviewing plugins, so please make things easier for us.


Understanding the Review Queue

When you reply, your plugin enters the review queue again. Fewer review cycles mean quicker approval, while multiple reviews can extend the process to weeks or months.
Tip: Carefully fix all issues and test your plugin before resubmitting to speed up approval.

This process is designed to help you improve your plugin while making the review experience faster and more efficient for everyone.


Are you the rightful owner of this plugin?

We know this might seem like an odd question — especially coming from an algorithm — but bear with us; it’ll all make sense soon.

Here's how it works: If your plugin uses a name or URL associated with a specific entity, we need to confirm that you actually represent that entity. It's that simple.

So, how do we verify your identity? In most cases, we rely on the domain in your email address.

This situation usually comes up if you're using a personal account to submit a plugin meant to represent a company or if you’re a third party attempting to upload a plugin on someone else’s behalf (please don’t do that).

This is what we know about this plugin:
Plugin name: "WPA9 Gallery" in the readme.txt file
Plugin name: "WPA9 Gallery" in the wpa9-gallery.php file
Slug: wpa9-gallery
Author: Aleš Loziak
Plugin URI: https://apollo1.cz/
Contributors:
wpa9

This is what we know about you:
Username: aloziak
🟧 Your username does not match any of the contributors declared in the plugin.
Email: aloziak@gmail.com
🟥 Your email domain "gmail.com" does not seem to be related to any of the URLs, names, trademarks and/or or services declared in the plugin.
🟥 A gmail.com account cannot be used as a valid form of identification towards clarifying ownership, even if it contains your or your company's name. Anyone can create any gmail.com account that is not already in use. You could be you, but you could also be anyone else.

Please note that we carry out these checks to protect people like you. If someone else submitted a plugin under your name or your company's name, you would rightly be upset if we failed to confirm ownership in a demonstrably reliable way.


☑️ You can demonstrate or clarify ownership in one of the following ways:
📩 Update your WordPress.org email address to one under the domain of the entity associated with the plugin. You can change it in Your WordPress.org profile, we cannot change it for you.
Note: We will continue the review in this email thread. Any new emails will be sent to the new email account.

👤 Reply asking us to transfer this submission to the correct WordPress.org account. Please, tell us the username. If the owner does not yet have an account, they can create it using an email that is under the domain of the entity associated to the plugin.
⚠️ Do not resubmit this plugin using the new account!. I will transfer it for you. Simply reply to this email with the new username.
Note: Once the plugin has been approved, you can ask the owner to add you as a committer in the "Advanced" section of the plugin, so that you can commit code using your account.

🛠 Change the plugin's display name and slug to make it clear that the plugin is not officially affiliated with any other entity. Upload an updated version of your plugin via the "Add your plugin" page. Ask us to change the slug.

Reply to this email clarifying the situation, we know that sometimes you might be using an email account that belongs to the same entity or an entity that owns the other entity. Also, if you already have established plugins in the directory under the same account we can use that as tacit verification.

Perform a DNS check on the owner's domain. Add a TXT record at the owner's domain root @ with the following value: wordpressorg-aloziak-verification
Note: This will only be considered valid if that's done in the owner's domain and we can relate your account as being controlled by the owner. If you are a third party please follow other verification methods.

Remember that, if you own other plugins, all the plugins belonging to the same entity should be under the same WordPress.org account.

⚠️ Please do not resubmit this plugin using a different account. Both submissions will be rejected if you do so, and your accounts may be suspended until the situation is resolved. Instead, ask us to change the owner — we can certainly do that.

Have you checked for common technical issues?

Please ensure that your plugin adheres to best practices, including the following:

🔴 Use wp_enqueue commands

ℹ️ Why it matters: Because of performance and compatibility, please make use of the built in functions for including static and dynamic JS and/or CSS.

🔍 Identify JS and CSS outputs: Look for any <script> or <style> HTML tags in your plugin. In the majority of cases you could enqueue them.

🛠 Fix it: Make use of the specific function for enqueue them:
Type of code	Functions
Static JS	wp_register_script(), wp_enqueue_script(), admin_enqueue_scripts()
Inline JS	wp_add_inline_script()
Static CSS	wp_register_style(), wp_enqueue_style()
Inline CSS	wp_add_inline_style()

👉 In the public pages you can enqueue them using the hook wp_enqueue_scripts().
👉 In the admin pages you can enqueue them using the hook admin_enqueue_scripts(). You can also use admin_print_scripts() and admin_print_styles().
👉 As of WordPress 6.3, you can easily pass attributes like defer or async, as of WordPress 5.7, you can pass other attributes by using functions and filters.

Example:
function wpa9ga_enqueue_script() {
    wp_enqueue_script( 'wpa9ga_js', plugins_url( 'inc/main.js', __FILE__ ), array(), WPA9GA_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'wpa9ga_enqueue_script' );
Your JS/CSS is now enqueued!

Possible cases from your plugin include:
admin/views/export-import.php:126 <script>



Other details

We've detected some other details that you may want to check.

## You haven't added yourself to the "Contributors" list for this plugin.

In your readme file, the "Contributors" parameter is a case-sensitive, comma-separated list of all WordPress.org usernames that have contributed to the code.

Your username is not in this list, you need to add yourself if you want to appear listed as a contributor to this plugin.

If you don't want to appear that's fine, this is not mandatory, we're informing you just in case.

Analysis result:

# WARNING: None of the listed contributors "wpa9" is the WordPress.org username of the owner of the plugin "aloziak".


## Using load_plugin_textdomain() for loading the plugin translations is not needed for WordPress.org directory since WordPress 4.6.

When your plugin is hosted on WordPress.org you don't longer need to include load_plugin_textdomain() for the translations under your plugin slug "wpa9-gallery".

Therefore you can remove that call, WordPress will automatically load the translations for you when needed.

If you still support older WordPress versions you'll need to keep it, but please make sure to have the function call in a hook such as init to avoid issues for loading them too early. More information: https://make.wordpress.org/core/2024/10/21/i18n-improvements-6-7/.

From your plugin:
wpa9-gallery.php:45 load_plugin_textdomain('wpa9-gallery', false, dirname(WPA9_BASENAME) . '/languages');



## High position for your admin dashboard menu item

Your plugin registers its top-level admin menu item using a very high menu position, causing it to appear above or alongside core WordPress items.

WordPress has an established admin hierarchy that users rely on for consistency and navigation: Posts, Media, Pages, Appearance, Plugins, Users, Tools, Settings, etc. Placing a plugin in a prominent or unexpected position can disrupt this structure and create a cluttered experience, especially on sites with multiple plugins.

Plugins should integrate into WordPress, not compete with core items or other plugins for visibility.

Best practices for menu placement include:
Add configuration pages under Settings - add_options_page()
Add utility functionality under Tools - add_management_page()
Add extensions or integrations under the relevant parent plugin (for example, WooCommerce-related pages under WooCommerce) - add_submenu_page()
Use a lower menu position if a top-level item is necessary.

Positioning a menu item prominently is often perceived as a visibility tactic rather than a usability-driven decision. The WordPress admin interface is a workspace. Please adjust the menu placement accordingly.

Example(s) from your plugin:
includes/class-wpa9-admin.php:20 add_menu_page(__('WPA9 Gallery', 'wpa9-gallery'), __('WPA9 Gallery', 'wpa9-gallery'), self::CAP, self::DASHBOARD_SLUG, array($this, 'page_dashboard'), 'dashicons-format-gallery', 25);




👉 Your next steps

This is your checklist:
Are you the rightful owner of this plugin?
Have you checked for common technical issues?
Other details

If there is something that needs to be fixed, please:
Take your time and fix it. Make sure that everything was addressed and the plugin works.
Update your plugin files at the "Add your plugin" page, while being logged in with your account "aloziak".
Reply to this email.
Please keep your reply short, direct and clear. Avoid overly verbose and long AI responses. Do not list the changes made, we don't need that, we will review the entire plugin again, we won't compare the changes. However, please share any important context or clarifications that may help us during the review.

If after checking the list and do the changes you feel that everything is right or need further clarification, please reply to this email and a volunteer will help you.

If you believe there is a requirement you cannot accomplish and choose not to make changes, your plugin submission will be rejected after three months.

Thanks!

By taking these steps, you're helping the Plugin Review Team work more efficiently — meaning your plugin (along with the thousands of others in the queue) can be reviewed faster. 🚀 We really appreciate your contribution!

Disclaimers

If, at any time during the review process, you wish to change your permalink (aka the plugin slug) "wpa9-gallery", you must explicitly and clearly tell us what you would like it to be. Just changing it in your code and in the display name is not sufficient. Remember, permalinks cannot be altered after approval.
This email was partially auto-generated, so please be aware that some information might not be entirely accurate. No personal data was shared with the AI during this process. If you notice any obvious errors or something seems off, feel free to reply — we’ll be happy to take a closer look and readjust this automation.

Review ID: AUTOPREREVIEW ❗OWN wpa9-gallery/aloziak/18Jun26/T1 18Jun26/4.0.1 (P0TDX328034HGN)


--
WordPress Plugins Team | plugins@wordpress.org
https://make.wordpress.org/plugins/
https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
https://wordpress.org/plugins/plugin-check/