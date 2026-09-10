import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'identification', title: 'Training Need Ident.', route: 'identification', icon: 'fa-user-graduate', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'individual', title: 'Training Announc.', route: 'Individual', icon: 'fa-bullhorn', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'certificate', title: 'Attendance Training', route: 'certificate', icon: 'fa-file-signature', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'evaluate', title: 'Training Evaluation', route: 'evaluate', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'retraining', title: 'Retraining Training', route: 'retraining', icon: 'fa-redo', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'induction', title: 'Training Log', route: 'Induction', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'identification', title: 'Training Need Identification', route: 'identification', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'individual', title: 'Training Announcement', route: 'Individual', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'induction', title: 'Induction Training', route: 'induction', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'individual', title: 'Individual Training Record', route: 'individual', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
