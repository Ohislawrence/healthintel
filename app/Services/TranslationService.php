<?php

namespace App\Services;

class TranslationService
{
    /**
     * Available languages for lab result interpretation.
     */
    public static function availableLanguages(): array
    {
        return config('languages.available', [
            'en' => ['label' => 'English', 'native' => 'English'],
            'pcm' => ['label' => 'Nigerian Pidgin', 'native' => 'Pidgin English'],
            'yo' => ['label' => 'Yoruba', 'native' => 'Yorùbá'],
            'ha' => ['label' => 'Hausa', 'native' => 'Hausa'],
            'ig' => ['label' => 'Igbo', 'native' => 'Igbo'],
        ]);
    }

    /**
     * Get the language-specific instruction to append to the system prompt.
     */
    public static function languageInstruction(string $langCode): string
    {
        $instructions = [
            'en' => 'Provide the interpretation in English.',
            'pcm' => <<<'TXT'
Provide the interpretation in Nigerian Pidgin English.
Use simple, everyday Pidgin vocabulary. Examples of Pidgin style:
- "Dis result show say..." (This result shows that...)
- "E no too bad" (It's not too serious)
- "Make you go see doctor" (You should see a doctor)
- "Di normal range na..." (The normal range is...)
Explain medical terms in simple Pidgin words in parentheses.
TXT,
            'yo' => <<<'TXT'
Provide the interpretation in Yoruba language.
Use simple, everyday Yoruba vocabulary that a non-medical person would understand.
Examples of Yoruba style:
- "Èsì ìdánwò yìí fi hàn pé..." (This test result shows that...)
- "Kò burú púpọ̀" (It's not too serious)
- "Ẹ jọ̀wọ́ ẹ lọ bá dókítà yín sọ̀rọ̀" (Please go talk to your doctor)
Explain any medical terms in simple Yoruba words in parentheses.
TXT,
            'ha' => <<<'TXT'
Provide the interpretation in Hausa language.
Use simple, everyday Hausa vocabulary that a non-medical person would understand.
Explain any medical terms in simple Hausa words in parentheses.
TXT,
            'ig' => <<<'TXT'
Provide the interpretation in Igbo language.
Use simple, everyday Igbo vocabulary that a non-medical person would understand.
Explain any medical terms in simple Igbo words in parentheses.
TXT,
        ];

        return $instructions[$langCode] ?? $instructions['en'];
    }

    /**
     * Build a multilingual-adapted system prompt by appending language instructions.
     */
    public static function multilingualSystemPrompt(string $basePrompt, string $langCode): string
    {
        if ($langCode === 'en') {
            return $basePrompt;
        }

        $langInstruction = static::languageInstruction($langCode);
        $languageName = static::availableLanguages()[$langCode]['label'] ?? $langCode;

        return $basePrompt . "\n\n" . <<<TXT
LANGUAGE INSTRUCTION:
{$langInstruction}

IMPORTANT: Your ENTIRE response must be in {$languageName}. Do NOT mix English with {$languageName}.
Use the EXACT same format (## sections, bullet points, etc.) but in {$languageName}.
Keep the emoji markers (⚠️, 🔸, ✅, 💡, 📋, ℹ️) as they are.
TXT;
    }
}