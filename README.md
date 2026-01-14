# Fictioneer Child Theme

A blank WordPress child theme for [Fictioneer](https://github.com/Tetrakern/fictioneer/).

## Action & Filter Examples

You can modify the Fictioneer parent theme quite a lot with [actions](https://github.com/Tetrakern/fictioneer/blob/main/ACTIONS.md) and [filters](https://github.com/Tetrakern/fictioneer/blob/main/FILTERS.md). To keep things in one place as much as possible, most code snippets have been moved to the Fictioneer main repository under [Customize](https://github.com/Tetrakern/fictioneer/blob/main/CUSTOMIZE.md). You are expected to know the basics of CSS, HTML, and PHP or consult one of the many free tutorials on the matter just a quick Internet search away.

### Example: Scope the Blog shortcode to specific roles

This goes into your child theme’s `functions.php`.

```php
function child_scope_blog_shortcode_to_roles( $query_args ) {
  # Optional: Only apply the filter to a specific page, etc.
  if ( ! is_page( 'your-page-slug' ) ) {
    return $query_args;
  }

  # Step 1: Get users with the specified role(s)
  $users = get_users( array( 'role__in' => ['author'] ) );

  # Step 2: Extract the user IDs
  $user_ids = wp_list_pluck( $users, 'ID' );

  # Step 3: Modify query arguments
  $query_args['author__in'] = $user_ids;

  # Step 4: Return modified query arguments
  return $query_args;
}
add_filter( 'fictioneer_filter_shortcode_blog_query_args', 'child_scope_blog_shortcode_to_roles', 10 );
```

### Example: Removing Actions & Filters

In order to remove actions from the parent theme, best use the `'wp'` (priority 11+) hook to ensure you remove them *after* they have been added (otherwise it will not work). Filters are similar, although some cases require more specific hooks, but `'init'` or `'wp'` should generally work too. More on [removing actions](https://developer.wordpress.org/reference/functions/remove_action/) and [removing filters](https://developer.wordpress.org/reference/functions/remove_filter/) can be found in the official WordPress documentation.

This goes into your child theme’s `functions.php`.

```php
function child_remove_scheduled_chapter() {
  // Removes "Next Chapter" schedule note above chapter lists
  remove_action( 'fictioneer_story_after_content', 'fictioneer_story_scheduled_chapter', 41 );

  // Removes ellipsis added to "Read more" links on excerpts
  remove_filter( 'excerpt_more', 'fictioneer_excerpt_ellipsis' );
}
add_action( 'wp', 'child_remove_scheduled_chapter', 11 ); // This is added on 'wp' with priority 10, so you need to be later
```
