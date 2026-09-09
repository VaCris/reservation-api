<?php

namespace App\DataFixtures;

use App\Entity\Location;
use App\Entity\Permission;
use App\Entity\Resource;
use App\Entity\ResourceType;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures para datos iniciales de desarrollo
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Crear Roles
        $userRole = new Role();
        $userRole->setName('ROLE_USER')
            ->setDescription('Usuario estándar del sistema');
        $manager->persist($userRole);

        $adminRole = new Role();
        $adminRole->setName('ROLE_ADMIN')
            ->setDescription('Administrador del sistema');
        $manager->persist($adminRole);

        $managerRole = new Role();
        $managerRole->setName('ROLE_MANAGER')
            ->setDescription('Gerente de recursos');
        $manager->persist($managerRole);

        // 2. Crear Permisos
        $permissions = [
            ['name' => 'reservation.create', 'resource' => 'reservation', 'action' => 'create', 'desc' => 'Crear reservas'],
            ['name' => 'reservation.view', 'resource' => 'reservation', 'action' => 'view', 'desc' => 'Ver reservas'],
            ['name' => 'reservation.cancel', 'resource' => 'reservation', 'action' => 'cancel', 'desc' => 'Cancelar reservas'],
            ['name' => 'reservation.approve', 'resource' => 'reservation', 'action' => 'approve', 'desc' => 'Aprobar reservas'],
            ['name' => 'resource.manage', 'resource' => 'resource', 'action' => 'manage', 'desc' => 'Gestionar recursos'],
        ];

        $permissionEntities = [];
        foreach ($permissions as $perm) {
            $permission = new Permission();
            $permission->setName($perm['name'])
                ->setResource($perm['resource'])
                ->setAction($perm['action'])
                ->setDescription($perm['desc']);
            $manager->persist($permission);
            $permissionEntities[$perm['name']] = $permission;
        }

        // Asignar permisos a roles
        $userRole->addPermission($permissionEntities['reservation.create'])
                 ->addPermission($permissionEntities['reservation.view'])
                 ->addPermission($permissionEntities['reservation.cancel']);

        $managerRole->addPermission($permissionEntities['reservation.create'])
                    ->addPermission($permissionEntities['reservation.view'])
                    ->addPermission($permissionEntities['reservation.cancel'])
                    ->addPermission($permissionEntities['reservation.approve'])
                    ->addPermission($permissionEntities['resource.manage']);

        $adminRole->addPermission($permissionEntities['reservation.create'])
                  ->addPermission($permissionEntities['reservation.view'])
                  ->addPermission($permissionEntities['reservation.cancel'])
                  ->addPermission($permissionEntities['reservation.approve'])
                  ->addPermission($permissionEntities['resource.manage']);

        // 3. Crear Usuarios
        $users = [
            ['email' => 'admin@empresa.com', 'first' => 'Admin', 'last' => 'Sistema', 'phone' => '+51999000001', 'role' => $adminRole, 'pass' => 'admin123'],
            ['email' => 'manager@empresa.com', 'first' => 'Carlos', 'last' => 'Gerente', 'phone' => '+51999000002', 'role' => $managerRole, 'pass' => 'manager123'],
            ['email' => 'juan.perez@empresa.com', 'first' => 'Juan', 'last' => 'Pérez', 'phone' => '+51999000003', 'role' => $userRole, 'pass' => 'user123'],
            ['email' => 'maria.garcia@empresa.com', 'first' => 'María', 'last' => 'García', 'phone' => '+51999000004', 'role' => $userRole, 'pass' => 'user123'],
            ['email' => 'pedro.lopez@empresa.com', 'first' => 'Pedro', 'last' => 'López', 'phone' => '+51999000005', 'role' => $userRole, 'pass' => 'user123'],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email'])
                ->setFirstName($userData['first'])
                ->setLastName($userData['last'])
                ->setPhoneNumber($userData['phone'])
                ->setIsActive(true)
                ->setPassword($this->passwordHasher->hashPassword($user, $userData['pass']));
            $user->addRole($userData['role']);
            $manager->persist($user);
        }

        // 4. Crear Ubicaciones
        $locations = [
            ['name' => 'Sede Central Lima', 'address' => 'Av. Javier Prado 123', 'city' => 'Lima', 'country' => 'Perú', 'postal' => '15036'],
            ['name' => 'Oficina Miraflores', 'address' => 'Av. Larco 456', 'city' => 'Lima', 'country' => 'Perú', 'postal' => '15074'],
            ['name' => 'Oficina San Isidro', 'address' => 'Av. República de Panamá 789', 'city' => 'Lima', 'country' => 'Perú', 'postal' => '15073'],
        ];

        $locationEntities = [];
        foreach ($locations as $locData) {
            $location = new Location();
            $location->setName($locData['name'])
                ->setAddress($locData['address'])
                ->setCity($locData['city'])
                ->setCountry($locData['country'])
                ->setPostalCode($locData['postal'])
                ->setIsActive(true);
            $manager->persist($location);
            $locationEntities[] = $location;
        }

        // 5. Crear Tipos de Recursos
        $resourceTypes = [
            ['name' => 'Sala de Reuniones', 'desc' => 'Salas para reuniones y presentaciones', 'duration' => 60, 'approval' => false, 'strategy' => 'MeetingRoomStrategy'],
            ['name' => 'Sala de Conferencias', 'desc' => 'Salas grandes para eventos', 'duration' => 120, 'approval' => false, 'strategy' => 'MeetingRoomStrategy'],
            ['name' => 'Sala de Capacitación', 'desc' => 'Salas para cursos y talleres', 'duration' => 180, 'approval' => false, 'strategy' => 'MeetingRoomStrategy'],
            ['name' => 'Servidor de Producción', 'desc' => 'Servidores críticos del sistema', 'duration' => 120, 'approval' => true, 'strategy' => 'HighSecurityStrategy'],
            ['name' => 'Equipo de Video', 'desc' => 'Cámaras y equipos de grabación', 'duration' => 240, 'approval' => false, 'strategy' => 'CommonResourceStrategy'],
            ['name' => 'Vehículo Corporativo', 'desc' => 'Vehículos de la empresa', 'duration' => 480, 'approval' => true, 'strategy' => 'HighSecurityStrategy'],
        ];

        $resourceTypeEntities = [];
        foreach ($resourceTypes as $rtData) {
            $resourceType = new ResourceType();
            $resourceType->setName($rtData['name'])
                ->setDescription($rtData['desc'])
                ->setDefaultDuration($rtData['duration'])
                ->setRequiresApproval($rtData['approval'])
                ->setValidationStrategy($rtData['strategy']);
            $manager->persist($resourceType);
            $resourceTypeEntities[$rtData['name']] = $resourceType;
        }

        // 6. Crear Recursos
        $resources = [
            // Sede Central Lima
            ['name' => 'Sala Ejecutiva A', 'desc' => 'Sala con capacidad para 12 personas', 'type' => 'Sala de Reuniones', 'location' => 0, 'capacity' => 12, 'metadata' => ['equipment' => ['proyector', 'pizarra', 'video conferencia']]],
            ['name' => 'Sala Ejecutiva B', 'desc' => 'Sala con capacidad para 8 personas', 'type' => 'Sala de Reuniones', 'location' => 0, 'capacity' => 8, 'metadata' => ['equipment' => ['TV 55"', 'pizarra']]],
            ['name' => 'Auditorio Principal', 'desc' => 'Capacidad para 100 personas', 'type' => 'Sala de Conferencias', 'location' => 0, 'capacity' => 100, 'metadata' => ['equipment' => ['proyector', 'sistema de audio', 'micrófonos']]],
            ['name' => 'Sala de Capacitación 1', 'desc' => 'Sala con 30 puestos', 'type' => 'Sala de Capacitación', 'location' => 0, 'capacity' => 30, 'metadata' => ['equipment' => ['proyector', 'computadoras']]],

            // Oficina Miraflores
            ['name' => 'Sala Miraflores A', 'desc' => 'Sala pequeña para 6 personas', 'type' => 'Sala de Reuniones', 'location' => 1, 'capacity' => 6, 'metadata' => ['equipment' => ['TV', 'pizarra']]],
            ['name' => 'Sala Miraflores B', 'desc' => 'Sala mediana para 10 personas', 'type' => 'Sala de Reuniones', 'location' => 1, 'capacity' => 10, 'metadata' => ['equipment' => ['proyector']]],

            // Oficina San Isidro
            ['name' => 'Sala San Isidro', 'desc' => 'Sala premium para 8 personas', 'type' => 'Sala de Reuniones', 'location' => 2, 'capacity' => 8, 'metadata' => ['equipment' => ['smart TV', 'video conferencia']]],

            // Recursos de alta seguridad
            ['name' => 'Servidor Producción 01', 'desc' => 'Servidor principal de producción', 'type' => 'Servidor de Producción', 'location' => 0, 'capacity' => 1, 'metadata' => ['specs' => '64GB RAM, 2TB SSD']],
            ['name' => 'Servidor Producción 02', 'desc' => 'Servidor secundario de producción', 'type' => 'Servidor de Producción', 'location' => 0, 'capacity' => 1, 'metadata' => ['specs' => '32GB RAM, 1TB SSD']],

            // Equipos
            ['name' => 'Cámara Sony 4K', 'desc' => 'Cámara profesional con accesorios', 'type' => 'Equipo de Video', 'location' => 0, 'capacity' => 1, 'metadata' => ['includes' => ['trípode', 'micrófono', 'luces']]],
            ['name' => 'Camioneta Toyota', 'desc' => 'Camioneta 4x4 para 5 pasajeros', 'type' => 'Vehículo Corporativo', 'location' => 0, 'capacity' => 5, 'metadata' => ['placa' => 'ABC-123', 'año' => '2023']],
        ];

        foreach ($resources as $resData) {
            $resource = new Resource();
            $resource->setName($resData['name'])
                ->setDescription($resData['desc'])
                ->setResourceType($resourceTypeEntities[$resData['type']])
                ->setLocation($locationEntities[$resData['location']])
                ->setCapacity($resData['capacity'])
                ->setIsActive(true)
                ->setMetadata($resData['metadata']);
            $manager->persist($resource);
        }

        // 7. Configuraciones del Sistema
        $settings = [
            ['key' => 'app.name', 'value' => 'Sistema de Reservas Empresarial', 'type' => 'string', 'category' => 'general', 'public' => true],
            ['key' => 'reservation.max_advance_days', 'value' => '90', 'type' => 'integer', 'category' => 'reservations', 'public' => false],
            ['key' => 'reservation.min_duration_minutes', 'value' => '15', 'type' => 'integer', 'category' => 'reservations', 'public' => false],
            ['key' => 'reservation.max_duration_hours', 'value' => '8', 'type' => 'integer', 'category' => 'reservations', 'public' => false],
            ['key' => 'business.start_hour', 'value' => '8', 'type' => 'integer', 'category' => 'business', 'public' => true],
            ['key' => 'business.end_hour', 'value' => '19', 'type' => 'integer', 'category' => 'business', 'public' => true],
        ];

        foreach ($settings as $settingData) {
            $setting = new \App\Entity\Setting();
            $setting->setKey($settingData['key'])
                ->setValue($settingData['value'])
                ->setType($settingData['type'])
                ->setCategory($settingData['category'])
                ->setIsPublic($settingData['public']);
            $manager->persist($setting);
        }

        $manager->flush();

        echo "\n✅ Datos iniciales cargados exitosamente!\n";
        echo "📧 Usuarios creados:\n";
        echo "   - admin@empresa.com (admin123) - Administrador\n";
        echo "   - manager@empresa.com (manager123) - Gerente\n";
        echo "   - juan.perez@empresa.com (user123) - Usuario\n";
        echo "   - maria.garcia@empresa.com (user123) - Usuario\n";
        echo "   - pedro.lopez@empresa.com (user123) - Usuario\n";
    }
}