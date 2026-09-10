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
    { id: 'rawpacking-approval', title: 'Raw Packg Rej Apprvl', route: '../rawpacking-approval', icon: 'fa-times-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'online-approval', title: 'Online Rej Apprvl', route: '../online-approval', icon: 'fa-check-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'rawpackingreport', title: 'Raw/Packg Rej Report', route: '../rawpackingreport', icon: 'fa-chart-bar', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'onlinereport', title: 'Online Rej Report', route: '../onlinereport', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'destruction', title: 'Destruction Report', route: '../destruction', icon: 'fa-trash-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'return', title: 'Return Report', route: '../return', icon: 'fa-undo', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'rawpacking-approval', title: 'Raw Packing Rejection Approval', route: '../rawpacking-approval', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'online-approval', title: 'Online Rejection Approval', route: '../online-approval', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'rawpackingreport', title: 'Raw / Packing Rejection Report', route: '../rawpackingreport', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'onlinereport', title: 'Online Rejection Report', route: '../onlinereport', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
  ];

  options;
  chart;
  options1;
  chart1;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getonlinechart();
    this.getdestrionchart();
    this.get_rights();

  }

  getonlinechart(){
    this.service.get('rejection.php?type=getonlinechart').subscribe((response:any) =>{
      this.options = {
        series: response['series'],
        labels: response['lables'],
        chart: { type: 'pie', },
      };
      this.chart = new ApexCharts(document.querySelector("#chart"), this.options);
      this.chart.render();
    })
  }

  getdestrionchart(){
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
  rights;
  righ;
  ischecker;
isapprover;
qms_approver;
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&module_name=Quotation&department1=Quality Assurance&form_type=user&form_name=New Quotation&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.righ=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      this.qms_approver=this.rights[0].qms_approver
      console.log(this.rights)
      console.log(this.righ)
      console.log(this.qms_approver)
    });
  }
}
