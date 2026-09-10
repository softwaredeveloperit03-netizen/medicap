import { Component } from '@angular/core';

@Component({
  selector: 'app-eq-ich-hub',
  templateUrl: './hub.component.html',
  styleUrls: ['./hub.component.css'],
})
export class HubComponent {
  stages = [
    { title: 'URS', desc: 'User Requirement Specification', route: '/engineering/equip-qualification/urs', icon: 'user' },
    { title: 'DQ', desc: 'Design Qualification', route: '/engineering/equip-qualification/dq', icon: 'pencil' },
    { title: 'FAT', desc: 'Factory Acceptance Test', route: '/engineering/equip-qualification/factory', icon: 'building' },
    { title: 'SAT', desc: 'Site Acceptance Test', route: '/engineering/equip-qualification/site', icon: 'home' },
    { title: 'IQ', desc: 'Installation Qualification', route: '/engineering/equip-qualification/iq', icon: 'wrench' },
    { title: 'OQ', desc: 'Operational Qualification', route: '/engineering/equip-qualification/oq', icon: 'cog' },
    { title: 'PQ', desc: 'Performance Qualification', route: '/engineering/equip-qualification/pq', icon: 'bar-chart' },
    { title: 'Requalification', desc: 'Periodic / Change-based requalification', route: '/engineering/equip-qualification/requalification', icon: 'refresh' },
  ];
}
