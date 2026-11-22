<?php

class AuthToken {

    private const TOKEN_LIFETIME = 604800;

    private static function secret(): string {
        $secret = getenv('APP_AUTH_SECRET');
        if ($secret === false || strlen($secret) < 32) {
            throw new RuntimeException('APP_AUTH_SECRET must be configured with at least 32 characters.');
        }

        return $secret;
    }

    private static function encode(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function decode(string $value) {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }

    public static function issue(int $userId, string $role): string {
        if ($userId < 1 || !in_array($role, ['pwd', 'company'], true)) {
            throw new InvalidArgumentException('Cannot issue a token for an invalid user.');
        }

        $payload = json_encode([
            'sub' => $userId,
            'role' => $role,
            'exp' => time() + self::TOKEN_LIFETIME
        ]);

        if ($payload === false) {
            throw new RuntimeException('Unable to create authentication token.');
        }

        $encodedPayload = self::encode($payload);
        $signature = hash_hmac('sha256', $encodedPayload, self::secret(), true);

        return $encodedPayload . '.' . self::encode($signature);
    }

    public static function authenticate(): ?array {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if ($authorization === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    $authorization = $value;
                    break;
                }
            }
        }

        if (!preg_match('/^Bearer\s+([A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)$/i', trim($authorization), $matches)) {
            return null;
        }

        $parts = explode('.', $matches[1], 2);
        $payloadJson = self::decode($parts[0]);
        $providedSignature = self::decode($parts[1]);
        if ($payloadJson === null || $providedSignature === null) {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $parts[0], self::secret(), true);
        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (
            !is_array($payload) ||
            !isset($payload['sub'], $payload['role'], $payload['exp']) ||
            !is_numeric($payload['sub']) ||
            (int)$payload['sub'] < 1 ||
            !in_array($payload['role'], ['pwd', 'company'], true) ||
            !is_numeric($payload['exp']) ||
            (int)$payload['exp'] <= time()
        ) {
            return null;
        }

        return [
            'user_id' => (int)$payload['sub'],
            'role' => $payload['role']
        ];
    }
}
?>
