<?php
  
  if (empty($_GET['category_id'])) {
    header('Location: '. document::ilink('categories'));
    exit;
  }
  
  if (empty($_GET['page'])) $_GET['page'] = 1;
  if (empty($_GET['sort'])) $_GET['sort'] = 'popularity';
  
  document::$snippets['head_tags']['canonical'] = '<link rel="canonical" href="'. document::href_ilink(null, array(), array('category_id')) .'" />';
  
  breadcrumbs::add(language::translate('title_categories', 'Categories'), document::ilink('categories'));
  
  $category = new ref_category($_GET['category_id']);
  
  if (empty($category->status)) {
    notices::add('errors', language::translate('error_page_not_found', 'The requested page could not be found'));
    header('HTTP/1.1 404 Not Found');
    header('Location: '. document::ilink('categories'));
    exit;
  }
  
  foreach (functions::catalog_category_trail($category->id) as $category_id => $category_name) {
    breadcrumbs::add($category_name, document::ilink(null, array('category_id' => $category_id)));
  }
  
  //document::$snippets['title'] = array(); // reset
  document::$snippets['title'][] = $category->head_title[language::$selected['code']] ? $category->head_title[language::$selected['code']] : $category->name[language::$selected['code']];
  document::$snippets['keywords'] = $category->meta_keywords[language::$selected['code']] ? $category->meta_keywords[language::$selected['code']] : $category->keywords;
  document::$snippets['description'] = $category->meta_description[language::$selected['code']] ? $category->meta_description[language::$selected['code']] : $category->short_description[language::$selected['code']];
  
  functions::draw_fancybox("a.fancybox[data-fancybox-group='product-listing']");

  include(FS_DIR_HTTP_ROOT . WS_DIR_INCLUDES . 'column_left.inc.php');
  
  $box_category_cache_id = cache::cache_id('box_category', array('basename', 'get', 'language', 'currency', 'account', 'prices'));
  if (cache::capture($box_category_cache_id, 'file')) {
    
    $page = new view();
    
    $page->snippets = array(
      'id' => $category->id,
      'name' => $category->name[language::$selected['code']],
      'description' => $category->description[language::$selected['code']],
      'h1_title' => $category->h1_title[language::$selected['code']] ? $category->h1_title[language::$selected['code']] : $category->name[language::$selected['code']],
      'image' => functions::image_resample(FS_DIR_HTTP_ROOT . WS_DIR_IMAGES . $category->image, FS_DIR_HTTP_ROOT . WS_DIR_CACHE, 1024, 0, 'FIT_ONLY_BIGGER'),
      'subcategories' => array(),
      'products' => array(),
      'sort_alternatives' => array(
        'popularity' => language::translate('title_popularity', 'Popularity'),
        'name' => language::translate('title_name', 'Name'),
        'price' => language::translate('title_price', 'Price'),
        'date' => language::translate('title_date', 'Date'),
      ),
    );

  // Subcategories
    $subcategories_query = functions::catalog_categories_query($category->id);
    if (database::num_rows($subcategories_query)) {
      while ($subcategory = database::fetch($subcategories_query)) {
        $page->snippets['subcategories'][] = $subcategory;
      }
    }
    
  // Products
    switch ($category->list_style) {
      case 'rows':
        $items_per_page = 10;
        break;
      case 'columns':
      default:
        $items_per_page = settings::get('items_per_page');
        break;
    }
    
    $products_query = functions::catalog_products_query(
      array(
        'category_id' => $category->id,
        'manufacturers' => !empty($_GET['manufacturers']) ? $_GET['manufacturers'] : null,
        'product_groups' => !empty($_GET['product_groups']) ? $_GET['product_groups'] : null,
        'sort' => $_GET['sort']
      )
    );
    
    if (database::num_rows($products_query)) {
      if ($_GET['page'] > 1) database::seek($products_query, $items_per_page * ($_GET['page'] - 1));
      
      $page_items = 0;
      while ($listing_product = database::fetch($products_query)) {
        switch($category->list_style) {
          case 'rows':
            $listing_product['listing_type'] = 'row';
            $page->snippets['products'][] = $listing_product;
            break;
          default:
          case 'columns':
            $listing_product['listing_type'] = 'column';
            $page->snippets['products'][] = $listing_product;
            break;
        }
        if (++$page_items == $items_per_page) break;
      }
      
    }
    
    $page->snippets['pagination'] = functions::draw_pagination(ceil(database::num_rows($products_query)/$items_per_page));
    
    echo $page->stitch('views/box_category');
    
    cache::end_capture($box_category_cache_id);
  }
  
?>