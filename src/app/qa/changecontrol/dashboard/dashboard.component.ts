import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import * as ApexCharts from 'apexcharts';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html'
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'new', title: 'New Change Control', route: '../new', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'checking', title: 'Change Control for Checking', route: '../checking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'review', title: 'Change Control For Review', route: '../review', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'verify', title: 'Change Control for Verification', route: '../verify', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'approve', title: 'Change Control For Approval', route: '../approve', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'implement', title: 'Change Control Implementation', route: '../implement', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'log', title: 'Change Control Trend / Log', route: '../log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];


  isUser = false;
  isChecker = false;
  isApprover = false;
  options;
  chart;
  options1;
  chart1;
  constructor(private service: DataAccessService) {
    this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver'))); 
  }

  ngOnInit(): void {
    this.getcategorychart();
    this.getdepartmentchart();
  }

  getcategorychart(){
    this.service.get('changecontrol.php?type=getcategorychart').subscribe((response:any) =>{
      this.options = {
        series: response['series'],
        labels: response['lables'],
        chart: { type: 'pie', },
      };
      this.chart = new ApexCharts(document.querySelector("#chart"), this.options);
      this.chart.render();
    })
  }

  getdepartmentchart(){
    this.service.get('changecontrol.php?type=getdepartmentchart').subscribe((response:any) =>{
      this.options1 = {
        series: response['series'],
        labels: response['lables'],
        chart: { type: 'pie', },
      };
      this.chart1 = new ApexCharts(document.querySelector("#chart1"), this.options1);
      this.chart1.render();
    })
  }

}
