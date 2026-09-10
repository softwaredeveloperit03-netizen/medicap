import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'packing-configuration', title: 'Packing Configuration', route: 'packing/configuration', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'new', title: 'New Work Order', route: 'new', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];

  isView = false;
  results;

    selectedResult = [];
    primarymaterialList = [];
    secondarymaterialList = [];
    tertiarymaterialList = [];
    othermaterialList = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getdatalist();
  }

    getdatalist() {
        this.service.get('packing/workorder.php?type=workOrderList').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
      this.isView = true;
      this.primarymaterialList = this.selectedResult['primary_material'];
      this.secondarymaterialList = this.selectedResult['secondary_material'];
      this.tertiarymaterialList = this.selectedResult['tertiary_material'];
      this.othermaterialList = this.selectedResult['other_material'];
  }

  download(value) {
    if (value == 'manual') {
      this.service.open('pdf1/sop.php?type=sop&sop_no=' + this.selectedResult['sop_no']);
    } else if (value == 'digital') {
      this.service.open('pdf1/sop.php?type=sopdigital&sop_no=' + this.selectedResult['sop_no']);
    } else {
      this.service.open('pdf1/sop.php?type=log');
    }
  }

}
