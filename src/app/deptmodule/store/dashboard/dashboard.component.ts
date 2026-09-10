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
    { id: 'levapproval', title: 'Leverages Approval', route: 'levApproval', icon: 'fa-thumbs-up', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  ];


  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.levrageForApproval();
   }

  unreadlevarage = 0;
 
  levrageForApproval() {
    this.service.get('notification.php?type=getInprocessReceivingsLeveragesNotification').subscribe(response => {
      this.unreadlevarage = response['Pending_Levarage'];
      if(this.unreadlevarage > 0){
        alertify.warning(response['text']);
      }
    });
  }

}