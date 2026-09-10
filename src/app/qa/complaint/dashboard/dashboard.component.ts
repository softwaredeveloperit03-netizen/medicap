import { Component, OnInit } from '@angular/core';
import * as ApexCharts from 'apexcharts';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  options;
  chart;
  summary = { pending: 0, approval: 0, approved: 0, total: 0 };
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getMarketChart();
    this.getMarketSummary();
    this.get_rights();
  }

  getMarketChart() {
    this.service
      .get('qaDepartment.php?type=getmarketchart')
      .subscribe((response: any) => {
        if (this.chart) {
          this.chart.destroy();
        }
        this.options = {
          series: response['series'] || [],
          labels: response['lables'] || [],
          chart: { width: '100%', type: 'pie' },
        };
        this.chart = new ApexCharts(
          document.querySelector('#chart1'),
          this.options
        );
        this.chart.render();
      });
  }

  getMarketSummary() {
    this.service
      .get('qaDepartment.php?type=getMarketComplaintSummary')
      .subscribe((response: any) => {
        this.summary = {
          pending: Number(response?.pending) || 0,
          approval: Number(response?.approval) || 0,
          approved: Number(response?.approved) || 0,
          total: Number(response?.total) || 0,
        };
      });
  }

  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//
}
