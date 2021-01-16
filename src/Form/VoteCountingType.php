<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class VoteCountingType extends KeyCheckType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe de déchiffrement',
                'mapped' => false,
                'required' => true,
                'attr' => ['placeholder' => "Mot de passe donné par le responsable de l'intégrité"],
            ])
        ;
    }
}
