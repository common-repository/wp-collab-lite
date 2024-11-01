<?php
/*
Template for WPCollab Archives Project 

If user is can 'edit_posts' - show list of all project, else - show project access form
*/

/**
 * WP Collab
 *
 * @package   WP_Collab
 * @author    Circlewaves Team <support@circlewaves.com>
 * @license   GPL-2.0+
 * @link      http://circlewaves.com
 * @copyright 2014 Circlewaves Team <support@circlewaves.com>
 */
?>

<?php get_header(); ?>

			<div id="content" class="wpcollab-project-content wpcollab-project-archive">

				<div id="inner-content" class="wrap clearfix">
						<div id="main" class="tvelwecol first clearfix" role="main">
					<?php 
						/* Show Projects List to admin and author */
						if(current_user_can( 'edit_posts' )){ 
					?>
						<h1 class="archive-title h2"><?php _e('Projects List','wp-collab');?></h1>

							<?php if (have_posts()) : while (have_posts()) : the_post();?>
							
								<?php 
									//Get Project Meta 
									$post_id=get_the_ID();
									$project_status = get_post_meta( $post_id, 'wpcollab_project_status', true );
									$project_manager = get_post_meta( $post_id, 'wpcollab_project_manager', true );
									$project_extra = get_post_meta( $post_id, 'wpcollab_project_extra', true );
									
									$project_manager = get_userdata($project_manager);		
								?>							

							<article id="post-<?php the_ID(); ?>" <?php post_class( 'clearfix' ); ?> role="article">
								<header class="article-header">
									<h3 class="h2"><a href="<?php echo get_permalink().'?access_token='.$post->post_password;?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
									<div class="wpcollab-project-manager row">
										<span class="manager-caption"><?php _e('Project Manager','wp-collab');?></span>
										<span class="manager-value"><?php echo $project_manager->display_name;	?></span>
									</div>									
									<div class="wpcollab-project-dates row">
										<div class="project-date-modified sixcol">
											<span class="date-caption"><?php _e('Last updated','wp-collab');?></span> <br />
											<span class="date-value"><?php echo get_the_modified_time('d M, Y \a\t H:i \G\M\TP', $post->ID); ?></span>
										</div>
									</div>	
									<div class="wpcollab-project-status row">
										<span class="status-caption"><?php _e('Status','wp-collab');?></span>
										<span class="status-value"><?php echo  $project_status?></span>
									</div>										
								</header>
							</article>

							<?php endwhile; ?>

									<?php 
									
										echo '<nav class="pagination">';
										$bignum = 999999999;
											echo paginate_links( array(
												'base' 			=> str_replace( $bignum, '%#%', esc_url( get_pagenum_link($bignum) ) ),
												'format' 		=> '',
												'current' 		=> max( 1, get_query_var('paged') ),
												'total' 		=> $wp_query->max_num_pages,
												'prev_text' 	=> '&larr;',
												'next_text' 	=> '&rarr;',
												'type'			=> 'list',
												'end_size'		=> 3,
												'mid_size'		=> 3
											) );
										
										echo '</nav>';
									
									?>

							<?php else : //END if(have_posts()) ?>

									<article id="post-not-found" class="hentry clearfix">
										<header class="article-header">
											<h1><?php _e( 'Sorry, no projects found','wp-collab' ); ?></h1>
										</header>
									</article>

							<?php endif; ?>

					<?php 
						/* If user is not an admin/author - display form */
						}else{
					?>
							<div class="page-content">
									<h1 class="archive-title h1"><?php _e('Project Access','wp-collab');?></h1>
									<section class="entry-content clearfix">
									<?php if(isset($_POST['WPCollabFormHasError']) && $_POST['WPCollabFormError']){?>
										<div class="wpcollab-error-msg">
											<?php _e($_POST['WPCollabFormError'],'wp-collab');?>
										</div><!-- .error -->
									<?php } ?>	
									<div class="wpcollab-form-wrapper">
									<form method="post" action="">
										<div class="wpcollab-row-wrapper row">
											<label for="project_uid" class="col twocol"><?php _e('Project UID','wp-collab');?>:</label>
											<input type="text" name="project_uid" class="col sixcol" value="" />
										</div>
										<div class="wpcollab-row-wrapper row">
											<label for="project_password" class="col twocol"><?php _e('Password','wp-collab');?>:</label>
											<input type="password" id="project_password" name="project_password"  class="col sixcol" value="" />
										</div>
										<div class="wpcollab-row-wrapper row">
											<input type="submit" value="<?php _e('Login','wp-collab');?>">
										</div>
										<?php wp_nonce_field( 'send-wpcollab-uid','wpcollab_nonce' ) ?>
									</form>
									</div>
								</section>
							</div>
					<?php } // Form end?>
						</div>

								</div>

			</div>

<?php get_footer(); ?>
