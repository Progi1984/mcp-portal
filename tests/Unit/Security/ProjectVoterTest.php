<?php

namespace App\Tests\Unit\Security;

use App\Entity\Project;
use App\Entity\User;
use App\Security\Voter\ProjectVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProjectVoterTest extends TestCase
{
    private ProjectVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new ProjectVoter();
    }

    private function token(mixed $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $result = $this->voter->vote($this->token(new User()), new Project(), ['SOME_OTHER_ATTR']);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainsForNonProjectSubject(): void
    {
        $result = $this->voter->vote($this->token(new User()), new \stdClass(), ['PROJECT_EDIT']);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testGrantsAccessToProjectOwner(): void
    {
        $owner = new User();
        $project = (new Project())->setUser($owner);

        $result = $this->voter->vote($this->token($owner), $project, ['PROJECT_EDIT']);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeniesAccessToNonOwner(): void
    {
        $owner = new User();
        $other = new User();
        $project = (new Project())->setUser($owner);

        $result = $this->voter->vote($this->token($other), $project, ['PROJECT_EDIT']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessToUnauthenticatedUser(): void
    {
        $project = (new Project())->setUser(new User());

        $result = $this->voter->vote($this->token(null), $project, ['PROJECT_EDIT']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}
