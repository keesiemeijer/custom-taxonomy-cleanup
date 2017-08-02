<div class="wrap rpbt_cache">
	<h1><?php _e( 'Custom Taxonomy Cleanup', 'custom-taxonomy-cleanup' ); ?></h1>
	<?php echo $notice; ?>
	<h3>	
		<?php
		/* translators: 1: Total term count, 2: Custom taxonomy count, 3: 'custom taxonomy' or 'custom taxonomies' (single/plural) */
		printf( _n( '%1$d term from %2$d unused %3$s detected!', '%1$d terms from %2$d unused %3$s detected!', $total, 'custom-taxonomy-cleanup' ), number_format_i18n( $total ), number_format_i18n( $type_count ), $type_str ); ?>
	</h3>
	<p>
		<?php _e( 'Here you can delete terms from custom taxonomies that are currently not registered (no longer in use).', 'custom-taxonomy-cleanup' ); ?><br/>		 
		<?php
			/* translators: %d: Batch size */
			printf( __( 'Terms are deleted in batches of %d terms.', 'custom-taxonomy-cleanup' ), $this->batch_size ); ?>
	</p>
	<p>
		<?php _e( "It's recommended you <strong style='font-weight:bold; color:red;'>make a database backup</strong> before proceeding.", 'custom-taxonomy-cleanup' ); ?>
	</p>
	<hr>
	<form method="post" action="">
		<?php wp_nonce_field( 'custom_taxonomy_cleanup_nonce', 'security' ); ?>
		<table class='form-table'>
			<tr>
				<th scope='row'>
					<label for="ctc_taxonomy">
						<?php _ex( 'Taxonomy', 'Taxonomy form label text', 'custom-taxonomy-cleanup' ); ?>
					</label>
				</th>
				<td>
					<select id="ctc_taxonomy" name="ctc_taxonomy">
						<?php echo $options; ?>
					</select>
					<p class="description">
						<?php _e( 'The Taxonomy you want to delete terms from.', 'custom-taxonomy-cleanup' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<input id="custom_taxonomy_cleanup" class="button button-primary" name="custom_taxonomy_cleanup" value="Delete Terms!" type="submit">
	</form><br/>
	<hr>
	<p>
		<?php
			/* translators: %s: plugin link */
			printf( __( 'This page is generated by the %s plugin.', 'custom-taxonomy-cleanup' ), $plugin_link );
		?>
	</p>
</div>