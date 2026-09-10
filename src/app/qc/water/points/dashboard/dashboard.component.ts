import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  loading;
  // results=[];
  // results1=[];
  results;
  departments;
  water_type = '';
  point_name = '';
  department = '';
  preventive = [
    {
      id: 1,
      particular: 'Daily',
      checked: false,
      last_insp_date: '',
      last_prevent_date: '',
    },
    {
      id: 2,
      particular: 'Weekly',
      checked: false,
      last_insp_date: '',
      last_prevent_date: '',
    },
    {
      id: 3,
      particular: 'FortNightly',
      checked: false,
      last_insp_date: '',
      last_prevent_date: '',
    },
    {
      id: 4,
      particular: 'Monthly',
      checked: false,
      last_insp_date: '',
      last_prevent_date: '',
    },
    {
      id: 5,
      particular: 'Quarterly',
      checked: false,
      last_insp_date: '',
      last_prevent_date: '',
    },
    {
      id: 6,
      particular: 'Half-Yearly',
      checked: false,
      last_insp_date: '',
      last_prevent_date: '',
    },
    {
      id: 7,
      particular: 'Annually',
      checked: false,
      last_insp_date: '',
      last_prevent_date: '',
    },
  ];
  constructor(private service: DataAccessService) {  this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getPointsLog();
    // this.getDepartments();
    this.get_rights();
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

  // getDepartments(){
  //   this.service.get('common.php?type=getDepartments').subscribe(response => {
  //     this.departments = response;
  //   });
  // }

  getPointsLog() {
    this.service.get('qc/water.php?type=getPointsLog').subscribe((response) => {
      this.results = response;
      //this.results1=response;
    });
  }
  download() {
    this.service.open('qc/water.php?type=downloadPointsLog');
  }

  // filterStock(){
  //   this.results1 = [];
  //   for(let i=0; i<this.results1.length; i++){
  //     let data = this.results1[i];
  //     if(data.water_type.toUpperCase().includes(this.water_type.toUpperCase()) && data.point_name.toUpperCase().includes(this.point_name.toUpperCase()) && data.department.toUpperCase().includes(this.department.toUpperCase())){
  //       this.results1.push(data);
  //     }
  //   }
  // }

  // clear(){
  //   this.water_type='';
  //   this.point_name='';
  //   this.department='';
  //   //this.results = this.results1;
  // }
  isAllocation = false;
  isAllocation1 = false;
  id;
  addfreq(id) {
    const row = (this.results || []).find((r) => String(r.id) === String(id));
    const existing = Array.isArray(row?.frequency) ? row.frequency : [];
    const selected = new Set(existing.map((f) => String(f?.particular || '').trim()));
    this.preventive = this.preventive.map((item) => ({
      ...item,
      checked: selected.has(item.particular),
    }));
    this.isAllocation = true;
    this.id = id;
  }
  selectedResult = [];
  addSch(index) {
    this.selectedResult = this.results[index];
    console.log('this.selectedResult :>> ', this.selectedResult);
    this.isAllocation1 = true;
  }

  saveFreq(data) {
    let temp = data.value || {};
    temp['id'] = this.id;
    temp['freq'] = this.preventive.filter((item) => item.checked);
    if (!temp['id']) {
      alertify.error('Point id missing.');
      return;
    }
    if (temp['freq'].length === 0) {
      alertify.error('Please select at least one frequency.');
      return;
    }

    this.service
      .post('qc/water.php?type=saveFreq', JSON.stringify(temp))
      .subscribe((response) => {
        alertify.success('submitted succesfully');
        data.reset();
        this.getPointsLog();
        this.isAllocation = false;
      });
  }
  saveFreq1() {
    let temp = {};

    temp['freq'] = this.selectedResult['frequency'];
    temp['id'] = this.selectedResult['id'];

    this.service
      .post('qc/water.php?type=saveFreq1', JSON.stringify(temp))
      .subscribe((response) => {
        alertify.success('submitted succesfully');

        this.getPointsLog();
        this.isAllocation1 = false;
      });
  }
}
