<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class KeyCheckType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('key', FileType::class, [
                'label' => 'Votix secret key',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '5k',
                        'mimeTypes' => [
                            'text/plain',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid key file',
                    ])
                ],
            ])
        ;
    }
}
