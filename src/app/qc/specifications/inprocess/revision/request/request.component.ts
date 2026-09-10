import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {
  specifications;
  dosages;
  isViewSpecification = false;
  selectedSpec = [];
  materiallist = [];
  dosage_form = '';
  grade = '';
  status = '';
  maxdate;

  results;
  isView=false;
  isImpactQuality = false;
  departments;






  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
   this.getDosages();
    this.getApprovedSpecifications();
    this.getDepartments();
  }

  getApprovedSpecifications(){
    this.service.get('qc/specification/inprocess.php?type=getApprovedSpecifications').subscribe(response=>{
    this.results=response;
    });
  }

  getDepartments() {
    this.service.get('changecontrol.php?type=getDepartments').subscribe((response:any) =>{
      this.departments =  response;
    });
  }

getDosages(){
    this.service.get('qc/specification/inprocess.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }   

  viewSpecification(index) {
    this.selectedSpec = this.results[index];
    this.selectedSpec['dosage_form'] = this.results['dosage_form'];
    this.isViewSpecification = true;
  }


  saveForm(data) {
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;

    let test = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status']) {
        test[test.length] = department['department_name'];
      }
    }
    temp['departments'] = test;
    

    this.service.post('qc/specification/inprocess.php?type=saveRevisionRequest&specification_no='+this.selectedSpec['specification_no']+'&version_no='+this.selectedSpec['version_no'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        data.resetForm();
        this.router.navigate(['/changecontrol']);
        alertify.success('Successfully send for Approval');
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
    });
  }

  checkImpactQuality(value) {
    if (value === 'Yes') {
      this.isImpactQuality = true;
    } else {
      this.isImpactQuality = false;
    }
  }

  updateDept(value, i) {
    this.departments[i].status = value;
  }

  revision(index){
    this.selectedSpec=this.results[index];
    this.isView=true;
  }


}
