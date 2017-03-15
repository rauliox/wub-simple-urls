<?php
/**
* Plugin Name: Wub Simple URLS
* Plugin URI: https://wubpress.com
* Description: Creates Custom URL structures for WooCommerce
* Version: 1.0
* Author: Richard Miles
* Author URI: https://wubpress.com
* License: GPL12
*/

function wub_seo_rewrite_rules() {

  $taxonomy_name = 'product_cat';

  $product_type = 'product';

  //gets categories of taxonomy
  $categories = custom_get_terms($taxonomy_name);

  $posts = custom_get_posts($product_type , $taxonomy_name, $categories);

  foreach ($posts as $post) {
    add_rewrite_rule('^(' . $post->post_name . ')/?$', 'index.php?post_type=product&name=$matches[1]', 'top' );
  }


  foreach ($categories as $category) {
    add_rewrite_rule('^'.$category->slug.'/?$', 'index.php?'.$taxonomy_name.'='.$category->slug,'top');

              //create term link to term with pagination
    add_rewrite_rule('^'.$category->slug.'(/page/([0-9]+))/?$', 'index.php?'.$taxonomy_name.'='.$category->slug.'&paged=$matches[2]','top');
  }
}

function custom_get_terms($term) {
  global $wpdb;

  $out = [];

    //gets all terms from taxonomy ($term)
  $a = $wpdb->get_results($wpdb->prepare("SELECT t.name,t.slug,t.term_group,x.term_taxonomy_id,x.term_id,x.taxonomy,x.description,x.parent,x.count
    FROM {$wpdb->prefix}term_taxonomy x
    LEFT JOIN {$wpdb->prefix}terms t ON (t.term_id = x.term_id)
    WHERE x.taxonomy=%s;",$term));

  foreach ($a as $b) {
      //create instance of term and save into object
    $obj = new stdClass();
    $obj->term_id = $b->term_id;
    $obj->name = $b->name;
    $obj->slug = $b->slug;
    $obj->term_group = $b->term_group;
    $obj->term_taxonomy_id = $b->term_taxonomy_id;
    $obj->taxonomy = $b->taxonomy;
    $obj->description = $b->description;
    $obj->parent = $b->parent;
    $obj->count = $b->count;
    $out[] = $obj;
  }

  return $out;
}

add_action('init',  'wub_seo_rewrite_rules');

function wub_post_custom_link($post_link, $id = 0) {

   $post = get_post($id);

   if ($post->post_type !== 'product') {
    return $post_link;
   }

  $rewrite_string = $post->post_name . '/';

  return home_url(user_trailingslashit($rewrite_string));

}

add_filter('post_type_link', 'wub_post_custom_link' , 10, 2);

function wub_term_custom_link( $url, $term, $taxonomy) {

  return get_home_url() . '/'. $term->slug . '/';
}

add_filter('term_link', 'wub_term_custom_link', 10, 3);


function custom_get_posts($post_type, $taxonomy, $terms = array()) {

  global $wpdb;

  $slugs = slug_array_to_csv($terms);

  $a = $wpdb->get_results("SELECT ID, post_name, post_type FROM $wpdb->posts
    LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)
    LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
    LEFT JOIN $wpdb->terms ON($wpdb->term_taxonomy.term_id = $wpdb->terms.term_id)
    WHERE $wpdb->terms.name  NOT IN ($slugs)
    AND $wpdb->posts.post_status = 'publish'
    AND $wpdb->posts.post_type = '$post_type'
    ");

  return $a;
}

function slug_array_to_csv($terms) {
    $slugs = [];

    if (isset($terms)) {

      foreach ($terms as $term) {
        $slugs[] = "'" . $term->slug . "'";
      }
    }

    if($slugs) {
      return implode(", ", $slugs);
    } else {
      return false;
    }
  }

?>