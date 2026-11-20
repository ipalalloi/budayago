<?php
declare(strict_types=1);
final class Policy {
    public const ROLES = ['admin', 'curator', 'holder', 'teacher', 'student'];
    public const SCOPES = ['PUBLIC', 'MEMBERS', 'RESTRICTED'];
    public static function canEdit(array $u, array $knowledge): bool {
        return in_array($u['role'], ['admin', 'curator'], true)
            && ($u['role'] === 'admin' || (int)$knowledge['author_id'] === (int)$u['id'])
            && in_array($knowledge['status'], ['draft', 'rejected'], true);
    }
    public static function canReview(array $u, array $knowledge): bool {
        return $u['role'] === 'holder' && (int)$knowledge['holder_id'] === (int)$u['id'] && $knowledge['status'] === 'submitted';
    }
    public static function canWithdraw(array $u, array $knowledge): bool {
        return $u['role'] === 'holder' && (int)$knowledge['holder_id'] === (int)$u['id'] && in_array($knowledge['status'], ['approved', 'published'], true);
    }
    public static function canReadKnowledge(?array $u, array $k): bool {
        if ($k['status'] === 'published' && $k['scope'] === 'PUBLIC') { return true; }
        if (!$u) { return false; }
        if ($k['status'] === 'published' && $k['scope'] === 'MEMBERS') { return true; }
        return $u['role'] === 'admin' || (int)$k['holder_id'] === (int)$u['id']
            || ($u['role'] === 'curator' && (int)$k['author_id'] === (int)$u['id']);
    }
    public static function canPublish(array $u, array $k): bool {
        return $u['role'] === 'admin' && $k['status'] === 'approved' && $k['scope'] !== 'RESTRICTED'
            && (int)$k['allow_learning'] === 1 && ($k['scope'] !== 'PUBLIC' || (int)$k['allow_online'] === 1);
    }
    public static function canViewLesson(?array $u, array $lesson): bool {
        return $u !== null && $lesson['status'] === 'published' && $lesson['knowledge_status'] === 'published'
            && $lesson['scope'] !== 'RESTRICTED' && (int)$lesson['allow_learning'] === 1;
    }
    public static function canReadSourceReference(?array $u, array $knowledge): bool {
        if ($u === null) { return false; }
        if ($u['role'] === 'admin') { return true; }
        if ($u['role'] === 'holder') {
            return (int)$knowledge['holder_id'] === (int)$u['id'];
        }
        if ($u['role'] === 'curator') {
            return (int)$knowledge['author_id'] === (int)$u['id'];
        }
        return false;
    }
    public static function normalizeAnswer(string $answer): string { return strtolower(preg_replace('/\s+/u', ' ', trim($answer)) ?? trim($answer)); }
    public static function validEmail(string $email): bool { return strlen($email) <= 190 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
    public static function validateKnowledge(string $title, string $summary, string $body, string $sourceName, string $sourceRef, string $scope): void {
        if (strlen(trim($title)) < 4 || strlen($title) > 180 || strlen(trim($summary)) < 10 || strlen($summary) > 1000
            || strlen(trim($body)) < 20 || strlen($body) > 30000 || strlen(trim($sourceName)) < 3 || strlen($sourceName) > 190
            || strlen(trim($sourceRef)) < 4 || strlen($sourceRef) > 500 || !in_array($scope, self::SCOPES, true)) {
            throw new DomainException('Isi judul, ringkasan, isi, rujukan, dan tingkat akses sesuai batas yang ditentukan.');
        }
    }
}
