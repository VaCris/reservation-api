<?php

namespace App\Tests\Controller\Api;

use App\Entity\Resource;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test funcional del ReservationController
 * Cumple con ISO 27001 - Verificación de Cumplimiento
 */
class ReservationControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->passwordHasher = $container->get('security.user_password_hasher');

        // Cargar fixtures manualmente
        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        // Limpiar todas las tablas
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        $tables = [
            'reservations',
            'user_resource_permissions',
            'reservation_approvals',
            'resources',
            'availability_slots',
            'notifications',
            'audit_logs',
            'user_roles',
            'role_permissions',
            'users',
            'roles',
            'permissions',
            'resource_types',
            'locations',
            'recurring_patterns',
            'settings',
            'user_sessions'
        ];

        foreach ($tables as $table) {
            try {
                $connection->executeStatement($platform->getTruncateTableSQL($table, true));
            } catch (\Exception $e) {
                // Ignorar si la tabla no existe
            }
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        // Crear fixtures directamente
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // 1. Crear Roles
        $userRole = new \App\Entity\Role();
        $userRole->setName('ROLE_USER')
            ->setDescription('Usuario estándar del sistema');
        $this->entityManager->persist($userRole);

        $adminRole = new \App\Entity\Role();
        $adminRole->setName('ROLE_ADMIN')
            ->setDescription('Administrador del sistema');
        $this->entityManager->persist($adminRole);

        // 2. Crear Usuarios
        $user = new User();
        $user->setEmail('user@test.com')
            ->setFirstName('Test')
            ->setLastName('User')
            ->setPhoneNumber('+1234567890')
            ->setIsActive(true)
            ->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->addRole($userRole);
        $this->entityManager->persist($user);

        $admin = new User();
        $admin->setEmail('admin@test.com')
            ->setFirstName('Admin')
            ->setLastName('User')
            ->setPhoneNumber('+0987654321')
            ->setIsActive(true)
            ->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->addRole($adminRole);
        $this->entityManager->persist($admin);

        // 3. Crear Ubicación
        $location = new \App\Entity\Location();
        $location->setName('Oficina Principal')
            ->setAddress('123 Test Street')
            ->setCity('Test City')
            ->setCountry('Test Country')
            ->setPostalCode('12345')
            ->setIsActive(true);
        $this->entityManager->persist($location);

        // 4. Crear Tipos de Recursos
        $meetingRoomType = new \App\Entity\ResourceType();
        $meetingRoomType->setName('Sala de Reuniones')
            ->setDescription('Salas para reuniones')
            ->setDefaultDuration(60)
            ->setRequiresApproval(false)
            ->setValidationStrategy('MeetingRoomStrategy');
        $this->entityManager->persist($meetingRoomType);

        $highSecurityType = new \App\Entity\ResourceType();
        $highSecurityType->setName('Recurso de Alta Seguridad')
            ->setDescription('Recursos críticos que requieren aprobación')
            ->setDefaultDuration(120)
            ->setRequiresApproval(true)
            ->setValidationStrategy('HighSecurityStrategy');
        $this->entityManager->persist($highSecurityType);

        // 5. Crear Recursos
        $resource1 = new Resource();
        $resource1->setName('Sala de Reuniones A')
            ->setDescription('Sala con capacidad para 10 personas')
            ->setResourceType($meetingRoomType)
            ->setLocation($location)
            ->setCapacity(10)
            ->setIsActive(true)
            ->setMetadata(['equipment' => ['projector', 'whiteboard']]);
        $this->entityManager->persist($resource1);

        $resource2 = new Resource();
        $resource2->setName('Servidor de Producción')
            ->setDescription('Servidor crítico de producción')
            ->setResourceType($highSecurityType)
            ->setLocation($location)
            ->setCapacity(1)
            ->setIsActive(true)
            ->setValidationStrategy('HighSecurityStrategy');
        $this->entityManager->persist($resource2);
        $this->entityManager->flush();
    }

    /**
     * Test: Crear una reserva exitosamente
     */
    public function testCreateReservationSuccess(): void
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'user@test.com']);
        $this->client->loginUser($user);

        $resource = $this->entityManager->getRepository(Resource::class)
            ->findOneBy(['name' => 'Sala de Reuniones A']);

        $tomorrow = new \DateTimeImmutable('+1 day 10:00');
        $endTime = new \DateTimeImmutable('+1 day 11:00');

        $this->client->jsonRequest('POST', '/api/reservations', [
            'resourceId' => $resource->getId(),
            'startTime' => $tomorrow->format(\DateTimeInterface::ATOM),
            'endTime' => $endTime->format(\DateTimeInterface::ATOM),
            'notes' => 'Reunión de equipo'
        ]);

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('confirmation_code', $data['data']);
        $this->assertEquals('pending', $data['data']['status']);
    }

    /**
     * Test: No se puede crear reserva sin autenticación
     */
    public function testCreateReservationUnauthorized(): void
    {
        $resource = $this->entityManager->getRepository(Resource::class)
            ->findOneBy(['name' => 'Sala de Reuniones A']);

        $tomorrow = new \DateTimeImmutable('+1 day 10:00');
        $endTime = new \DateTimeImmutable('+1 day 11:00');

        $this->client->jsonRequest('POST', '/api/reservations', [
            'resourceId' => $resource->getId(),
            'startTime' => $tomorrow->format(\DateTimeInterface::ATOM),
            'endTime' => $endTime->format(\DateTimeInterface::ATOM)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test: Validar que no se puede reservar en el pasado
     */
    public function testCreateReservationInPastFails(): void
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'user@test.com']);
        $this->client->loginUser($user);

        $resource = $this->entityManager->getRepository(Resource::class)
            ->findOneBy(['name' => 'Sala de Reuniones A']);

        $yesterday = new \DateTimeImmutable('-1 day 10:00');
        $endTime = new \DateTimeImmutable('-1 day 11:00');

        $this->client->jsonRequest('POST', '/api/reservations', [
            'resourceId' => $resource->getId(),
            'startTime' => $yesterday->format(\DateTimeInterface::ATOM),
            'endTime' => $endTime->format(\DateTimeInterface::ATOM)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('pasado', $data['error']);
    }

    /**
     * Test: Validar conflicto de horarios
     */
    public function testCreateReservationConflict(): void
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'user@test.com']);
        $this->client->loginUser($user);

        $resource = $this->entityManager->getRepository(Resource::class)
            ->findOneBy(['name' => 'Sala de Reuniones A']);

        $tomorrow = new \DateTimeImmutable('+1 day 14:00');
        $endTime = new \DateTimeImmutable('+1 day 15:00');

        // Primera reserva
        $this->client->jsonRequest('POST', '/api/reservations', [
            'resourceId' => $resource->getId(),
            'startTime' => $tomorrow->format(\DateTimeInterface::ATOM),
            'endTime' => $endTime->format(\DateTimeInterface::ATOM)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Intentar crear reserva en el mismo horario (conflicto)
        $this->client->jsonRequest('POST', '/api/reservations', [
            'resourceId' => $resource->getId(),
            'startTime' => $tomorrow->format(\DateTimeInterface::ATOM),
            'endTime' => $endTime->format(\DateTimeInterface::ATOM)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('disponible', $data['error']);
    }

    /**
     * Test: Listar reservas del usuario
     */
    public function testListUserReservations(): void
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'user@test.com']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/api/reservations');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertIsArray($data['data']);
    }

    /**
     * Test: Verificar disponibilidad
     */
    public function testCheckAvailability(): void
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'user@test.com']);
        $this->client->loginUser($user);

        $resource = $this->entityManager->getRepository(Resource::class)
            ->findOneBy(['name' => 'Sala de Reuniones A']);

        $tomorrow = new \DateTimeImmutable('+1 day 16:00');
        $endTime = new \DateTimeImmutable('+1 day 17:00');

        $this->client->request('GET', sprintf(
            '/api/reservations/availability/%d?start_time=%s&end_time=%s',
            $resource->getId(),
            urlencode($tomorrow->format(\DateTimeInterface::ATOM)),
            urlencode($endTime->format(\DateTimeInterface::ATOM))
        ));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('available', $data);
        $this->assertTrue($data['available']);
    }

    /**
     * Test: Cancelar reserva
     */
    public function testCancelReservation(): void
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'user@test.com']);
        $this->client->loginUser($user);

        $resource = $this->entityManager->getRepository(Resource::class)
            ->findOneBy(['name' => 'Sala de Reuniones A']);

        // Crear reserva
        $tomorrow = new \DateTimeImmutable('+1 day 09:00');
        $endTime = new \DateTimeImmutable('+1 day 10:00');

        $this->client->jsonRequest('POST', '/api/reservations', [
            'resourceId' => $resource->getId(),
            'startTime' => $tomorrow->format(\DateTimeInterface::ATOM),
            'endTime' => $endTime->format(\DateTimeInterface::ATOM)
        ]);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $reservationId = $responseData['data']['id'];

        // Cancelar la reserva
        $this->client->request('PUT', '/api/reservations/' . $reservationId . '/cancel');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('cancelled', $data['data']['status']);
    }

    /**
     * Test: Solo admin puede confirmar reservas
     */
    public function testOnlyAdminCanConfirmReservation(): void
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'user@test.com']);
        $this->client->loginUser($user);

        $resource = $this->entityManager->getRepository(Resource::class)
            ->findOneBy(['name' => 'Sala de Reuniones A']);

        $tomorrow = new \DateTimeImmutable('+1 day 11:00');
        $endTime = new \DateTimeImmutable('+1 day 12:00');

        $this->client->jsonRequest('POST', '/api/reservations', [
            'resourceId' => $resource->getId(),
            'startTime' => $tomorrow->format(\DateTimeInterface::ATOM),
            'endTime' => $endTime->format(\DateTimeInterface::ATOM)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $reservationId = $responseData['data']['id'];

        // Verificar que tenemos un ID válido
        $this->assertNotNull($reservationId, 'Reservation ID should not be null');

        // Intentar confirmar como usuario (debe fallar)
        $this->client->request('PUT', '/api/reservations/' . $reservationId . '/confirm');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Confirmar como admin (debe funcionar)
        $admin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@test.com']);
        $this->client->loginUser($admin);

        $this->client->request('PUT', '/api/reservations/' . $reservationId . '/confirm');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('confirmed', $data['data']['status']);
    }

    /**
     * Test: Estrategia de Alta Seguridad requiere 24h de anticipación
     */
    public function testHighSecurityStrategyRequires24Hours(): void
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'user@test.com']);
        $this->client->loginUser($user);

        $resource = $this->entityManager->getRepository(Resource::class)
            ->findOneBy(['name' => 'Servidor de Producción']);

        $tomorrow = new \DateTimeImmutable('tomorrow 14:00');
        $endTime = new \DateTimeImmutable('tomorrow 15:00');

        $dayOfWeek = (int) $tomorrow->format('N');
        if ($dayOfWeek >= 6) {
            $daysToAdd = (8 - $dayOfWeek);
            $tomorrow = $tomorrow->modify("+{$daysToAdd} days");
            $endTime = $endTime->modify("+{$daysToAdd} days");
        }

        $this->client->jsonRequest('POST', '/api/reservations', [
            'resourceId' => $resource->getId(),
            'startTime' => $tomorrow->format(\DateTimeInterface::ATOM),
            'endTime' => $endTime->format(\DateTimeInterface::ATOM)
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('24 horas', $data['error']);
    }
}