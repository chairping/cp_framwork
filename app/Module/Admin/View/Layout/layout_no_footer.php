<?php
$layout = \Util\Helper::getConfig('layout');

require($layout['directory'] . '/layouts/simple_header.html' );

// require($layout['directory'] . '/layouts/simple_header_search_nav.html' );

require($layout['directory'] . '/layouts/simple_menu.html' );

?>
<?php echo $_content_; ?>
