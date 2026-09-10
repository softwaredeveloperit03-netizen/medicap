import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'masters', title: 'FG Control Sample Management', route: 'masters', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'masters-rack', title: 'Rack Master', route: 'masters/rack', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'raw', title: 'Raw Material Register', route: 'raw', icon: 'fa-archive', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'packing', title: 'Packing Material Reg', route: 'packing', icon: 'fa-box', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'finish', title: 'Finish Products Reg', route: 'finish', icon: 'fa-dolly', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'withdrawal', title: 'Withdrawal', route: 'withdrawal', icon: 'fa-file-import', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'review', title: 'Ctrl Sample Review', route: 'review', icon: 'fa-search', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'expired', title: 'Expired Ctrl Sample', route: 'expired', icon: 'fa-calendar-times', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'distroy', title: 'Destroy Sample Log', route: 'distroy', icon: 'fa-trash-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
