<?php
/**
 * @version   1.8 November 13, 2012
 * @author    RocketTheme, LLC http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2012 RocketTheme, LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 */
?>
<div id="newsflash" class="roknewsflash">
    <span class="flashing"><?php echo $instance['pretext']; ?></span>
    <ul style="margin-<?php echo ( is_rtl() ) ? 'right' : 'left'; ?>:<?php echo $instance['news_indent']; ?>px;">
	<?php if($roknewsflash->have_posts()) : while($roknewsflash->have_posts()) : $roknewsflash->the_post(); ?>
		<li>
		    <a href="<?php the_permalink(); ?>">
		    <?php
		    if ($instance['usetitle'] == '1') {
		        the_title();
		    } else {
		    	if($instance['content_type'] == 'content') :
			        echo $this->prepareRokContent(get_the_content(false), $instance['preview_count']).'...';
		    	else :
		    		echo $this->prepareRokContent(get_the_excerpt(), $instance['preview_count']).'...';
		    	endif;
		    }
		    ?>
  		    </a>
		</li>
	<?php endwhile; endif; ?>
    </ul>
</div>