import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe],
})
export class DashboardComponent implements OnInit {
  isView = false;
  results: any[] = [];
  loading = false;
  selectedResult: any = {};
  raw_materials: any[] = [];
  packing_materials: any[] = [];
  plant_type = '';
  from_date = '';
  to_date = '';
  today = '';
  product_name = '';
  showTailingBatches;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
     this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
  }

  ngOnInit() {
    this.getPlans();
    this.get_rights();
  }

  getPlans() {
    this.loading = true;
    this.service
      .get(
        'production/plan.php?type=getPlans&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe({
        next: (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.loading = false;
        },
      });
  }

  view(row: any) {
    if (!row) {
      return;
    }
    this.selectedResult = row;
    this.packing_materials = Array.isArray(row.packing_material)
      ? row.packing_material
      : [];
    this.raw_materials = Array.isArray(row.raw_materials)
      ? row.raw_materials
      : [];
    this.isView = true;
  }

  download() {
    this.service.open(
      'production/plan.php?type=downloadPlanningProductionDashboard&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
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
      .subscribe((response: any) => {
        this.rights = response;
        const r = Array.isArray(response) && response[0] ? response[0] : null;
        if (!r) {
          return;
        }
        this.isuser = r.isuser;
        this.ischecker = r.ischecker;
        this.isapprover = r.isapprover;
        this.qms_approver = r.qms_approver;
        this.dept_head = r.dept_head;
        this.isauditor = r.isauditor;
        this.plant_head = r.plant_head;
        this.shift_allocator = r.shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//
}
