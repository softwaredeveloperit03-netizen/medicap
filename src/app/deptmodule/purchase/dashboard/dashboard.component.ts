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
    { id: 'purchase', title: 'PURCHASE REQUISITION APPROVAL', route: 'indend', icon: 'fa-check-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'poapproval', title: 'PO Approval', route: 'poapproval', icon: 'fa-check-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];


  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
     this.indentApproval();
  }

  unreadindent = 0;

  indentApproval() {
    this.service.get('notification.php?type=getIndentForApprovalPlantHeadNotification').subscribe(response => {
      this.unreadindent = response['Pending_indent'];
      if(this.unreadindent > 0){
        alertify.warning(response['text']);
      }
    });
  }
}
