<?php
defined( 'ABSPATH' ) || exit;

global $options, $is_author;
?>
<li class="item">
    <?php $has_thumb = get_the_post_thumbnail(); if($has_thumb){ ?>
        <div class="item-img">
            <a class="item-img-inner" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank">
                <?php the_post_thumbnail('full'); ?>
            </a>
            <?php
            $category = get_the_category();
            $cat = $category?$category[0]:'';
            if($cat){
                ?>
                <a class="item-category" href="<?php echo esc_url(get_term_link($cat->cat_ID));?>" target="_blank"><?php echo esc_html($cat->name);?></a>
            <?php } ?>
        </div>
    <?php } ?>
    <div class="item-content">
        <h2 class="item-title">
            <a href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank">
                <?php if(isset($is_author) && $post->post_status=='draft'){ echo '<span>【草稿】</span>'; }else if(isset($is_author) && $post->post_status=='pending'){ echo '<span>【待审核】</span>'; }?>
                <?php the_title();?>
            </a>
        </h2>
        <div class="item-excerpt">
            <?php the_excerpt(); ?>
        </div>
        <div class="item-meta">
            <?php
            if(!$has_thumb){
                $category = get_the_category();
                $cat = $category?$category[0]:'';
                if($cat){ ?>
                    <a class="item-meta-li" href="<?php echo esc_url(get_category_link($cat->cat_ID));?>" target="_blank"><?php echo esc_html($cat->name);?></a>
                <?php } } ?>
            <span class="item-meta-li date"><?php the_time(get_option('date_format'));?></span>
        </div>
    </div>
</li>