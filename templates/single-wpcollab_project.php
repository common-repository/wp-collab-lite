<?php
/*
Template for WPCollab Single Project 
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


			<div id="content" class="wpcollab-project-content">

				<div id="inner-content" class="wrap">

						<div id="main" class="twelve" role="main">

							<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
							
							<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?> role="article">
								<?php if(!post_password_required()){ // show project information?>
								
								<?php 
									//Get Project Meta 
									$post_id=get_the_ID();
									$project_status = get_post_meta( $post_id, 'wpcollab_project_status', true );
									$project_manager = get_post_meta( $post_id, 'wpcollab_project_manager', true );
									$project_extra = get_post_meta( $post_id, 'wpcollab_project_extra', true );
									
									$project_manager = get_userdata($project_manager);		

								?>								
								<header class="article-header entry-header">
									<h1 class="single-title project-title"><?php the_title(); ?></h1>
									<div class="wpcollab-project-dates row">
										<div class="project-date sixcol">
											<span class="date-caption"><?php _e('Project created','wp-collab');?></span> <br />
											<span class="date-value"><?php echo get_the_time('d M, Y \a\t H:i \G\M\TP', $post->ID); ?></span>
										</div>
										<div class="project-date-modified sixcol">
											<span class="date-caption"><?php _e('Last updated','wp-collab');?></span> <br />
											<span class="date-value"><?php echo get_the_modified_time('d M, Y \a\t H:i \G\M\TP', $post->ID); ?></span>
										</div>
									</div>	
									<div class="wpcollab-project-status">
										<span class="status-caption"><?php _e('Status','wp-collab');?></span>
										<span class="status-value"><?php echo  $project_status?></span>
									</div>									
								</header>


								<section class="entry-content ">

									<div class="wpcollab-project-manager">
										<fieldset>
										<legend><?php _e('Project Manager','wp-collab');?></legend>
											<?php
											if(function_exists('get_avatar') && get_option('show_avatars')){
												echo get_avatar($project_manager->ID, 64);
											}
											?>											
											<span class="project-manager"><?php echo $project_manager->display_name;	?></span>
										</fieldset>
									</div>
									<div class="wpcollab-project-client">
										<fieldset>
										<legend><?php _e('Client','wp-collab');?></legend>								
											<p class="project-client-name"><?php _e('Name','wp-collab');?>: <?php echo  $project_extra['client_name'];?></p>
											<p class="project-client-email"><?php _e('Email','wp-collab');?>: <?php echo  $project_extra['client_email'];?></p>
											<?php
											// Show hidden data for website administrators and authors
											if(current_user_can( 'edit_posts' )&&($project_extra['client_details'])){?>
											<p class="project-client-details wpcollab-admin-only"><?php echo  nl2br($project_extra['client_details']);?></p>
											<?php } ?>
										</fieldset>		
									</div>
									<?php 
									if(has_post_thumbnail()){?>
									<div class="wpcollab-project-featured-image">
										<fieldset>
										<legend><?php _e('Featured Image','wp-collab');?></legend>			
											<?php the_post_thumbnail(); ?>
										</fieldset>			
									</div>	
									<?php } ?>									
									<div class="wpcollab-project-description">
										<fieldset>
										<legend><?php _e('Project Description','wp-collab');?></legend>								
											<?php the_content();?>
										</fieldset>		
									</div>
									<?php if(current_user_can( 'edit_posts' )&&($project_extra['project_notes'])){?>
									<div class="wpcollab-project-description wpcollab-admin-only">
										<fieldset>
										<legend><?php _e('Project Notes','wp-collab');?></legend>								
										<?php echo  nl2br($project_extra['project_notes']);?>
										</fieldset>		
									</div>								
									<?php } ?>
								</section>
								<footer class="entry-meta">
									<?php comments_template(); ?>		
								</footer>
							
								<?php }else{ // Display password form if password needed?>
								<header class="article-header entry-header">
									<h1><?php _e( 'Project Access','wp-collab'); ?></h1>
								</header>
								<section class="entry-content clearfix">
									<?php the_content(); ?>
								</section>								
								
								<?php }?>

							</article>

							<?php endwhile; ?>

							<?php else : //END if(have_posts()) ?>

									<article id="post-not-found" class="hentry clearfix">
										<header class="article-header">
											<h1><?php _e( 'Project not found!','wp-collab'); ?></h1>
										</header>
										<section class="entry-content">
											<p><?php _e( 'Contact us for more details','wp-collab' ); ?></p>
										</section>
										<footer class="article-footer">
									</article>

							<?php endif; ?>

						</div>


				</div>

			</div>

<?php get_footer(); ?>
