<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Passkey（WebAuthn）の検証をまとめたもの
 *
 * 登録・ID/PW 後の 2 段階目・Passkey のみのログインのすべてがここを通る。
 * 例外は外に出さず、失敗は結果オブジェクト（登録は false）で返す。
 */
class Users_PasskeyVerifier_Model
{
    private const RP_NAME = 'F-RevoCRM';

    /** 開発環境（http://localhost）で HTTPS なしでも動かすための例外 */
    private const SECURED_RP_IDS = ['localhost'];

    /** 認証時のアルゴリズムマネージャ（ライブラリ既定の ES256 / RS256）で検証できるものだけを許可する */
    private const CREDENTIAL_ALGORITHMS = [-7, -257]; // ES256, RS256

    private readonly Users_PublicKeyCredentialSourceRepository_Model $repository;

    private ?SerializerInterface $serializer = null;

    public function __construct(?Users_PublicKeyCredentialSourceRepository_Model $repository = null)
    {
        $this->repository = $repository ?? new Users_PublicKeyCredentialSourceRepository_Model();
    }

    /**
     * @param string       $credentialJson navigator.credentials.get() の結果の JSON
     * @param string|false $challenge      サーバーが発行したチャレンジ（生のバイト列）。無ければ false
     * @param int|null     $expectedUserId 2 段階目ではセッションのユーザー。null なら全ユーザーから持ち主を探す
     */
    public function verifyAssertion(
        string $credentialJson,
        string|false $challenge,
        string $rpId,
        ?int $expectedUserId
    ): Users_PasskeyAssertionResult_Model {
        if ($challenge === false || $challenge === '') {
            return $this->fail(null, 'challenge_missing');
        }
        $credential = $this->parseCredential($credentialJson);
        if ($credential === null || !$credential->response instanceof AuthenticatorAssertionResponse) {
            return $this->fail(null, 'invalid_credential');
        }

        $rows = $this->repository->findAllByCredentialId($credential->rawId);
        if ($rows === []) {
            return $this->fail(null, 'unknown_credential');
        }
        if (count($rows) > 1) {
            // 同じ credential ID が複数行ある。どれかを選ぶと別人として通り得るため、持ち主を特定せず拒否する
            $this->logError('Duplicate passkey credential ID (' . count($rows) . ' rows)');

            return $this->fail(null, 'duplicate_credential');
        }
        $ownerUserId = $rows[0]['userId'];
        $source = $rows[0]['source'];
        if ($expectedUserId !== null && $ownerUserId !== $expectedUserId) {
            return $this->fail($ownerUserId, 'not_owner');
        }
        if ($source === null) {
            return $this->fail($ownerUserId, 'invalid_stored_credential');
        }
        if (!hash_equals((string) $ownerUserId, $source->userHandle)) {
            // 保存されている userHandle が行の持ち主と違う。検証に使う鍵と持ち主を結び付けられないので拒否する
            return $this->fail($ownerUserId, 'owner_mismatch');
        }

        try {
            $options = PublicKeyCredentialRequestOptions::create(
                $challenge,
                $rpId,
                [],
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED
            );
            $verified = AuthenticatorAssertionResponseValidator::create($this->ceremonyFactory()->requestCeremony())
                ->check(
                    $source,
                    $credential->response,
                    $options,
                    $rpId,
                    $expectedUserId === null ? null : (string) $expectedUserId
                );
        } catch (Throwable $e) {
            $this->logError('Passkey assertion verification failed', $e);

            return $this->fail($ownerUserId, 'verification_failed');
        }

        try {
            $this->repository->saveCredentialSource($verified);
        } catch (Throwable $e) {
            // 検証は通っているのでログインは止めない
            $this->logError('Failed to save passkey counter', $e);
        }

        return new Users_PasskeyAssertionResult_Model(true, $ownerUserId, 'ok');
    }

    /**
     * @param string       $credentialJson navigator.credentials.create() の結果の JSON
     * @param string|false $challenge      サーバーが発行したチャレンジ（生のバイト列）。無ければ false
     */
    public function verifyRegistration(
        string $credentialJson,
        string|false $challenge,
        string $rpId,
        int $userId,
        string $username
    ): CredentialRecord|false {
        if ($challenge === false || $challenge === '') {
            return false;
        }
        $credential = $this->parseCredential($credentialJson);
        if ($credential === null || !$credential->response instanceof AuthenticatorAttestationResponse) {
            return false;
        }

        if ($this->repository->findAllByCredentialId($credential->rawId) !== []) {
            // 他のユーザーを含め、既に登録済みの credential ID は受け付けない（WebAuthn §7.1 step 22）
            $this->logError('Passkey credential ID is already registered');

            return false;
        }

        try {
            $excludeCredentials = array_map(
                static fn (CredentialRecord $source) => $source->getPublicKeyCredentialDescriptor(),
                $this->repository->findAllForUserId($userId)
            );
            $options = PublicKeyCredentialCreationOptions::create(
                PublicKeyCredentialRpEntity::create(self::RP_NAME, $rpId),
                PublicKeyCredentialUserEntity::create($username, (string) $userId, $username),
                $challenge,
                array_map(
                    static fn (int $alg) => PublicKeyCredentialParameters::createPk($alg),
                    self::CREDENTIAL_ALGORITHMS
                ),
                AuthenticatorSelectionCriteria::create(
                    AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
                    AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                    AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
                ),
                PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
                $excludeCredentials
            );

            return AuthenticatorAttestationResponseValidator::create($this->ceremonyFactory()->creationCeremony())
                ->check($credential->response, $options, $rpId);
        } catch (Throwable $e) {
            $this->logError('Passkey registration verification failed', $e);

            return false;
        }
    }

    private function ceremonyFactory(): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        $factory->setSecuredRelyingPartyId(self::SECURED_RP_IDS);
        $factory->setCounterChecker(new Users_PasskeyCounterChecker_Model());
        $factory->setAttestationStatementSupportManager($this->attestationStatementSupportManager());

        return $factory;
    }

    private function attestationStatementSupportManager(): AttestationStatementSupportManager
    {
        return AttestationStatementSupportManager::create([NoneAttestationStatementSupport::create()]);
    }

    /**
     * ブラウザから届いた JSON を PublicKeyCredential に読み込む。読めなければ null。
     *
     * 登録画面の JS は rawId などを btoa()（標準 base64・パディングあり）で送るため、
     * ライブラリが期待する base64url（パディングなし）にそろえてから読み込む。
     */
    private function parseCredential(string $credentialJson): ?PublicKeyCredential
    {
        try {
            $data = json_decode($credentialJson, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                return null;
            }
            $data = $this->normalizeBase64Fields($data);
            $this->serializer ??= (new WebauthnSerializerFactory($this->attestationStatementSupportManager()))->create();
            return $this->serializer->deserialize(
                json_encode($data, JSON_THROW_ON_ERROR),
                PublicKeyCredential::class,
                'json'
            );
        } catch (Throwable $e) {
            $this->logError('Invalid passkey credential JSON', $e);

            return null;
        }
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function normalizeBase64Fields(array $data): array
    {
        foreach (['id', 'rawId'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = self::toBase64Url($data[$key]);
            }
        }
        if (isset($data['response']) && is_array($data['response'])) {
            foreach (['clientDataJSON', 'attestationObject', 'authenticatorData', 'signature', 'userHandle'] as $key) {
                if (isset($data['response'][$key]) && is_string($data['response'][$key])) {
                    $data['response'][$key] = self::toBase64Url($data['response'][$key]);
                }
            }
        }

        return $data;
    }

    private static function toBase64Url(string $value): string
    {
        return rtrim(strtr($value, '+/', '-_'), '=');
    }

    private function fail(?int $userId, string $reason): Users_PasskeyAssertionResult_Model
    {
        return new Users_PasskeyAssertionResult_Model(false, $userId, $reason);
    }

    private function logError(string $message, ?Throwable $e = null): void
    {
        global $log;
        if ($log instanceof Logger) {
            $log->error($e === null ? $message : $message . ': ' . get_class($e) . ': ' . $e->getMessage());
        }
    }
}
