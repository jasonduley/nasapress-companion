<?php
/**
* Plugin Name: NASAPress Companion
* Plugin URI: https://github.com/bruffridge/nasapress-companion
* Description: Contains functions and shortcodes used by the NASAPress Theme.
* Version: 1.0
* Author: Shaun McKeehan and Brandon Ruffridge
* License: GPL3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

function the_excerpt_max_charlength($excerpt, $charlength) {
	$charlength++;
  $out = '';

	if ( mb_strlen( $excerpt ) > $charlength ) {
		$subex = mb_substr( $excerpt, 0, $charlength - 5 );
		$exwords = explode( ' ', $subex );
		$excut = - ( mb_strlen( $exwords[ count( $exwords ) - 1 ] ) );
		if ( $excut < 0 ) {
			$out .= mb_substr( $subex, 0, $excut );
		} else {
			$out .= $subex;
		}
		$out .= ' ...';
	} else {
		$out .= $excerpt;
	}
  return $out;
}

function displayCatPages($orderby, $categoryId, $display, $includeChildren) {
  
  $content = "";
  
  // Query for category pages
  $categoryPagesArgs = array(
    'post_type' => 'page',
    'order' => 'ASC',
    'orderby' => $orderby,
    'tax_query' => array(
      array(
        'taxonomy' => 'category',
        'terms'    => $categoryId,
        'include_children' => $includeChildren,
      ),
    ),
    'posts_per_page' => -1
  );
  $categoryPages = new WP_Query($categoryPagesArgs);

  // Loop through each page in the category
  $pageCount = 0;
  while($categoryPages->have_posts()) {

    $pageCount++;
    $categoryPages->the_post();

//==========================List View Container===============================
    if($display == 'both' || $display == 'list') {
      // Page thumbnail
      $content .= '<article class="listView usa-grid-full grc-facilities-facility">';
      $content .= '<div class="usa-width-one-third">';
      $content .= '<figure class="wp-caption">';
      $content .= get_the_post_thumbnail(null, 'thumbnail');

      // Page thumbnail
      $content .= '<figcaption class="wp-caption-text">'.get_the_post_thumbnail_caption().'</figcaption>';
      $content .= '</figure>';
      $content .= '</div>';

      // Page title
      $content .= '<div class="usa-width-two-thirds">';
      $content .= '<h3><a title="'.the_title_attribute(array('echo' => false)).'" href="'.get_the_permalink().'">'.get_the_title().'</a></h3>';

      // Page excerpt
      $content .= '<p>'.get_the_excerpt().'</p>';
      $content .= '</div>';
      $content .= '</article>';

//=========================End of List View====================================
    }
    if ($display == 'both' || $display == 'grid') {
//==========================Grid View Container================================

      if($pageCount % 3 == 1)
        $content .= '<div class="usa-grid-full">';

      // Grid item
      $content .= '<a title="'.the_title_attribute(array('echo' => false)).'" href="'.get_the_permalink().'" class=" gridView usa-width-one-third grc-grid-item ">';

      // Page image
      $content .= get_the_post_thumbnail(null, 'thumbnail', array( 'class' => 'grc-grid-item-image' ));

      // Page title
      $content .= '<div class="grc-grid-item-label">';
      $content .= get_the_title();
      $content .= externalText(get_the_ID());
      $content .= '</div>';

      // Overlay
      $content .= '<div class="grc-grid-item-overlay">';
      $content .= '<div class="grc-grid-item-text">';

      // Overlay text
      $content .= the_excerpt_max_charlength(get_the_excerpt(), 160);
      $content .= externalText(get_the_ID());
      $content .= '</div>';
      $content .= '</div>';

      $content .= '</a>';

      if($pageCount % 3 == 0)
        $content .= '</div>'; // USA Grid Full

//======================End of Grid View Containe===============================
    }
  }

  if($pageCount % 3 != 0) {
    $content .= '</div>'; // USA Grid Full
  }

  // Reset the WP_Query globals
  wp_reset_postdata();
  
  return $content;
}

//==============================================================================
/**
 * Add shortcode for category listings
 * 
 * Shortcode parameters:
 *  display: both (default), grid, list
 *    Display options. Only both will show the view toggle buttons.
 *  slug: {category slug}
 *  which: childCats (default), thisCat
 *    Choose whether to list pages in this category, or in sections by child category.
 *  includechildren: yes (default), no
 *    Choose whether to include pages that are in a subcategory.
 *  orderby: menu_order (default), title
 * 
 */
function categoryList( $atts ) {
  $which = is_array($atts) && array_key_exists('which', $atts) ? $atts['which'] : 'childCats';
  $includeChildren = is_array($atts) && array_key_exists('includechildren', $atts) ? $atts['includechildren'] : true;
  $includeChildren = $includeChildren === "no" ? false : true;
  $display = is_array($atts) && array_key_exists('display', $atts) ? $atts['display'] : 'both';
  $orderby = is_array($atts) && array_key_exists('orderby', $atts) ? $atts['orderby'] : 'menu_order';
  $orderby = $orderby == 'title' ? 'title' : 'menu_order';
  
  $content = '<div class="grc-list">';

  // Get the current category ID
  $categoryId = get_category_by_slug($atts['slug'])->term_id;
  $disableGridBtn = '';

	// Gridview and Listview button
  if($display == 'both') {
    $content .= '<button id="switchViewBtn" class="usa-button usa-button-secondary grc-grid-view-links"><i class="fa fa-list" aria-hidden="true"></i> Switch to List View</button>';
  }

  if($which == 'thisCat') {
    $content .= displayCatPages($orderby, $categoryId, $display, $includeChildren);
  }
  elseif($which == 'childCats') {
    // Get any direct children of the current category
    $childrenCategoryArgs = array('parent' => $categoryId);
    $childrenCategories = get_categories($childrenCategoryArgs);

    foreach($childrenCategories as $category) {

      // Category title
      $content .= '<h2 class="usa-heading">'.$category->name.'</h2>';

      if($category->description) {

        $desc = wpautop( $category->description ); // Wrap paragraphs in p tags
        $desc = do_shortcode( $desc ); // Render shortcodes

        $content .= $desc;
      }

      $content .= displayCatPages($orderby, $category->term_id, $display, $includeChildren);
    }
  }
  
  // Return the formatted HTML
  $content .= '</div>';
  return $content;
}
add_shortcode('category-list', 'categoryList');

//==============================================================================

/**
 * Add shortcode for child page listings
 * Shortcode parameters:
 *  display: both (default), grid, list
 *    Display options. Only both will show the view toggle buttons.
 *  columns: 3 (default), 4
 *    Only applies to display: grid, or both.
 *  parent: {current page id} (default), {some other page id}
 *  fullgrid: yes (default), no
 *    Displays listing in usa-grid-full or just usa-grid.
 *  orderby: menu_order (default), title
 *    Does not apply if pages parameter is set.
 *  limit: {-1} (default), {another whole number}
 *    Choose to limit how many pages display. -1 = no limit.
 *  pages: {comma separated list of child page IDs to display}
 *   pages will be displayed in the order they are listed in; orderby parameter does not apply.
 */
function childrenList( $atts ) {
  $content = '<div class="grc-list">';
  $displayType = $_GET['display'] ? $_GET['display'] : 'grid';

  $pageId = is_array($atts) && array_key_exists('parent', $atts) ? $atts['parent'] : get_the_ID();
  $columns = is_array($atts) && array_key_exists('columns', $atts) ? $atts['columns'] : 3;
  $display = is_array($atts) && array_key_exists('display', $atts) ? $atts['display'] : 'both';
  $fullGrid = is_array($atts) && array_key_exists('fullgrid', $atts) ? $atts['fullgrid'] : 'yes';
  $limit = is_array($atts) && array_key_exists('limit', $atts) ? $atts['limit'] : -1;
  $pages = is_array($atts) && array_key_exists('pages', $atts) ? explode(',', preg_replace( '/\s*,\s*/', ',', filter_var( $atts['pages'], FILTER_SANITIZE_STRING ) ) ) : false;
  $orderby = is_array($atts) && array_key_exists('orderby', $atts) ? $atts['orderby'] : 'menu_order';

  $disableGridBtn = '';

  $gridColumns = $columns == 4 ? 'one-fourth' : 'one-third';
  $gridType = $fullGrid == 'yes' ? '-full' : '';
  $orderby = $orderby == 'title' ? 'title' : 'menu_order';

  if($display == 'list') {
    $displayType = 'list';
  }
  else if ($display == 'grid') {
    $displayType = 'grid';
  }

  // View switching buttons
  if($display == 'both') {
    if($displayType == 'grid') {
      $content .= '<div class="clearfix">&nbsp;</div><p class="grc-grid-view-links" id="jmp' . $pageId . '"><span class="grc-left-toggle-link-active"><i class="fa fa-th-large" aria-hidden="true"></i> Grid View</span><a href="?display=list#jmp' . $pageId . '" class="grc-grid-link"><i class="fa fa-list" aria-hidden="true"></i>List View</a></p>';
    }
    else if($displayType == 'list') {
      $content .= '<div class="clearfix">&nbsp;</div><p class="grc-grid-view-links" id="jmp' . $pageId . '"><a href=".#jmp' . $pageId . '" class="grc-grid-link"><i class="fa fa-th-large" aria-hidden="true"></i>Grid View</a><span class="grc-right-toggle-link-active"><i class="fa fa-list" aria-hidden="true"></i> List View</span></p>';
    }
  }

  // Get any direct children of the current page
// Query for category pages
  if($pages) {
    $childPagesArgs = array(
      'post__in' => $pages,
      'post_type' => 'page',
      'orderby' => 'post__in',
      'post_parent' => $pageId,
			'posts_per_page' => $limit
    );
  }
  else {
    $childPagesArgs = array(
      'post_type' => 'page',
      'order' => 'ASC',
      'orderby' => $orderby,
      'post_parent' => $pageId,
			'posts_per_page' => $limit
    );
  }
    $childPages = new WP_Query($childPagesArgs);

    // Loop through each page in the category
    $pageCount = 0;

    if($childPages->have_posts() && $displayType == 'grid')
      $content .= '<div class="usa-grid' . $gridType . '">';

    while($childPages->have_posts()) {
      $childPages->the_post();
      $pageCount++;

      if($displayType == 'list') {
        // Page thumbnail
        $content .= '<article class="usa-grid' . $gridType . ' grc-facilities-facility">';
        $postThumbnail = get_the_post_thumbnail(null, 'thumbnail');
        if($postThumbnail) {
          $content .= '<div class="usa-width-one-third">';
          $content .= '<figure class="wp-caption">';
          $content .= $postThumbnail;

          // Page thumbnail
          $content .= '<figcaption class="wp-caption-text">'.get_the_post_thumbnail_caption().'</figcaption>';
          $content .= '</figure>';
          $content .= '</div>';

          $content .= '<div class="usa-width-two-thirds">';
        }
        else {
          $content .= '<div class="usa-width-one-whole">';
        }
        // Page title
        $content .= '<h3><a title="'.the_title_attribute(array('echo' => false)).'" href="'.get_the_permalink().'">'.get_the_title().'</a></h3>';

        // Page excerpt
        $content .= '<p>'.get_the_excerpt().'</p>';
        $content .= '</div>';
        $content .= '</article>';
      } else {
        // Grid item
        if($pageCount > 1 && $pageCount % $columns == 1) {
          $content .= '</div><div class="usa-grid' . $gridType . '">';
        }
        $content .= '<a title="'.the_title_attribute(array('echo' => false)).'" href="'.get_the_permalink().'" class="usa-width-' . $gridColumns . ' grc-grid-item">';

        // Page image
        $content .= get_the_post_thumbnail(null, 'thumbnail', array( 'class' => 'grc-grid-item-image' ));

        // Page title
        $content .= '<div class="grc-grid-item-label">';
        $content .= get_the_title();
        $content .= externalText(get_the_ID());
        $content .= '</div>';

        // Overlay
        $content .= '<div class="grc-grid-item-overlay">';
        $content .= '<div class="grc-grid-item-text">';

        // Overlay text
        $content .= the_excerpt_max_charlength(get_the_excerpt(), 160);
        $content .= externalText(get_the_ID());
        $content .= '</div>';
        $content .= '</div>';

        $content .= '</a>';
      }
    }

    if($pageCount > 0 && $displayType == 'grid')
      $content .= '</div>';

    // Reset the WP_Query globals
    wp_reset_postdata();

  $content .= "</div>";
  // Return the formatted HTML
  return $content;
}
add_shortcode('children-list', 'childrenList');

//==============================================================================
/**
 * Add shortcode for category posts listings
 */
function categoryPostsList( $atts ) {
  $content = '<div class="grc-list">';

  // Get the current category ID
  $category = get_category_by_slug($atts['slug']);
  /*$disableGridBtn = '';

	// Gridview and Listview button
	$content .= '<button id="switchViewBtn" class="usa-button usa-button-secondary grc-grid-view-links"><i class="fa fa-list" aria-hidden="true"></i> Switch to List View</button>';

  // Category title
  $content .= '<h2 class="usa-heading">'.$category->name.'</h2>';

  if($category->description) {

    $desc = wpautop( $category->description ); // Wrap paragraphs in p tags
    $desc = do_shortcode( $desc ); // Render shortcodes

    $content .= $desc;
  }*/

  // Query for category posts
  $categoryPostsArgs = array(
    'post_type' => 'post',
    'order' => 'DESC',
    'orderby' => 'date',
    'tax_query' => array(
      array(
        'taxonomy' => 'category',
        'field'    => 'slug',
        'terms'    => $category->slug,
      ),
    ),
    'posts_per_page' => 6
  );
  $categoryPosts = new WP_Query($categoryPostsArgs);

  // Loop through each post in the category
  $postCount = 0;
  while($categoryPosts->have_posts()) {

    $postCount++;
    $categoryPosts->the_post();
/*
    //==========================List View Container===============================

    // Post thumbnail
    $content .= '<article class="listView usa-grid-full grc-facilities-facility">';
    $content .= '<div class="usa-width-one-third">';
    $content .= '<figure class="wp-caption">';
    $content .= get_the_post_thumbnail(null, 'thumbnail');

    // Post thumbnail
    $content .= '<figcaption class="wp-caption-text">'.get_the_post_thumbnail_caption().'</figcaption>';
    $content .= '</figure>';
    $content .= '</div>';

    // Post title
    $content .= '<div class="usa-width-two-thirds">';
    $content .= '<h3><a title="'.the_title_attribute(array('echo' => false)).'" href="'.get_the_permalink().'">'.get_the_title().'</a></h3>';

    // Post excerpt
    $content .= '<p>'.get_the_excerpt().'</p>';
    $content .= '</div>';
    $content .= '</article>';

    //=========================End of List View====================================
*/
    //==========================Grid View Container================================

    if($postCount % 3 == 1)
      $content .= '<div class="usa-grid-full">';

    // Grid item
    $content .= '<a title="'.the_title_attribute(array('echo' => false)).'" href="'.get_the_permalink().'" class=" gridView usa-width-one-third grc-grid-item ">';

    // Post image
    $content .= get_the_post_thumbnail(null, 'thumbnail', array( 'class' => 'grc-grid-item-image' ));

    // Post title
    $content .= '<div class="grc-grid-item-label">';
    $content .= get_the_title();
    $content .= '</div>';

    // Overlay
    $content .= '<div class="grc-grid-item-overlay">';
    $content .= '<div class="grc-grid-item-text">';

    // Overlay text
    $content .= the_excerpt_max_charlength(get_the_excerpt(), 160);
    $content .= '</div>';
    $content .= '</div>';

    $content .= '</a>';

    if($postCount % 3 == 0)
      $content .= '</div>'; // USA Grid Full

    //======================End of Grid View Container===============================
  }

  if($postCount % 3 != 0) {
    $content .= '</div>'; // USA Grid Full
  }
  
  if($categoryPosts->found_posts > 6) { // if there are more than 6 posts in the category display see more posts link.
    $content .= '<div class="usa-grid-full"><div class="usa-width-one-whole"><a href="' . get_category_link($category->cat_ID) . '" class="usa-button usa-button-secondary">More ' . $category->name . ' news</a></div></div>'; // USA Grid Full
  }

  // Reset the WP_Query globals
  wp_reset_postdata();

  // Return the formatted HTML
  $content .= '</div>';
  return $content;
}
add_shortcode('category-posts-list', 'categoryPostsList');

/**
 * Pull NASA.gov posts via API
 */
function display_portal_posts( $atts ) {
  // Shortcode attributes
  $collection = is_array($atts) && array_key_exists('collection', $atts) && preg_match('/[0-9]+/', $atts['collection']) ? $atts['collection'] : '7460';
	$columns = is_array($atts) && array_key_exists('columns', $atts) ? $atts['columns'] : 4;
	$fullGrid = is_array($atts) && array_key_exists('fullgrid', $atts) ? $atts['fullgrid'] : 'yes';
	$limit = is_array($atts) && array_key_exists('limit', $atts) ? $atts['limit'] : 4;

	// CSS class manupulations
	$gridColumns = $columns == 4 ? 'one-fourth' : 'one-third';
  $gridType = $fullGrid == 'yes' ? '-full' : '';
  $nasaImageURL = '/sites/default/files/styles/1x1_cardfeed/public/';
  $usingCache = false;

  if(environment() != 'development') {
    $memcache = new Memcache;
    
    //todo-config
		$memcache->connect('YOUR_MEMCACHE_HOST', YOUR_MEMCACHE_PORT);

		if($memcache) {
      $grcNews = $memcache->get('portal-'.$collection);
      $usingCache = $grcNews === false ? false : true;
		}
	}

  if(!$usingCache) { // No valid cached values
    // API Endpoints
    $nasaApiUrl = 'https://www.nasa.gov/api/2';
    //todo-config
    $nasaQueryUrl = '/ubernode/_search?size=24&from=0&sort=promo-date-time%3Adesc&q=((ubernode-type%3Afeature%20OR%20ubernode-type%3Aimage%20OR%20ubernode-type%3Acollection_asset)%20AND%20(collections%3A'.$collection.'))&_source_include=promo-date-time%2Cmaster-image%2Cnid%2Ctitle%2Ctopics%2Cmissions%2Ccollections%2Cother-tags%2Cubernode-type%2Cprimary-tag%2Csecondary-tag%2Ccardfeed-title%2Ctype%2Ccollection-asset-link%2Clink-or-attachment%2Cpr-leader-sentence%2Cimage-feature-caption%2Cattachments%2Curi';

    // Fetch collection of Ubernodes
    $apiResponse = wp_remote_get($nasaApiUrl.$nasaQueryUrl);

    if(is_wp_error($apiResponse)) {
      return '<div class="grc-list usa-grid">Error fetching NASA Portal posts :(</div>';
    }

    $grcNews = json_decode($apiResponse['body']);
    $grcNews = $grcNews->hits->hits;

    if(!is_array($grcNews)) {
      return '<div class="grc-list usa-grid">Error fetching NASA Portal posts :(</div>';
    }
  }

	$content = '<div class="grc-list portal-news usa-grid'.$gridType.'">';
	$i = 0;

	// Loop through each Ubernode (Post)
	foreach($grcNews as $node) {
		if($i >= $limit) {
			break;
		}

		// Format the post
		$content .= '<a href="https://www.nasa.gov'.esc_attr($node->_source->uri).'" class="usa-width-' . $gridColumns . ' grc-grid-item">';

		// Page image
		$content .= '<img class="grc-grid-item-image wp-post-image grc-grid-item-image" src="https://www.nasa.gov'.$nasaImageURL.esc_attr(ltrim($node->_source->{'master-image'}->uri, 'public://')).'" alt="" width="300" height="300" />';

		// Page title
		$content .= '<div class="grc-grid-item-label">';
		$content .= esc_html($node->_source->{'cardfeed-title'});
		$content .= '<i class="fa fa-external-link" aria-hidden="true"></i></div>';

		// Overlay
		$content .= '<div class="grc-grid-item-overlay">';
		$content .= '<div class="grc-grid-item-text">';

    // Overlay text
		$content .= esc_html(the_excerpt_max_charlength($node->_source->{'image-feature-caption'} ? $node->_source->{'image-feature-caption'} : ($node->_source->{'pr-leader-sentence'} ? wp_strip_all_tags($node->_source->{'pr-leader-sentence'}) : 'Read more'), 160));
		$content .= '<i class="fa fa-external-link" aria-hidden="true"></i></div>';
		$content .= '</div>';

		$content .= '</a>';

		$i++;
	}

  if(environment() != 'development') {
    if(!$usingCache) { // If $nodeList is false it means we are using cached values.
      $memcache->set('portal-'.$collection, $grcNews, 0, 1800); // Cache for 30 minutes.
    }
    $memcache->close();
  }

	$content .= '</div>';

	return $content;
}
add_shortcode('portal-posts', 'display_portal_posts');

/**
 * Display spinoff posts via the NASA Technology API
 */
function display_spinoff_posts( $atts ) {
	// Attribute information from shortcode
	$columns = is_array($atts) && array_key_exists('columns', $atts) ? $atts['columns'] : 3;
	$fullGrid = is_array($atts) && array_key_exists('fullgrid', $atts) ? $atts['fullgrid'] : 'yes';
	$limit = is_array($atts) && array_key_exists('limit', $atts) ? $atts['limit'] : 3;

	// CSS class manupulations
	$gridColumns = $columns == 4 ? 'one-fourth' : 'one-third';
  $gridType = $fullGrid == 'yes' ? '-full' : '';

  if(environment() != 'development') {
		$memcache = new Memcache;
    //todo-config
		$memcache->connect('YOUR_MEMCACHE_HOST', YOUR_MEMCACHE_PORT);
		$spinoffs = false;
		if($memcache) {
			$spinoffs = $memcache->get('spinoffs');
		}
	}
	else {
		$spinoffs = false;
	}

	if($spinoffs === false) {
    // Query for spinoff data
    //todo-config
    $nasaApiUrl = 'https://technology.nasa.gov/api/query/spinoff/grc';
    $apiResponse = request_data($nasaApiUrl);

    if(!$apiResponse) {
      return '<div class="grc-list usa-grid">Error fetching NASA Spinoff posts :(</div>';
    }
    $jsonResponse = json_decode($apiResponse);
    $spinoffs = $jsonResponse->results;
    
    if(!$spinoffs) {
      return '<div class="grc-list usa-grid">Error fetching NASA Spinoff posts :(</div>';
    }
    
    usort($spinoffs, function($a, $b) {
      $aPieces = explode('-', $a[1]);
      $bPieces = explode('-', $b[1]);

      $aNum = intval($aPieces[2]);
      $bNum = intval($bPieces[2]);

      if ($aNum == $bNum) {
          return 0;
      }
      return ($aNum > $bNum) ? -1 : 1;
    });

    if(environment() != 'development') {
			$memcache->set('spinoffs', $spinoffs, 0, 86400); // Cache for one day.
		}
	}

	if(environment() != 'development') {
		$memcache->close();
	}

	$content = '<div class="grc-list usa-grid">';
	$i = 0;

	foreach($spinoffs as $spinoff) {
		if($i >= $limit) {
			break;
		}

    $postData = $spinoff;
    //todo-config
    $content .= '<div class="usa-width-' . $gridColumns . ' grc-txt-grid-item"><p class="site-subheading h3">' . esc_html(ucwords($postData[5])) . '</p><h3><a href="https://spinoff.nasa.gov/database/spinoffDetail.php?this=/spinoff//grc/'.esc_attr($postData[1]).'">' . esc_html(trim($postData[2])) . '<i class="fa fa-external-link" aria-hidden="true"></i></a></h3><p>';
    $content .= esc_html(the_excerpt_max_charlength($postData[3] ? $postData[3] : 'Read more...', 160));
    $content .= '</p></div>';

		$i++;
	}

	$content .= '</div>';

	return $content;
}
add_shortcode('spinoff-posts', 'display_spinoff_posts');

function environment() {

  $svrname = preg_replace('#^https?://#', '', home_url());
  // todo-config
	switch($svrname) {
    case 'YOUR_PROD_URL':
      return 'production';
    case 'YOUR_TEST_URL':
      return 'test';
    default:
      return 'development';
  }
}

/*
Plugin Name: Shortcode Empty Paragraph Fix
Plugin URI: http://www.johannheyne.de/wordpress/shortcode-empty-paragraph-fix/
Description: This plugin fixes the empty paragraphs using shortcodes
Author URI: http://www.johannheyne.de
Version: 0.2
Put this in /wp-content/plugins/ of your Wordpress installation.
If you dont want to fix this problem with a plugin, then simply copy the code to your function.php file in the themefolder.
*/

function shortcode_empty_paragraph_fix( $content ) {
  $array = array (
    '<p>[' => '[',
    ']</p>' => ']',
    ']<br />' => ']'
  );

  $content = strtr( $content, $array );

  return $content;
}
add_filter( 'the_content', 'shortcode_empty_paragraph_fix' );

add_filter( 'gform_field_value_location', 'location_population_function' );
function location_population_function( $value ) {
  return get_permalink();
}

add_filter( 'gform_field_value_user_agent', 'user_agent_population_function' );
function user_agent_population_function( $value ) {
  return $_SERVER['HTTP_USER_AGENT'];
}

/**
 * Defines the function used to initial the cURL library.
 *
 * @param  string  $url        To URL to which the request is being made
 * @return string  $response   The response, if available; otherwise, null
 */
function curl( $url ) {

	$curl = curl_init( $url );

	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $curl, CURLOPT_HEADER, 0 );
	curl_setopt( $curl, CURLOPT_USERAGENT, '' );
	curl_setopt( $curl, CURLOPT_TIMEOUT, 10 );

	$response = curl_exec( $curl );
	if( 0 !== curl_errno( $curl ) || 200 !== curl_getinfo( $curl, CURLINFO_HTTP_CODE ) ) {
		$response = null;
	} // end if
	curl_close( $curl );

	return $response;

} // end curl

/**
 * Retrieves the response from the specified URL using one of PHP's outbound request facilities.
 *
 * @params	$url	The URL of the feed to retrieve.
 * @returns		The response from the URL; null if empty.
 */
function request_data( $url ) {

	$response = null;

	// First, we try to use wp_remote_get
	$response = wp_remote_get( $url );
	if( is_wp_error( $response ) ) {

		// If that doesn't work, then we'll try file_get_contents
		$response = file_get_contents( $url );
		if( false == $response ) {

			// And if that doesn't work, then we'll try curl
			$response = curl( $url );
			if( null == $response ) {
				$response = 0;
			} // end if/else

		} // end if

	} // end if

	// If the response is an array, it's coming from wp_remote_get,
	// so we just want to capture to the body index for json_decode.
	if( is_array( $response ) ) {
		$response = $response['body'];
	} // end if/else

	return $response;

} // end request_data

function externalText($id) {
  $external = '';
  $linksto = get_post_meta($id, "_links_to");
  $linksto = is_array($linksto) && $linksto[0] ? $linksto[0] : null;
  $linksto = substr( $linksto, 0, 4 ) !== "http" && substr( $linksto, 0, 1 ) !== "/" ? 'https://' . $linksto : $linksto;

  if($linksto && filter_var($linksto, FILTER_VALIDATE_URL) && isexternal($linksto)) {
    $external = '<i class="fa fa-external-link" aria-hidden="true"></i>';
  }
  return $external;
}

function isexternal($url) {
  $components = parse_url($url);
  $host = parse_url(get_site_url());
  $host = $host['host'];
  return !empty($components['host']) && !empty($host) && strcasecmp($components['host'], $host); // empty host will indicate url like '/relative.php'
}