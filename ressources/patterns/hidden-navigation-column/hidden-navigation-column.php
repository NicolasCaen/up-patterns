<?php
/**
 * Title: Navigation Column
 * Slug: ng1-base/hidden-navigation-column
 * Block Types: core/navigation
 * Categories: Navigation
 * Description: Navigation en colonne.
 *
 * @package WordPress
 * @subpackage ng1-base
 * @since ng1-base 1.0
 */

?>
<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"}} -->
	<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Accueil', 'ng1-base' ); ?>","url":"#"} /-->
	<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Qui sommes-nous ?', 'ng1-base' ); ?>","url":"#"} /-->
	<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Nos services', 'ng1-base' ); ?>","url":"#"} /-->
	<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Contact', 'ng1-base' ); ?>","url":"#"} /-->
<!-- /wp:navigation -->