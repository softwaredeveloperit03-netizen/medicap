import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'area', title: 'Proceed for Sampling', route: 'area', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'line', title: 'QA Line Clearance', route: 'line', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'sample', title: 'Start Sampling', route: 'sample', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];

 
  isNew = false;
  results;

  selectedSampling = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getAwaitingSamplings();
  }

  getAwaitingSamplings(){
    this.service.get('qc/sampling/packing.php?type=getAwaitingSamplings').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedSampling = this.results[index];
    this.isNew = true;
  }

}
