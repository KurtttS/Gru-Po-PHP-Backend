<?php

/**
 * TtsModel
 * Database work for the Text To Speech "Quick Phrases" feature.
 *
 * - Suggested phrases: kept in this file (SUGGESTED_PHRASES). Every user sees them,
 *   nobody can edit or delete them. They are NOT stored in the database.
 * - User phrases: stored in the existing `tts_phrases` table
 *   (id, user_id, category, label, phrase_text, sort_order).
 */
class TtsModel {

    private $pdo;

    // Categories shown in the app (must match TtsActivity.java and tts_update.sql)
    public const CATEGORIES = [
        'About Me',
        'Casual',
        'Yes/No',
        'Quick Sentences',
        'Convenience',
        'Question Response'
    ];

    public const MAX_PHRASE_LENGTH = 255;

    public const SUGGESTED_PHRASES = [
        'About Me' => [
            'Hello, my name is ____. Nice to meet you.',
            'I use this app to speak for me. Please give me a moment to type.',
            'I can understand you. I will answer using this app.',
            'I am applying for this job and I am ready to work.',
            'I have experience in this kind of work.'
        ],
        'Casual' => [
            'Good morning!',
            'Good afternoon!',
            'How are you today?',
            'Thank you very much.',
            'Have a nice day!',
            'See you tomorrow.'
        ],
        'Yes/No' => [
            'Yes.',
            'No.',
            'Yes, thank you.',
            'No, thank you.',
            'Maybe, let me think about it.',
            'I am not sure.'
        ],
        'Quick Sentences' => [
            'Please wait a moment.',
            'Can you repeat that, please?',
            'Please speak slowly.',
            'Can you write it down for me?',
            'I understand.',
            'I do not understand.'
        ],
        'Convenience' => [
            'Where is the restroom?',
            'I need help, please.',
            'Can I take a short break?',
            'Where should I go?',
            'Please call me if you need me.'
        ],
        'Question Response' => [
            'I am available to start right away.',
            'I can work on weekdays and weekends.',
            'My strength is that I am hardworking and willing to learn.',
            'I will send my documents as soon as possible.',
            'Do you have any questions for me?'
        ]
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ============================== READ ==============================

    /** Suggested phrases first, then the user's own phrases. */
    public function fetchPhrases(int $userId, string $category): array {
        $phrases = [];

        foreach (self::SUGGESTED_PHRASES[$category] ?? [] as $text) {
            $phrases[] = [
                'id' => 0,
                'category' => $category,
                'phrase' => $text,
                'is_default' => true
            ];
        }

        if ($userId > 0) {
            foreach ($this->fetchUserPhrases($userId, $category) as $row) {
                $phrases[] = $row;
            }
        }

        return $phrases;
    }

    public function fetchUserPhrases(int $userId, string $category): array {
        $stmt = $this->pdo->prepare(
            "SELECT id, category, phrase_text
             FROM tts_phrases
             WHERE user_id = :user_id AND category = :category
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([
            'user_id' => $userId,
            'category' => $category
        ]);

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = $this->formatRow($row);
        }
        return $rows;
    }

    public function fetchPhraseById(int $id, int $userId): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT id, category, phrase_text
             FROM tts_phrases
             WHERE id = :id AND user_id = :user_id"
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->formatRow($row) : null;
    }

    // ============================== CREATE ==============================

    public function addPhrase(int $userId, string $category, string $phrase): ?array {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tts_phrases (user_id, category, label, phrase_text, sort_order)
             VALUES (:user_id, :category, :label, :phrase_text, :sort_order)"
        );
        $stmt->execute([
            'user_id' => $userId,
            'category' => $category,
            'label' => $this->makeLabel($phrase),
            'phrase_text' => $phrase,
            'sort_order' => $this->nextSortOrder($userId, $category)
        ]);

        return $this->fetchPhraseById((int)$this->pdo->lastInsertId(), $userId);
    }

    // ============================== UPDATE ==============================

    /** Returns false if the phrase does not exist or belongs to another user. */
    public function updatePhrase(int $id, int $userId, string $category, string $phrase): bool {
        if ($this->fetchPhraseById($id, $userId) === null) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE tts_phrases
             SET category = :category, label = :label, phrase_text = :phrase_text
             WHERE id = :id AND user_id = :user_id"
        );
        $stmt->execute([
            'category' => $category,
            'label' => $this->makeLabel($phrase),
            'phrase_text' => $phrase,
            'id' => $id,
            'user_id' => $userId
        ]);

        return true;
    }

    // ============================== DELETE ==============================

    /** Returns false if the phrase does not exist or belongs to another user. */
    public function deletePhrase(int $id, int $userId): bool {
        $stmt = $this->pdo->prepare(
            "DELETE FROM tts_phrases WHERE id = :id AND user_id = :user_id"
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

    // ============================== HELPERS ==============================

    private function nextSortOrder(int $userId, string $category): int {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order
             FROM tts_phrases
             WHERE user_id = :user_id AND category = :category"
        );
        $stmt->execute([
            'user_id' => $userId,
            'category' => $category
        ]);

        return (int)$stmt->fetchColumn();
    }

    /** `label` column is VARCHAR(100): a short version of the phrase. */
    private function makeLabel(string $phrase): string {
        return mb_strlen($phrase) > 100 ? mb_substr($phrase, 0, 97) . '...' : $phrase;
    }

    private function formatRow(array $row): array {
        return [
            'id' => (int)$row['id'],
            'category' => $row['category'],
            'phrase' => $row['phrase_text'],
            'is_default' => false
        ];
    }
}
?>
