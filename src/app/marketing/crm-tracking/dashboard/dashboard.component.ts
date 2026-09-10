import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'email-new', title: 'New Email', route: 'email/new', icon: 'fa-envelope', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'email-log', title: 'Email Log', route: 'email/log', icon: 'fa-envelope-open', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'email-followup', title: 'Email Followup', route: 'email/followup', icon: 'fa-redo', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'phone-new', title: 'New Call', route: 'phone/new', icon: 'fa-phone', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'phone-new2', title: 'Update Call', route: 'phone/new2', icon: 'fa-phone', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'phone-log', title: 'Call Log', route: 'phone/log', icon: 'fa-phone-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'phone-followup', title: 'Call Followup', route: 'phone/followup', icon: 'fa-phone-volume', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];


 
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
   
  }
 
}


