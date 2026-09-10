import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'list', title: 'List of Tanks', route: 'list', icon: 'fa-list', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'checklist', title: 'Checklist Master', route: 'checklist', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'points', title: 'User Point Master', route: 'points', icon: 'fa-map-pin', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'cleaning-cleaning-schedule', title: 'Schedule', route: 'cleaning/cleaning_schedule', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'cleaning-cleaning-processs', title: 'Cleaning Process', route: 'cleaning/cleaning_processs', icon: 'fa-broom', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'cleaning-cleaning-logbook', title: 'Cleaning Log Book', route: 'cleaning/cleaning_logbook', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'sanitization-sanitization-schedule', title: 'Schedule', route: 'sanitization/sanitization_schedule', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'sanitization-sanitization-processs', title: 'Sanitization Process', route: 'sanitization/sanitization_processs', icon: 'fa-wrench', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'sanitization-sanitization-logbook', title: 'Sanitization Log Book', route: 'sanitization/sanitization_logbook', icon: 'fa-book-open', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'wfilog', title: 'WFI System Logbook', route: 'wfilog', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'waterclrecord', title: 'Water Chlorination Record', route: 'waterclrecord', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'purifierlog', title: 'Purified Water Loop Sanitization Record', route: 'purifierlog', icon: 'fa-recycle', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'purifierplant', title: 'Purified Water Plant Log Book', route: 'purifierPlant', icon: 'fa-water', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'uvcabinate', title: 'UV Cabinate Cleaning Record', route: 'uvcabinate', icon: 'fa-sun', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'engineering-water-soft-water-generation-sys', title: 'Soft Water Generation System', route: 'engineering/water/Soft_Water_Generation_Sys', icon: 'fa-check-square', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'watersystem', title: 'Water System Passivation Record', route: 'Watersystem', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'waterhardnessil', title: 'Water Hardness Conductivity checks', route: 'waterhardnessil', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'operation', title: 'Water Operation of Pretreatment Plant', route: 'operation', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit() {
  }

}
