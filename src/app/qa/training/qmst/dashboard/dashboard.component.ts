import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'new', title: 'QMS Training New', route: 'new', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'annoncement', title: 'QMS Training Announcement', route: 'annoncement', icon: 'fa-bullhorn', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'attendance', title: 'QMS Training Attendance', route: 'attendance', icon: 'fa-calendar-check', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'evaluation', title: 'QMS Training Evaluation', route: 'evaluation', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'log', title: 'QMS Training Report', route: 'log', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'annoncement', title: 'QMS Training Annoncement', route: 'annoncement', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
  ];


  constructor(private router:Router) { }

  ngOnInit(): void {
  }
  onChangeTraining(trainingType: string) {
    console.log(trainingType);
    switch(trainingType) {
      case 'Need Based Training':
        this.router.navigate(['qa/training/need']);
        break;
      case 'On Job Training':
        this.router.navigate(['qa/training/job-training']);
        break;
      case 'Document Training Training':
        this.router.navigate(['qa/training/document']);
        break;
      case 'QMS Training':
        this.router.navigate(['qa/training/qms']);
        break;
      case 'Retraining Training':
        this.router.navigate(['qa/training/retraining']);
        break;
      case 'Daily Training':
        this.router.navigate(['qa/training/daily']);
        break;
      case 'Induction Training':
        this.router.navigate(['qa/training/induction']);
        break;
      default:
        break;
    }
  }
}
