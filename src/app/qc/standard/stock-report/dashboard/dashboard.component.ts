import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'quarantine', title: 'Reference Standard Stock', route: 'quarantine', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'working', title: 'Working Standard Stock', route: 'working', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'approved', title: 'Primary Standard Stock', route: 'approved', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'expired', title: 'Expired Standards', route: 'expired', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];

  checklist:any=[
   
  ];
  
    stocks;
    stock;
    isView = false;
    grade: any;
    material_type;
    standard_name;
    loading;
    selectedReport: any = {};
    results: any;
    stocpurchase: any;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getAllStock();
  }
  
 
  getAllStock(){
    this.service.get('qc/standard/stock-report.php?type=getAllStock&material_type='+this.material_type+'&grade='+this.grade+'&standard_name='+this.standard_name).subscribe(response => {
      this.stocks = response;
    });
  }

  view(index) {
 
    this.selectedReport=this.stocks[index] 
    this.isView = true;
   
  }

  download(){
    this.service.open('qc/standard/stock-report.php?type=downloadStandardstockorder&material_type='+this.material_type);
  }
  CLOSEView() {
    this.isView = false;
  }
}
