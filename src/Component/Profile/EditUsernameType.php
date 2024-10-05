<?php

declare(strict_types=1);

namespace Pl8r\Component\Profile;

use Pl8r\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<User>
 */
final class EditUsernameType extends AbstractType
{
  #[\Override]
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('username')
      ->add('submit', SubmitType::class)
    ;
  }

  #[\Override]
  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => User::class,
    ]);
  }
}
