<?php

namespace App\Http;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Resolves the active profile from the `X-Profile-Id` request header.
 *
 * Not an authentication mechanism (no accounts in the MVP): it only scopes
 * what the front displays to the profile the user picked.
 */
final class ActiveProfileResolver
{
    public const HEADER = 'X-Profile-Id';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProfileRepository $profileRepository,
    ) {
    }

    public function getActiveProfile(): Profile
    {
        $rawId = $this->requestStack->getCurrentRequest()?->headers->get(self::HEADER);

        if (null === $rawId || '' === $rawId) {
            throw new BadRequestHttpException(sprintf('Missing %s header.', self::HEADER));
        }

        $profile = ctype_digit($rawId) ? $this->profileRepository->find((int) $rawId) : null;

        if (null === $profile) {
            throw new BadRequestHttpException(sprintf('Invalid %s header: no profile with id "%s".', self::HEADER, $rawId));
        }

        return $profile;
    }
}
