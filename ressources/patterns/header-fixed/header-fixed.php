<?php
/**
 * Title: Header Fixed
 * Slug: ng1-base/header-fixed
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Site header with site title and navigation fixed.
 *
 * @package WordPress
 * @subpackage ng1-base
 * @since ng1-base 1.0
 */

?>
<!-- wp:group {"align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base-3"}}}},"backgroundColor":"contrast","textColor":"base-3","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-base-3-color has-contrast-background-color has-text-color has-background has-link-color"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:site-title {"level":0} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group"><!-- wp:navigation {"ref":10,"overlayBackgroundColor":"base","overlayTextColor":"contrast","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->