import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-primary',
  templateUrl: './primary.component.html',
  styleUrls: ['./primary.component.css'],
})
export class PrimaryComponent implements OnInit {
  isUser = false;
  isChecker = false;
  isApprover = false;
  clicked = false;
  isInit = true;
  isMoa = false;
  isTestStart = false;
  testings;
  units;
  limit_type = '';
  selected_test;
  selectedTesting = [];
  selectedMOA = [];
  spec_tests = [];
  rechecks = [];
  pendings = [];
  tests = [];
  testing_persons;
  selected_test_idx = 0;
  isStart = false;
  todayDate: Date = new Date();
  start_date;
  end_time;
  end_date;
  isStop = false;
  isData = false;
  isData1 = false;

  constructor(private service: DataAccessService, private router: Router) {
      this.loggedInDept = localStorage.getItem('department');
    // this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    // this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    // this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }
  modal(index) {
    this.selected_spec_test1 = this.spec_tests[index];
    console.log(this.selected_spec_test);
    this.getCheckPointData();
    this.isData = true;
  }
  selected_spec_test: any = [];
  selected_spec_test1: any = [];

  ngOnInit() {
    this.getPendingTestingForms();
    this.getTestingPersons();
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
         localStorage.getItem('department')
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

  checkPointData;
  getCheckPointData() {
    this.service
      .get(
        'store/raw.php?type=getCheckPointByForm&module=Sampling-Correction&form=Primary Checklist'
      )
      .subscribe((response) => {
        this.checkPointData = response;
      });
  }

  getPendingTestingForms() {
    this.service
      .get(
        'qc/testing/raw.php?type=getRejectedTestingForms_primary_oos&is_rds=' +
          0
      )
      .subscribe((response) => {
        this.testings = response;
      });
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe((response) => {
      this.units = response;
    });
  }

  viewSpecification(index) {
    // this.rechecks = [];
    // this.pendings = [];
    this.selectedTesting = this.testings[index];
    this.isInit = false;
    this.spec_tests = this.selectedTesting['tests'];

    // if (this.selectedTesting['spec_status'] == 'done') {
    //   this.tests = this.selectedTesting['tests'];

    //   let i = 0, j = 0;
    //   this.tests.forEach(element => {
    //     if (element['status'] == 'recheck' && element['fail_status'] == 'correction' && element['fail_form'] == 'error1') {
    //       this.rechecks[i] = element;
    //       i++;
    //     } else {
    //       this.pendings[j] = element;
    //       j++;
    //     }
    //   });
    //   this.isInit = false;
    // } else {
    //   alertify.error(this.selectedTesting['spec_status']);
    // }
  }
  getTestingPersons() {
    this.service
      .get('common.php?type=get_qc_testing_persons')
      .subscribe((response) => {
        this.testing_persons = response;
      });
  }
  viewMOA(index) {
    this.selected_test_idx = index;
    let moa = this.selectedTesting['tests'];
    this.selectedMOA = moa[index];
    this.selected_test = this.selectedTesting['tests'][index];
    this.limit_type = this.selected_test['limit_type'];
    this.isTestStart = true;
    // this.isMoa = true;
    // this.isInit=true;
    // this.getUnits();
  }

  updateResult(data) {
    if (!data.valid) {
      alertify.error('All Fields Are Required');
      return;
    }

    this.spec_tests[this.selected_test_idx]['result'] = data.value['result'];
    this.spec_tests[this.selected_test_idx]['tested_by'] =
      data.value['tested_by'];
    this.spec_tests[this.selected_test_idx]['incident_oos_no'] =
      data.value['incident_oos_no'];
    this.spec_tests[this.selected_test_idx]['remarks'] = data.value['remarks'];
    this.spec_tests[this.selected_test_idx]['tested_date'] =
      data.value['test_date'];
    this.spec_tests[this.selected_test_idx]['tested_time'] =
      data.value['test_time'];
    if (this.limit_type == 'Range') {
      if (
        Number(data.value['result']) <
          Number(this.spec_tests[this.selected_test_idx]['lower_limit']) ||
        Number(data.value['result']) >
          Number(this.spec_tests[this.selected_test_idx]['upper_limit'])
      ) {
        this.spec_tests[this.selected_test_idx]['status'] = 'Non Complies';
      } else {
        this.spec_tests[this.selected_test_idx]['status'] = 'Complies';
      }
    } else {
      this.spec_tests[this.selected_test_idx]['status'] = data.value['status'];
    }

    this.service
      .post(
        'qc/testing/raw.php?type=saveTestingForm',
        JSON.stringify(this.spec_tests[this.selected_test_idx])
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          data.resetForm();
          this.getPendingTestingForms();
          data.resetForm();
          this.isTestStart = false;
          alertify.success('test successfully send for approval');
        } else {
          alertify.error('An error occured, please try again');
        }
      });
  }

  updateSamplingTime(value) {
    var d = new Date(),
      h = (d.getHours() < 10 ? '0' : '') + d.getHours(),
      m = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
    if (value == 'start') {
      this.start_date = new Date();
      this.isStart = true;
    } else if (value == 'end') {
      var d = new Date(),
        h = (d.getHours() < 10 ? '0' : '') + d.getHours(),
        m = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
      let time = new Date().toLocaleTimeString();
      this.end_time = h + ':' + m;
      this.end_date = new Date();
      this.isStop = true;
    }
  }

  submitTest(data) {
    // console.log(data);
    // if (!data) {
    //   alertify.error('All Field are required');
    //   return;
    // }
    // let temp = data.value;
    // temp['test_no'] = this.selectedMOA["id"];
    // temp['testing_no'] = this.selectedMOA["testing_no"];

    // let descriptions = this.selectedMOA['method_details'];
    // for (let i = 0; i < descriptions.length; i++) {
    //   let description = descriptions[i];

    //   if (description["option"] == "chemical") {
    //     let chemicals = description['list'];
    //     for (let j = 0; j < chemicals.length; j++) {
    //       let chemical = chemicals[j];
    //       chemical['batches'] = null;
    //       chemicals[j] = chemical;
    //     }
    //     description['list'] = chemicals;
    //   }
    //   descriptions[i] = description;
    // }
    // temp['descriptions'] = descriptions;

    let selTestingRec: any = this.selectedTesting;
    this.service
      .post(
        'qc/testing/raw.php?type=updateTestingStatus_primary_oos&testingID=' +
          this.selectedTesting['testing_no'],
        {}
      )
      .subscribe((response) => {
        this.router.navigate(['/qc/testing-rds/raw/checking']);
      });
  }
  oos_no;
  oos_date;
  incident_description;
  immediate_cause;
  correct_result;
  error_type;
  errorUpdate(data) {
    console.log(data);
    // if (!data.valid) {
    //   alertify.error('All Field are required');
    //   return;
    // }
    let temp = data.value;
    temp['checkPointData'] = this.checkPointData;
    temp['oos_date'] = this.oos_date;
    temp['oos_no'] = this.oos_no;
    temp['incident_description'] = this.incident_description;
    temp['immediate_cause'] = this.immediate_cause;
    temp['correct_result'] = this.correct_result;
    temp['error_type'] = this.error_type;
    temp['specification_no '] = this.selectedTesting['specification_no'];
    temp['sampling_no '] = this.selectedTesting['sampling_no'];

    this.service
      .post(
        'qc/testing/raw.php?type=testing_correction&id=' +
          this.selected_spec_test['id'] +
          '&testing_no=' +
          this.selectedTesting['testing_no'] +
          '&sampling_no=' +
          this.selectedTesting['sampling_no'] +
          '&specification_no=' +
          this.selectedTesting['specification_no'],
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          data.resetForm();
          this.getPendingTestingForms();
          this.isMoa = false;
          this.isInit = true;
          this.router.navigate(['/qc/testing-erp/raw/home']);
          alertify.success('test successfully send for approval');
        } else {
          alertify.error('An error occured, please try again');
        }
      });
  }
  errorUpdateIncident(data) {
    console.log(data);
    // if (!data.valid) {
    //   alertify.error('All Field are required');
    //   return;
    // }
    let temp = data.value;
    temp['checkPointData'] = this.checkPointData;
    temp['oos_date'] = this.oos_date;
    temp['oos_no'] = this.oos_no;
    temp['incident_description'] = this.incident_description;
    temp['immediate_cause'] = this.immediate_cause;
    temp['correct_result'] = this.correct_result;
    temp['error_type'] = this.error_type;
    temp['specification_no '] = this.selectedTesting['specification_no'];
    temp['sampling_no '] = this.selectedTesting['sampling_no'];

    this.service
      .post(
        'qc/testing/raw.php?type=testing_correction_incident&id=' +
          this.selected_spec_test1['id'] +
          '&testing_no=' +
          this.selectedTesting['testing_no'] +
          '&sampling_no=' +
          this.selectedTesting['sampling_no'] +
          '&specification_no=' +
          this.selectedTesting['specification_no'],
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          data.resetForm();
          this.getPendingTestingForms();
          this.isMoa = false;
          this.isInit = true;
          this.router.navigate(['/qc/testing-erp/raw/home']);
          alertify.success('test successfully send for approval');
        } else {
          alertify.error('An error occured, please try again');
        }
      });
  }

  Check() {
    // this.service.get('qc/testing/raw.php?type=oos_check&testing_no='+this.selectedTesting['testing_no']+'&testing_test_id='+this.selected_spec_test1['testing_test_id']).subscribe(response => {
    //   this.testing_persons = response;
    // });

    this.service
      .post(
        'qc/testing/raw.php?type=oos_check&testing_no=' +
          this.selectedTesting['testing_no'] +
          '&testing_test_id=' +
          this.selected_spec_test1['testing_test_id'],
        {}
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          // data.resetForm();
          this.getPendingTestingForms();

          this.isInit = true;
          this.router.navigate(['/qc/oos/primary']);
          alertify.success(' success');
        } else {
          alertify.error('An error occured, please try again');
        }
      });
  }
}
