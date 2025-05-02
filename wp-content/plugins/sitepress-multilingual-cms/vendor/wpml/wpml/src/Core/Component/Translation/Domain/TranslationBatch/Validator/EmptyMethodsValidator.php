<?php

namespace WPML\Core\Component\Translation\Domain\TranslationBatch\Validator;

use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;

/**
 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-3747/TM-rvmp-Sending-for-translation-failed-Batch-elements-cannot-be-empty
 *
 * It may happen that previous validators removed all elements from a language.
 * In such situation we have to remove the whole language from a batch.
 *
 * At the end, it may happen that we removed all languages from a batch. It means that eventual batch
 * is completely empty. In such situation, we return null.
 *
 * How it may happen?
 *
 * You have to have at least 6 languages so only ONE post can be included in a single chunk
 * Let's say languages are: French, German, Italian, Spanish, Portuguese, Dutch
 *
 * You select the first post which is translated automatically in some languages, let's say German and Italian.
 *
 * You select the second post which is not translated at all.
 * Now, you select automatic method for all 6 languages.
 * You choose to "leave existing translated unchanged". It means we won't create a new translations in German and Italian
 * for the first post.
 *
 * The JS validator let us proceed with that because we have the second post which is not translated at all.
 * But, our chunking does not allow us to have more than 10 jobs in one chunk where jobs are number_of_languages x number_of_posts.
 * We use "floor" function to round it. It means that we have 2 chunks: one for the first post with all 6 languages and
 * another for the second post with all 6 languages.
 *
 * When we send the first chunk, the CompletedTranslationValidator will remove German and Italian languages for the first post.
 * The problem is the first post is the only one! So, we do not have any post ids for those languages anymore.
 * This is what was detected in the ticket wpmldev-3747.
 *
 * We can extend this example even more. The first post can be automatically translated in all languages.
 * In such case, after the CompletedTranslationValidator, we will have an empty batch.
 *
 * This validator must be run always after all other validators.
 */
class EmptyMethodsValidator implements ValidatorInterface {


  /**
   * @param TranslationBatch $translationBatch
   *
   * @return array{0: TranslationBatch|null, 1: IgnoredElement[]}
   */
  public function validate( TranslationBatch $translationBatch ): array {
    $targetLanguages = [];
    foreach ( $translationBatch->getTargetLanguages() as $targetLanguage ) {
      if ( $targetLanguage->getElements() ) {
        $targetLanguages[] = $targetLanguage;
      }
    }

    if ( empty( $targetLanguages ) ) {
      return [ null, [] ];
    }

    $translationBatch = $translationBatch->copyWithNewTargetLanguages( $targetLanguages );

    return [ $translationBatch, [] ];
  }


}
