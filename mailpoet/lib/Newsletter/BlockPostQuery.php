<?php

namespace MailPoet\Newsletter;

use DateTimeInterface;

class BlockPostQuery {
  const DEFAULT_POSTS_PER_PAGE = 10;

  /**
   * @var array $args = [
   *     amount => (int) \WP_Query::posts_per_page
   *     offset => (int)  \WP_Query::offset
   *     posts => (int[]) \WP_Query::post_in
   *     contentType => (string) \WP_Query::post_type
   *     postStatus => (string) \WP_Query::post_status
   *     search => (string) \WP_Query::s
   *     sortBy => (string) 'newest' | 'DESC' | 'ASC' \WP_Query::order
   * ]
   */
  public $args = [];

  /*** @var int[] \WP_Query::post__not_in  */
  public $postsToExclude = [];

  /** @var int|false */
  public $newsletterId = false;

  /***
   * Translates to \WP_Query::date_query => ['column' => 'post_date', 'after' => {date string}]
   *
   * @var bool|DateTimeInterface
   */
  public $newerThanTimestamp = false;

  /**
   * @param array $query = [
   *    args,
   *    postsToExclude,
   *    newsletterId,
   *    newerThanTimestamp,
   *    terms,
   *    inclusionType
   * ]
   * @return void
   */
  public function __construct(
    array $query = []
  ) {
    $this->setProperties($query);
  }

  private function setProperties(array $query): void {
    if (empty($query)) {
      return;
    }

    foreach (get_class_vars(self::class) as $propertyName => $value) {
      if (isset($query[$propertyName])) {
        $this->{$propertyName} = $query[$propertyName];
      }
    }
  }

  public function getPostType(): string {
    return (isset($this->args['contentType'])) ? $this->args['contentType'] : 'post';
  }

  /**
   * Returns post status
   *
   * @return string
   */
  public function getPostStatus(): string {
    return (isset($this->args['postStatus'])) ? $this->args['postStatus'] : 'publish';
  }

  public function getOrder(): string {
    return isset($this->args['sortBy']) && in_array($this->args['sortBy'], ['newest', 'DESC']) ? 'DESC' : 'ASC';
  }

  public function constructTaxonomiesQuery() {
    $taxonomiesQuery = [];
    if (isset($this->args['terms']) && is_array($this->args['terms'])) {
      $taxonomies = [];
      // Categorize terms based on their taxonomies
      foreach ($this->args['terms'] as $term) {
        $taxonomy = $term['taxonomy'];
        if (!isset($taxonomies[$taxonomy])) {
          $taxonomies[$taxonomy] = [];
        }
        $taxonomies[$taxonomy][] = $term['id'];
      }

      foreach ($taxonomies as $taxonomy => $terms) {
        if (!empty($terms)) {
          $tax = [
            'taxonomy' => $taxonomy,
            'field' => 'id',
            'terms' => $terms,
          ];
          if ($this->args['inclusionType'] === 'exclude') $tax['operator'] = 'NOT IN';
          $taxonomiesQuery[] = $tax;
        }
      }
      if (!empty($taxonomiesQuery)) {
        // With exclusion we want to use 'AND', because we want posts that
        // don't have excluded tags/categories. But with inclusion we want to
        // use 'OR', because we want posts that have any of the included
        // tags/categories
        $taxonomiesQuery['relation'] = ($this->args['inclusionType'] === 'exclude') ? 'AND' : 'OR';
      }
    }

    // make $taxonomies_query nested to avoid conflicts with plugins that use taxonomies
    return empty($taxonomiesQuery) ? [] : [$taxonomiesQuery];
  }

  public function getQueryParams(): array {
    $postsPerPage = (!empty($this->args['amount']) && (int)$this->args['amount'] > 0)
      ? (int)$this->args['amount']
      : self::DEFAULT_POSTS_PER_PAGE;
    $parameters = [
      'posts_per_page' => $postsPerPage,
      'post_type' => $this->getPostType(),
      'post_status' => $this->getPostStatus(),
      'orderby' => 'date',
      'order' => $this->getOrder(),
    ];
    if (!empty($this->args['offset']) && (int)$this->args['offset'] > 0) {
      $parameters['offset'] = (int)$this->args['offset'];
    }
    if (isset($this->args['search'])) {
      $parameters['s'] = $this->args['search'];
    }
    if (isset($this->args['posts']) && is_array($this->args['posts'])) {
      $parameters['post__in'] = $this->args['posts'];
      $parameters['posts_per_page'] = -1; // Get all posts with matching IDs
    }
    if (!empty($this->postsToExclude)) {
      $parameters['post__not_in'] = $this->postsToExclude;
    }

    // WP posts with the type attachment have always post_status `inherit`
    if ($parameters['post_type'] === 'attachment' && $parameters['post_status'] === 'publish') {
      $parameters['post_status'] = 'inherit';
    }

    // This enables using posts query filters for get_posts, where by default
    // it is disabled.
    // However, it also enables other plugins and themes to hook in and alter
    // the query.
    $parameters['suppress_filters'] = false;

    if ($this->newerThanTimestamp instanceof DateTimeInterface) {
      $parameters['date_query'] = [
        [
          'column' => 'post_date',
          'after' => $this->newerThanTimestamp->format('Y-m-d H:i:s'),
        ],
      ];
    }

    $parameters['tax_query'] = $this->constructTaxonomiesQuery();
    return $parameters;
  }
}
