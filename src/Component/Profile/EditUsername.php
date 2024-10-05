<?php

declare(strict_types=1);

namespace Pl8r\Component\Profile;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Pl8r\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Turbo\TurboBundle;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Twig\Environment;

/**
 * @psalm-suppress PropertyNotSetInConstructor Set in mount()
 */
#[AsTwigComponent()]
final class EditUsername
{
  private FormInterface $form;

  public function __construct(
    private EntityManagerInterface $entityManager,
    private Security $security,
    private FormFactoryInterface $formFactory,
    private Environment $twig,
  ) {}

  public function mount(?FormInterface $form = null): void
  {
    $this->form = $form ?? $this->formFactory->create(EditUsernameType::class, $this->security->getUser());
  }

  public function editUsername(Request $request): Response
  {
    $this->mount();
    $this->form->handleRequest($request);
    if ($this->form->isSubmitted() && $this->form->isValid()) {
      $user = $this->form->getData();
      \assert($user instanceof User);
      $this->entityManager->persist($user);

      try {
        $this->entityManager->flush();
      } catch (UniqueConstraintViolationException $e) {
        $this->form->addError(new FormError('Username is already taken'));
      }
    }

    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

    return new Response($this->twig->load('Profile/stream.html.twig')->renderBlock(
      ($this->form->isSubmitted() && $this->form->isValid()) ? 'edit_username_success' : 'edit_username_fail',
      ['form' => $this->form],
    ));
  }

  /**
   * @psalm-suppress PossiblyUnusedMethod Used by UX Twig Components
   */
  public function getView(): FormView
  {
    return $this->form->createView();
  }
}
