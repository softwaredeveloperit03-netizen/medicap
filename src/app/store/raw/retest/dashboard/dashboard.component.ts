import { Component } from '@angular/core';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

@Component({
  selector: 'app-store-raw-retest-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent {
  cards: QcDeptCard[] = [
    { id: 'awaiting', title: 'Await Material for Retest', route: 'awaiting', icon: 'fa-hourglass-start', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'retest-detail', title: 'RM/PM Retest Detail', route: 'retest-detail', icon: 'fa-list-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'expired', title: 'Expired Materials Detail', route: 'expired-materials', icon: 'fa-exclamation-triangle', category: 'Modules', gradient: 'linear-gradient(135deg, #f83600 0%, #764ba2 100%)' },
    { id: 'calender', title: 'Retest Calendar', route: 'calender', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'intimation-slip', title: 'Retest Intimation Slip', route: '/store/retest-intimation-slip', icon: 'fa-file-signature', category: 'Modules', gradient: 'linear-gradient(135deg, #a18cd1 0%, #764ba2 100%)' },
    { id: 'log', title: 'Retest Reports', route: 'log', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];
}
