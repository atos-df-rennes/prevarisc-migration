<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

class HighlightExtension
{
    /**
     * Entoure les occurrences des mots-clés dans le sujet HTML avec une balise <span class="highlight">.
     * Le sujet doit être préalablement échappé en HTML. La casse du texte d'origine est préservée
     * et les balises HTML existantes ne sont pas altérées.
     *
     * @param string[] $keywords
     */
    #[AsTwigFilter(name: 'highlight', isSafe: ['html'])]
    public function highlight(string $subject, array $keywords): string
    {
        foreach ($keywords as $keyword) {
            $keyword = (string) $keyword;

            if ('' === $keyword) {
                continue;
            }

            $quotedKeyword = preg_quote($keyword, '/');
            $subject = (string) preg_replace_callback(
                '/(<[^>]*>)|('.$quotedKeyword.')/iu',
                static function (array $matches): string {
                    // Preserve HTML tags unchanged
                    if ('' !== $matches[1]) {
                        return $matches[1];
                    }

                    // Wrap matched text preserving the original case from the subject
                    return '<span class="highlight">'.$matches[2].'</span>';
                },
                $subject
            );
        }

        return $subject;
    }
}
