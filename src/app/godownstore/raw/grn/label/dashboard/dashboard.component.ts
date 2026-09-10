import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'awaiting', title: 'Awaiting GRN For Label', route: 'awaiting', icon: 'fa-tags', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'additional', title: 'Additional Labels', route: 'additional', icon: 'fa-tags', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'additional', title: 'Additional Lables', route: 'additional', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];

  from_date='';
  to_date='';
  labels;
  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    // this.getLabels();
  }
  getLabels(){
    this.service.get('store/label.php?type=getGRNLabels&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.labels=response;
    })
  }
  download(){
    this.service.open('store/label.php?type=downloadGRNLabels&from_date='+this.from_date+'&to_date='+this.to_date);
  }

}
