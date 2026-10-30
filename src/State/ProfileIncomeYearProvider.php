<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ProfileIncomeYear;
use App\Http\ActiveProfileResolver;
use App\Service\ProfileIncomeYearBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `GET /profiles/{profileId}/incomes?year=`: only the active profile's own
 * year can be read, any other profile is a 404. `year` is required and
 * validated by its QueryParameter.
 *
 * @implements ProviderInterface<ProfileIncomeYear>
 */
final class ProfileIncomeYearProvider implements ProviderInterface
{
    public function __construct(
        private readonly ActiveProfileResolver $activeProfileResolver,
        private readonly ProfileIncomeYearBuilder $builder,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProfileIncomeYear
    {
        $profile = $this->activeProfileResolver->getActiveProfile();

        if ((string) $profile->getId() !== (string) $uriVariables['profileId']) {
            throw new NotFoundHttpException('Profile not found.');
        }

        $year = $operation->getParameters()?->get('year')?->getValue();

        return $this->builder->build($profile, (int) $year);
    }
}
