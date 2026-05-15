<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\LocaleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LocaleSubscriberTest extends TestCase
{
    private LocaleSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new LocaleSubscriber();
    }

    private function requestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }

    // ── onKernelRequest ───────────────────────────────────────────────────────

    public function testDoesNothingWithoutPreviousSession(): void
    {
        $request = Request::create('/');
        $this->subscriber->onKernelRequest($this->requestEvent($request));
        $this->assertSame('en', $request->getLocale());
    }

    public function testSetsLocaleFromSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('_locale', 'fr');

        $request = Request::create('/');
        $request->setSession($session);
        $request->cookies->set($session->getName(), 'fake-session-id');

        $this->subscriber->onKernelRequest($this->requestEvent($request));

        $this->assertSame('fr', $request->getLocale());
    }

    public function testDoesNotChangeLocaleWhenSessionHasNoLocale(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $request = Request::create('/');
        $request->setSession($session);
        $request->cookies->set($session->getName(), 'fake-session-id');

        $this->subscriber->onKernelRequest($this->requestEvent($request));

        $this->assertSame('en', $request->getLocale());
    }

    // ── onLoginSuccess ────────────────────────────────────────────────────────

    public function testSetsSessionLocaleForUserEntityOnLogin(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = Request::create('/login');
        $request->setSession($session);

        $user  = (new User())->setLocale('fr');
        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getUser')->willReturn($user);
        $event->method('getRequest')->willReturn($request);

        $this->subscriber->onLoginSuccess($event);

        $this->assertSame('fr', $session->get('_locale'));
    }

    public function testDoesNotSetLocaleForNonUserEntity(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = Request::create('/login');
        $request->setSession($session);

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->method('getUser')->willReturn($this->createMock(UserInterface::class));
        $event->method('getRequest')->willReturn($request);

        $this->subscriber->onLoginSuccess($event);

        $this->assertNull($session->get('_locale'));
    }
}
