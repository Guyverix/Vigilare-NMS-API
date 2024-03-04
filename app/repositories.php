<?php
declare(strict_types=1);

// REPOSITORY
// retire after device is fully active
//use App\Domain\Host\HostRepository;

// ? get rid of this soonish
// use App\Domain\Host\TestRepository;


use App\Domain\History\HistoryRepository;
use App\Domain\EventCorrelation\EventCorrelationRepository;
use App\Domain\Event\EventRepository;
use App\Domain\Infrastructure\InfrastructureRepository;
use App\Domain\User\UserRepository;
use App\Domain\Admin\AdminRepository;
use App\Domain\Trap\TrapRepository;
use App\Domain\Device\DeviceRepository;
use App\Domain\Maintenance\MaintenanceRepository;
use App\Domain\NameMap\NameMapRepository;
use App\Domain\Snmp\SnmpRepository;
use App\Domain\Mapping\MappingRepository;
use App\Domain\GlobalMapping\GlobalMappingRepository;
use App\Domain\Discover\DiscoverRepository;
use App\Domain\Graphite\GraphiteRepository;
use App\Domain\RenderGraph\RenderGraphRepository;
use App\Domain\Poller\PollerRepository;
use App\Domain\Reporting\ReportingRepository;
use App\Infrastructure\Persistence\Reporting\DatabaseReportingRepository;
use App\Domain\Discovery\DiscoveryRepository;
use App\Domain\MonitoringPoller\MonitoringPollerRepository;
use App\Domain\Monitors\MonitorsRepository;

// DATABASES
// Likely this is going away!
use App\Infrastructure\Shared\Snmp\SharedSnmpRepository;

// retire after device is fully active
// use App\Infrastructure\Persistence\Host\DatabaseHostRepository;


use App\Infrastructure\Persistence\User\DatabaseUserRepository;
use App\Infrastructure\Persistence\Admin\DatabaseAdminRepository;
use App\Infrastructure\Persistence\EventCorrelation\DatabaseEventCorrelationRepository;
use App\Infrastructure\Persistence\Event\DatabaseEventRepository;
use App\Infrastructure\Persistence\Infrastructure\DatabaseInfrastructureRepository;
use App\Infrastructure\Persistence\History\DatabaseHistoryRepository;
use App\Infrastructure\Persistence\Trap\DatabaseTrapRepository;
use App\Infrastructure\Persistence\Maintenance\DatabaseMaintenanceRepository;
use App\Infrastructure\Persistence\NameMap\DatabaseNameMapRepository;
use App\Infrastructure\Persistence\Device\DatabaseDeviceRepository;
use App\Infrastructure\Persistence\Mapping\DatabaseMappingRepository;
use App\Infrastructure\Persistence\Discover\DatabaseDiscoverRepository;
use App\Infrastructure\Persistence\Graphite\DatabaseGraphiteRepository;
use App\Infrastructure\Persistence\GlobalMapping\DatabaseGlobalMappingRepository;
use App\Infrastructure\Persistence\SmartPoller\DatabaseSmartPollerRepository;
use App\Infrastructure\Persistence\RenderGraph\DatabaseRenderGraphRepository;
use App\Infrastructure\Persistence\Discovery\DatabaseDiscoveryRepository;
use App\Infrastructure\Persistence\MonitoringPoller\DatabaseMonitoringPollerRepository;
use App\Infrastructure\Persistence\Monitors\DatabaseMonitorsRepository;


use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    // Here we map our Repository interfaces to their database implementations
    $containerBuilder->addDefinitions([
//        HostRepository::class => \DI\autowire(DatabaseHostRepository::class),
        HistoryRepository::class => \DI\autowire(DatabaseHistoryRepository::class),
        EventRepository::class => \DI\autowire(DatabaseEventRepository::class),
        EventCorrelationRepository::class => \DI\autowire(DatabaseEventCorrelationRepository::class),
        InfrastructureRepository::class => \DI\autowire(DatabaseInfrastructureRepository::class),
        UserRepository::class => \DI\autowire(DatabaseUserRepository::class),
        AdminRepository::class => \DI\autowire(DatabaseAdminRepository::class),
        TrapRepository::class => \DI\autowire(DatabaseTrapRepository::class),
        MaintenanceRepository::class => \DI\autowire(DatabaseMaintenanceRepository::class),
        NameMapRepository::class => \DI\autowire(DatabaseNameMapRepository::class),
        SnmpRepository::class => \DI\autowire(SharedSnmpRepository::class),
        MappingRepository::class => \DI\autowire(DatabaseMappingRepository::class),
        PollerRepository::class => \DI\autowire(DatabaseSmartPollerRepository::class),
        DiscoverRepository::class => \DI\autowire(DatabaseDiscoverRepository::class),
        GraphiteRepository::class => \DI\autowire(DatabaseGraphiteRepository::class),
        GlobalMappingRepository::class => \DI\autowire(DatabaseGlobalMappingRepository::class),
        DeviceRepository::class => \DI\autowire(DatabaseDeviceRepository::class),
        MonitoringPollerRepository::class => \DI\autowire(DatabaseMonitoringPollerRepository::class),
        MonitorsRepository::class => \DI\autowire(DatabaseMonitorsRepository::class),
        DiscoveryRepository::class => \DI\autowire(DatabaseDiscoveryRepository::class),
        RenderGraphRepository::class => \DI\autowire(DatabaseRenderGraphRepository::class),
        ReportingRepository::class => \DI\autowire(DatabaseReportingRepository::class),
    ]);
};
