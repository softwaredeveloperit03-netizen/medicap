import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'master', title: 'Asset Master', route: 'master', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  ];


  constructor(private masterHubReturn: MasterHubReturnService) { }

  ngOnInit(): void {
  }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master/hra');
  }

}
