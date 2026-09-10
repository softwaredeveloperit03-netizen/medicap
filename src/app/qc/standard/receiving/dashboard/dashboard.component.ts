import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'challan', title: 'Challan Entry', route: 'challan', icon: 'fa-file-invoice', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  ];


  results;
  isView = false;
  selectedResult = [];
  status;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getOrdering();
  }

  getOrdering(){
    this.service.get('qc/standard/order.php?type=getOrdering').subscribe(response => {
      this.results = response;
    })
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  update(status) {
    this.service.get('qc/standard/order.php?type=recevingOrders&status=' + status + '&id=' +this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Data updated successfully');
        this.isView = false;
        this.getOrdering();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
