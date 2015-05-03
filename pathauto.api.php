<?php
use Drupal\Core\Language\Language;

/**
 * @file
 * Documentation for pathauto API.
 *
 * @see hook_token_info
 * @see hook_tokens
 */

/**
 * Alter pathauto alias type definitions.
 *
 * @param array &$definitions
 *   Alias type definitions.
 *
 */
function hook_path_alias_types_alter(array &$definitions) {
}

/**
 * Determine if a possible URL alias would conflict with any existing paths.
 *
 * Returning TRUE from this function will trigger pathauto_alias_uniquify() to
 * generate a similar URL alias with a suffix to avoid conflicts.
 *
 * @param string $alias
 *   The potential URL alias.
 * @param string $source
 *   The source path for the alias (e.g. 'node/1').
 * @param string $langcode
 *   The language code for the alias (e.g. 'en').
 *
 * @return
 *   TRUE if $alias conflicts with an existing, reserved path, or FALSE/NULL if
 *   it does not match any reserved paths.
 *
 * @see pathauto_alias_uniquify()
 */
function hook_pathauto_is_alias_reserved($alias, $source, $langcode) {
  // Check our module's list of paths and return TRUE if $alias matches any of
  // them.
  return (bool) db_query("SELECT 1 FROM {mytable} WHERE path = :path", array(':path' => $alias))->fetchField();
}

/**
 * Alter the pattern to be used before an alias is generated by Pathauto.
 *
 * @param string $pattern
 *   The alias pattern for Pathauto to pass to token_replace() to generate the
 *   URL alias.
 * @param array $context
 *   An associative array of additional options, with the following elements:
 *   - 'module': The module or entity type being aliased.
 *   - 'op': A string with the operation being performed on the object being
 *     aliased. Can be either 'insert', 'update', 'return', or 'bulkupdate'.
 *   - 'source': A string of the source path for the alias (e.g. 'node/1').
 *   - 'data': An array of keyed objects to pass to token_replace().
 *   - 'type': The sub-type or bundle of the object being aliased.
 *   - 'language': A string of the language code for the alias (e.g. 'en').
 *     This can be altered by reference.
 */
function hook_pathauto_pattern_alter(&$pattern, array &$context) {
  // Switch out any [node:created:*] tokens with [node:updated:*] on update.
  if ($context['module'] == 'node' && ($context['op'] == 'update')) {
    $pattern = preg_replace('/\[node:created(\:[^]]*)?\]/', '[node:updated$1]', $pattern);
  }
}

/**
 * Alter Pathauto-generated aliases before saving.
 *
 * @param string $alias
 *   The automatic alias after token replacement and strings cleaned.
 * @param array $context
 *   An associative array of additional options, with the following elements:
 *   - 'module': The module or entity type being aliased.
 *   - 'op': A string with the operation being performed on the object being
 *     aliased. Can be either 'insert', 'update', 'return', or 'bulkupdate'.
 *   - 'source': A string of the source path for the alias (e.g. 'node/1').
 *     This can be altered by reference.
 *   - 'data': An array of keyed objects to pass to token_replace().
 *   - 'type': The sub-type or bundle of the object being aliased.
 *   - 'language': A string of the language code for the alias (e.g. 'en').
 *     This can be altered by reference.
 *   - 'pattern': A string of the pattern used for aliasing the object.
 */
function hook_pathauto_alias_alter(&$alias, array &$context) {
  // Add a suffix so that all aliases get saved as 'content/my-title.html'
  $alias .= '.html';

  // Force all aliases to be saved as language neutral.
  $context['language'] = Language::LANGCODE_NOT_SPECIFIED;
}

/**
 * Alter the list of punctuation characters for Pathauto control.
 *
 * @param $punctuation
 *   An array of punctuation to be controlled by Pathauto during replacement
 *   keyed by punctuation name. Each punctuation record should be an array
 *   with the following key/value pairs:
 *   - value: The raw value of the punctuation mark.
 *   - name: The human-readable name of the punctuation mark. This must be
 *     translated using t() already.
 */
function hook_pathauto_punctuation_chars_alter(array &$punctuation) {
  // Add the trademark symbol.
  $punctuation['trademark'] = array('value' => '™', 'name' => t('Trademark symbol'));

  // Remove the dollar sign.
  unset($punctuation['dollar']);
}
