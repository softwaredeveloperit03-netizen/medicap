import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { readEquipmentDeptCalibrationConfig } from '../equipment-dept-calibration-context';

@Component({
  selector: 'app-equipment-dept-calibration-dashboard',
  templateUrl: './dashboard.component.html',
})
export class EquipmentDeptCalibrationDashboardComponent implements OnInit {
  cards: QcDeptCard[] = [];
  sectionLabel = 'Equipment Calibration';
  closeRoute = '/';

  constructor(private route: ActivatedRoute) {}

  ngOnInit(): void {
    const config = readEquipmentDeptCalibrationConfig(this.route);
    this.closeRoute = config.closeRoute;
    const dept = config.performDepartment || 'Department';
    this.sectionLabel = `Equipment Calibration (${dept})`;
    this.cards = [
      {
        id: 'pending',
        title: 'Pending Calibration',
        route: 'pending',
        icon: 'fa-tasks',
        category: 'Modules',
        gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)',
      },
      {
        id: 'log',
        title: 'Calibration Log',
        route: 'log',
        icon: 'fa-clipboard-list',
        category: 'Modules',
        gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      },
    ];
  }
}
