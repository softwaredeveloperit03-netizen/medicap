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
    { id: 'awaiting', title: 'Awaiting', route: 'awaiting', icon: 'fa-hourglass-half', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];


  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getPendingInterview();
  }

  unreadrinterview =0;

  getPendingInterview() {
    this.service.get('notification.php?type=getPendingInterviews').subscribe(response => {
      this.unreadrinterview = Number(response['Pending_interview']);
    
    });
  }


}
