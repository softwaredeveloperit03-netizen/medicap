import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'map-material-palate', title: 'Map Material to Palate', route: 'map-material-palate', icon: 'fa-link', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'map-palate-location', title: 'Map Palate to Location', route: 'map-palate-location', icon: 'fa-map-marked-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'empty-material-palate', title: 'Empty Material from Palate', route: 'empty-material-palate', icon: 'fa-unlink', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'empty-palate-location', title: 'Empty Palate from Location', route: 'empty-palate-location', icon: 'fa-map-marked', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];


  constructor(private service: DataAccessService) { }

  ngOnInit() {
  }

}
