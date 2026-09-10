import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'checklist-master', title: 'CheckList Master', route: 'checklist-master', icon: 'fa-list', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'asset', title: 'Asset Master', route: 'asset', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'log', title: 'Leave Policy Master', route: 'log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];


  constructor(
    private service: DataAccessService,
    private masterHubReturn: MasterHubReturnService
  ) { }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master');
  }
  plant_id;
  ngOnInit(): void {
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }

}
