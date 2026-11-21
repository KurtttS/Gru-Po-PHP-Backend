<?php
class ResumeModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createResume(int $userId, array $resume): int {
        try {
            $this->pdo->beginTransaction();

            if ($resume['is_primary']) {
                $this->clearPrimaryResume($userId);
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO resumes (
                    pwd_user_id,
                    title,
                    target_role,
                    summary,
                    skills_json,
                    experience_json,
                    education_json,
                    certifications,
                    accommodations_requested,
                    is_primary
                ) VALUES (
                    :user_id,
                    :title,
                    :target_role,
                    :summary,
                    :skills_json,
                    :experience_json,
                    :education_json,
                    :certifications,
                    :accommodations_requested,
                    :is_primary
                )"
            );
            $stmt->execute($this->resumeParameters($userId, $resume));
            $resumeId = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();
            return $resumeId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function fetchResumes(int $userId): array {
        $stmt = $this->pdo->prepare(
            "SELECT id, title, target_role, summary, skills_json, experience_json,
                    education_json, certifications, accommodations_requested,
                    is_primary, updated_at
             FROM resumes
             WHERE pwd_user_id = :user_id
             ORDER BY is_primary DESC, updated_at DESC, id DESC"
        );
        $stmt->execute(['user_id' => $userId]);

        return array_map([$this, 'decodeResume'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function fetchResume(int $resumeId, int $userId): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT id, title, target_role, summary, skills_json, experience_json,
                    education_json, certifications, accommodations_requested,
                    is_primary, updated_at
             FROM resumes
             WHERE id = :resume_id AND pwd_user_id = :user_id
             LIMIT 1"
        );
        $stmt->execute([
            'resume_id' => $resumeId,
            'user_id' => $userId
        ]);
        $resume = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resume ? $this->decodeResume($resume) : null;
    }

    public function updateResume(int $resumeId, int $userId, array $resume): bool {
        try {
            $this->pdo->beginTransaction();

            $check = $this->pdo->prepare(
                "SELECT id FROM resumes
                 WHERE id = :resume_id AND pwd_user_id = :user_id
                 LIMIT 1"
            );
            $check->execute([
                'resume_id' => $resumeId,
                'user_id' => $userId
            ]);
            if ($check->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }

            if ($resume['is_primary']) {
                $this->clearPrimaryResume($userId, $resumeId);
            }

            $parameters = $this->resumeParameters($userId, $resume);
            $parameters['resume_id'] = $resumeId;
            $stmt = $this->pdo->prepare(
                "UPDATE resumes
                 SET title = :title,
                     target_role = :target_role,
                     summary = :summary,
                     skills_json = :skills_json,
                     experience_json = :experience_json,
                     education_json = :education_json,
                     certifications = :certifications,
                     accommodations_requested = :accommodations_requested,
                     is_primary = :is_primary
                 WHERE id = :resume_id AND pwd_user_id = :user_id"
            );
            $stmt->execute($parameters);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function deleteResume(int $resumeId, int $userId): bool {
        $stmt = $this->pdo->prepare(
            "DELETE FROM resumes
             WHERE id = :resume_id AND pwd_user_id = :user_id"
        );
        $stmt->execute([
            'resume_id' => $resumeId,
            'user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

    private function resumeParameters(int $userId, array $resume): array {
        return [
            'user_id' => $userId,
            'title' => $resume['title'],
            'target_role' => $resume['target_role'],
            'summary' => $resume['summary'],
            'skills_json' => json_encode($resume['skills'], JSON_THROW_ON_ERROR),
            'experience_json' => json_encode($resume['experience'], JSON_THROW_ON_ERROR),
            'education_json' => json_encode($resume['education'], JSON_THROW_ON_ERROR),
            'certifications' => $resume['certifications'],
            'accommodations_requested' => $resume['accommodations_requested'],
            'is_primary' => $resume['is_primary'] ? 1 : 0
        ];
    }

    private function clearPrimaryResume(int $userId, ?int $exceptResumeId = null): void {
        $sql = "UPDATE resumes SET is_primary = 0 WHERE pwd_user_id = :user_id";
        $parameters = ['user_id' => $userId];

        if ($exceptResumeId !== null) {
            $sql .= " AND id <> :resume_id";
            $parameters['resume_id'] = $exceptResumeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);
    }

    private function decodeResume(array $resume): array {
        $resume['id'] = (int) $resume['id'];
        $resume['skills'] = json_decode($resume['skills_json'], true, 512, JSON_THROW_ON_ERROR);
        $resume['experience'] = json_decode($resume['experience_json'], true, 512, JSON_THROW_ON_ERROR);
        $resume['education'] = json_decode($resume['education_json'], true, 512, JSON_THROW_ON_ERROR);
        $resume['is_primary'] = (bool) $resume['is_primary'];
        unset($resume['skills_json'], $resume['experience_json'], $resume['education_json']);

        return $resume;
    }
}
?>