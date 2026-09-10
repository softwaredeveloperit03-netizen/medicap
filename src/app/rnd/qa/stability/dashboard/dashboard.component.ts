import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'rnd-qa-stability-initiate', title: 'Initiate Stability', route: 'rnd/qa/stability/initiate', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-sampling', title: 'Stability Sampling', route: 'rnd/qa/stability/sampling', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-charging', title: 'Stability Charging', route: 'rnd/qa/stability/charging', icon: 'fa-charging-station', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-schedule', title: 'Schedule of Study', route: 'rnd/qa/stability/schedule', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-allocation', title: 'St. Interval Allocation', route: 'rnd/qa/stability/allocation', icon: 'fa-chart-bar', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-testing', title: 'Testing', route: 'rnd/qa/stability/testing', icon: 'fa-vials', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-summary', title: 'Summary Report', route: 'rnd/qa/stability/summary', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-chember', title: 'St. Chamber Log', route: 'rnd/qa/stability/chember', icon: 'fa-thermometer-half', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-calender', title: 'Stability Calendar', route: 'rnd/qa/stability/calender', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-trend', title: 'Stability Trend', route: 'rnd/qa/stability/trend', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-allocation', title: 'Stability Interval Allocation', route: 'rnd/qa/stability/allocation', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'deviation', title: 'Deviation', route: 'deviation', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-chember', title: 'Stability Chamber Log', route: 'rnd/qa/stability/chember', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'rnd-qa-stability-calender', title: 'Stability Calender', route: 'rnd/qa/stability/calender', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
