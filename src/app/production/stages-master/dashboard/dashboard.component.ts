import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  isview = false;
  results;
  selectedResult;
  stages;
  fg_sub_materials;
  dosage_form;
  plant_type = 'Formulation';

  constructor(private service: DataAccessService) { this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.plant_type = this.service.getPlantConfigFields('plant_type');

    this.getStages();
    this.master_fg_types();
    this.get_rights();
    this.getSamplingType();
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

  getStages() {
    this.service.get("production/stage.php?type=get_iqpc_stages&material_type='Raw Material'").subscribe((response) => {
        this.results = response;
      });
  }

  samplingType = '';
  samplingType1 = '';

  getSamplingType() {
    this.service.get("production/stage.php?type=getSamplingType&field=samplingType").subscribe((response) => {
        this.samplingType = response['samplingType'];
        this.samplingType1 = response['samplingType'];
      });
  }


  downloadLog() {
    this.service.open('production/stage.php?type=downloadlog');
  }

  master_fg_types() {
    this.service
      .get('production/stage.php?type=fg_sub_materials')
      .subscribe((response) => {
        this.fg_sub_materials = response;
      });
  }


  isSampling = false;

  openSampling(){
    this.isSampling = true;

  }




  view(val) {
    
    this.selectedResult = this.results[val];
    this.stages =  this.selectedResult['stages_test'];
    this.isview = true;
 
  }


  IsEDIT = false;

  edit(){
    this.samplingType1 = '';
    this.IsEDIT = true;
  }



  save(form) {    

    if (!form.valid) {
      alert('All Fields are required');
      return;
    }

    let temp ={};
    temp['fieldValue'] = this.samplingType;
    temp['field'] = "samplingType";
 
    this.service.post('production/stage.php?type=saveSamplingType', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getSamplingType();
        alert('Samplig Type Saved Successfully');
        form.reset();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }


  editdata(form) {    

    if (!form.valid) {
      alert('All Fields are required');
      return;
    }

    let temp ={};
    temp['fieldValue'] = this.samplingType;
    temp['field'] = "samplingType";

    this.samplingType1 = this.samplingType;
 
    this.service.post('production/stage.php?type=editSamplingType', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getSamplingType();
        alert('Samplig Type Saved Successfully');
        form.reset();
        this.IsEDIT =  false;
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }



 
}
